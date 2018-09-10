<?php
/**
 * Run test suite command
 *
 * @package wpassure
 */

namespace WPAssure\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\Question;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use PHPUnit\TextUI\Command as PHPUnitCommand;

use WPAssure\EnvironmentFactory;
use WPAssure\Log;
use WPAssure\Utils;
use WPAssure\Config;
use WPSnapshots\Connection;
use WPSnapshots\Snapshot;
use WPSnapshots\Log as WPSnapshotsLog;

/**
 * Run test suite
 */
class Run extends Command {

	/**
	 * Setup up command
	 */
	protected function configure() {
		$this->setName( 'run' );
		$this->setDescription( 'Run an WPAssure test suite.' );

		$this->addOption( 'local', false, InputOption::VALUE_NONE, 'Run tests against local WordPress install.' );
		$this->addOption( 'save', false, InputOption::VALUE_NONE, 'If tests are successful, save snapshot ID to wpassure.json and push it to the remote repository.' );

		$this->addOption( 'suite_config_directory', false, InputOption::VALUE_REQUIRED, 'Path to a directory that contains wpassure.json.' );
		$this->addOption( 'snapshot_id', null, InputOption::VALUE_REQUIRED, 'WP Snapshot ID.' );
		$this->addOption( 'wp_directory', null, InputOption::VALUE_REQUIRED, 'Path to WordPress wp-config.php directory.' );
		$this->addOption( 'db_host', null, InputOption::VALUE_REQUIRED, 'Database host.' );
		$this->addOption( 'db_name', null, InputOption::VALUE_REQUIRED, 'Database name.' );
		$this->addOption( 'db_user', null, InputOption::VALUE_REQUIRED, 'Database user.' );
		$this->addOption( 'db_password', null, InputOption::VALUE_REQUIRED, 'Database password.' );
	}

	/**
	 * Execute command
	 *
	 * @param  InputInterface  $input Console input
	 * @param  OutputInterface $output Console output
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		Log::instance()->setOutput( $output );
		WPSnapshotsLog::instance()->setOutput( $output );
		WPSnapshotsLog::instance()->setVerbosityOffset( 1 );

		if ( ! function_exists( 'mysqli_init' ) ) {
			Log::instance()->write( 'WPAssure requires the mysqli PHP extension is installed.', 0, 'error' );
			return;
		}

		$connection = Connection::instance()->connect();

		if ( \WPSnapshots\Utils\is_error( $connection ) ) {
			Log::instance()->write( 'Could not connect to WP Snapshots repository.', 0, 'error' );
			return;
		}

		$wp_directory = $input->getOption( 'wp_directory' );

		if ( ! $wp_directory ) {
			$wp_directory = Utils\get_wordpress_path();
		}

		if ( empty( $wp_directory ) ) {
			Log::instance()->write( 'This does not seem to be a WordPress installation. No wp-config.php found in directory tree.', 0, 'error' );
			return;
		}

		$suite_config_directory = $input->getOption( 'suite_config_directory' );

		$suite_config = Config::create( $suite_config_directory );

		if ( false === $suite_config ) {
			return;
		}

		$local = $input->getOption( 'local' );

		$snapshot_id = false;

		if ( empty( $local ) ) {
			$snapshot_id = $input->getOption( 'snapshot_id' );

			if ( empty( $snapshot_id ) && ! empty( $suite_config['snapshot_id'] ) ) {
				$snapshot_id = $suite_config['snapshot_id'];
			}
		}

		if ( ! empty( $snapshot_id ) ) {
			if ( ! \WPSnapshots\Utils\is_snapshot_cached( $snapshot_id ) ) {
				$snapshot = Snapshot::download( $snapshot_id );

				if ( ! is_a( $snapshot, '\WPSnapshots\Snapshot' ) ) {
					Log::instance()->write( 'Could not download snapshot. Does it exist?', 0, 'error' );
					return;
				}
			}
		} else {
			if ( empty( $local ) ) {
				Log::instance()->write( 'You must either provide --snapshot_id, have a snapshot ID in wpassure.json, or provide the --local parameter.', 0, 'error' );
				return;
			}

			Log::instance()->write( 'Creating snapshot...' );

			$snapshot = Snapshot::create(
				[
					'path'            => $wp_directory,
					'db_host'         => $input->getOption( 'db_host' ),
					'db_name'         => $input->getOption( 'db_name' ),
					'db_user'         => $input->getOption( 'db_user' ),
					'db_password'     => $input->getOption( 'db_password' ),
					'project'         => 'wpassure-' . str_replace( ' ', '-', trim( strtolower( $suite_config['name'] ) ) ),
					'description'     => 'WP Assure snapshot',
					'no_scrub'        => false,
					'exclude_uploads' => true,
				]
			);

			$snapshot_id = $snapshot->id;

			Log::instance()->write( 'Snapshot ID is ' . $snapshot_id, 1 );
		}

		Log::instance()->write( 'Creating environment...' );

		$environment = EnvironmentFactory::create( $snapshot_id, $suite_config );

		if ( ! $environment ) {
			exit;
		}

		Log::instance()->write( 'Running tests...' );

		$test_files = [];
		$test_dirs  = ! empty( $suite_config['tests'] ) && is_array( $suite_config['tests'] )
			? $suite_config['tests']
			: array( 'tests' . DIRECTORY_SEPARATOR . '*.php' );

		foreach ( $test_dirs as $test_path ) {
			$test_path = trim( $test_path );

			if ( 0 === strpos( $test_path, './' ) ) {
				$test_path = substr( $test_path, 2 );
			}

			foreach ( glob( $suite_config['path'] . $test_path ) as $test_file ) {
				$test_files[] = $test_file;
			}
		}

		$error      = false;
		$test_files = array_unique( $test_files );

		foreach ( $test_files as $test_file ) {
			$command = new PHPUnitCommand();

			if ( 0 !== $command->run( [ WPASSURE_DIR . '/vendor/bin/phpunit', $test_file ], false ) ) {
				$error = true;
			}
		}

		if ( $error ) {
			Log::instance()->write( 'Test(s) have failed.', 0, 'error' );
		} else {
			Log::instance()->write( 'Test(s) passed!', 0, 'success' );

			if ( $input->getOption( 'save' ) ) {
				Log::instance()->write( 'Pushing snapshot to repository...', 1 );
				Log::instance()->write( 'Snapshot ID - ' . $snapshot_id, 1 );
				Log::instance()->write( 'Snapshot Project Slug - ' . $snapshot->meta['project'], 1 );

				if ( $snapshot->push() ) {
					Log::instance()->write( 'Snapshot ID saved to wpassure.json', 0, 'success' );

					$suite_config['snapshot_id'] = $snapshot_id;
					$suite_config->write();
				} else {
					Log::instance()->write( 'Could not push snapshot to repository.', 0, 'error' );
					$environment->destroy();
					return;
				}
			}
		}

		$environment->destroy();

		$output->writeln( 'Done.', 0, 'success' );
	}

}
