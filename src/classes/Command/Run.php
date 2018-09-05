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

use WPAssure\Environment;
use WPAssure\Log;
use WPAssure\AcceptanceTester;
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

		$this->addOption( 'snapshot_id', null, InputOption::VALUE_REQUIRED, 'WP Snapshot ID.' );
		$this->addOption( 'path', null, InputOption::VALUE_REQUIRED, 'Path to WordPress wp-config.php directory.' );
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

		$connection = Connection::instance()->connect();

		if ( \WPSnapshots\Utils\is_error( $connection ) ) {
			Log::instance()->write( 'Could not connect to WP Snapshots repository.', 0, 'error' );
			return;
		}

		$path = $input->getOption( 'path' );

		if ( ! $path ) {
			$path = Utils\get_wordpress_path();
		}

		if ( empty( $path ) ) {
			Log::instance()->write( 'This does not seem to be a WordPress installation. No wp-config.php found in directory tree.', 0, 'error' );
			return;
		}

		$suite_config = Config::create();

		if ( false === $suite_config ) {
			return;
		}

		$local = $input->getOption( 'local' );

		$snapshot_id = false;

		if ( empty( $local ) ) {
			$snapshot_id = $input->getOption( 'snapshot_id' );

			if ( empty( $snapshot_id ) ) {
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
			Log::instance()->write( 'Creating snapshot...' );

			$snapshot = Snapshot::create(
				[
					'path'            => $path,
					'db_host'         => $input->getOption( 'db_host' ),
					'db_name'         => $input->getOption( 'db_name' ),
					'db_user'         => $input->getOption( 'db_user' ),
					'db_password'     => $input->getOption( 'db_password' ),
					'project'         => 'WPAssure Snapshot',
					'description'     => 'WPAssure project',
					'no_scrub'        => false,
					'exclude_uploads' => true,
				]
			);

			$snapshot_id = $snapshot->id;

			Log::instance()->write( 'Snapshot ID is ' . $snapshot_id, 1 );
		}

		Log::instance()->write( 'Creating environment...' );

		$environment = Environment::create( $snapshot_id, $suite_config );

		if ( ! $environment ) {
			exit;
		}

		Log::instance()->write( 'Running tests...' );

		$test_files = [];

		foreach ( $suite_config['tests'] as $test_path ) {
			foreach ( glob( $test_path ) as $test_file ) {
				$test_files[] = $test_file;
			}
		}

		$test_files = array_unique( $test_files );

		$error = false;

		foreach ( $test_files as $test_file ) {
			$command = new PHPUnitCommand();
			if ( 0 !== $command->run( [ WPASSURE_DIR . '/vendor/bin/phpunit', $test_file ], false ) ) {
				$error = true;
			}
		}

		if ( $error ) {
			$output->writeln( 'Test(s) have failed.', 0, 'error' );
		} else {
			$output->writeln( 'Test(s) passed!', 0, 'success' );

			if ( $input->getOption( 'save' ) ) {
				$output->writeln( 'Snapshot ID saved to wpassure.json', 0, 'success' );

				$suite_config['snapshot_id'] = $snapshot_id;
				$suite_config->write();
			}
		}

		$environment->destroy();

		$output->writeln( 'Done.', 0, 'success' );
	}

}
