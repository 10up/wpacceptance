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

use PHPUnit\Framework\TestSuite as PHPUnitTestSuite;
use PHPUnit\TextUI\ResultPrinter as PHPUnitResultPrinter;

use WPAssure\EnvironmentFactory;
use WPAssure\Log;
use WPAssure\Utils;
use WPAssure\Config;
use WPAssure\GitLab;
use WPSnapshots\RepositoryManager;
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

		$this->addArgument( 'suite_config_directory', InputArgument::OPTIONAL, 'Path to a directory that contains wpassure.json.' );

		$this->addOption( 'cache_environment', false, InputOption::VALUE_NONE, 'Cache environment for repeat use.' );
		$this->addOption( 'screenshot_on_failure', false, InputOption::VALUE_NONE, 'Take screenshot on test failure or error.' );

		$this->addOption( 'local', false, InputOption::VALUE_NONE, 'Run tests against local WordPress install.' );
		$this->addOption( 'skip_before_scripts', false, InputOption::VALUE_NONE, 'Do not run before scripts.' );
		$this->addOption( 'enforce_clean_db', false, InputOption::VALUE_NONE, 'Ensure each test has a clean version of the snapshot database.' );
		$this->addOption( 'save', false, InputOption::VALUE_NONE, 'If tests are successful, save snapshot ID to wpassure.json and push it to the remote repository.' );
		$this->addOption( 'force_save', false, InputOption::VALUE_NONE, 'No matter the outcome of the tests, save snapshot ID to wpassure.json and push it to the remote repository.' );

		$this->addOption( 'snapshot_id', null, InputOption::VALUE_REQUIRED, 'WP Snapshot ID.' );
		$this->addOption( 'repository', null, InputOption::VALUE_REQUIRED, 'WP Snapshots repository to use.' );
		$this->addOption( 'wp_directory', null, InputOption::VALUE_REQUIRED, 'Path to WordPress wp-config.php directory.' );
		$this->addOption( 'db_host', null, InputOption::VALUE_REQUIRED, 'Database host.' );
		$this->addOption( 'db_name', null, InputOption::VALUE_REQUIRED, 'Database name.' );
		$this->addOption( 'db_user', null, InputOption::VALUE_REQUIRED, 'Database user.' );
		$this->addOption( 'db_password', null, InputOption::VALUE_REQUIRED, 'Database password.' );

		$this->addOption( 'filter_test_files', null, InputOption::VALUE_REQUIRED, 'Comma separate test files to execute. If used all other test files will be ignored.' );
		$this->addOption( 'filter_tests', null, InputOption::VALUE_REQUIRED, 'Filter tests to run. Is analagous to PHPUnit --filter.' );
		$this->addOption( 'colors', null, InputOption::VALUE_REQUIRED, 'Use colors in output ("never", "auto" or "always")' );
	}

	/**
	 * Execute command
	 *
	 * Exit codes:
	 * 0 -> All tests passed
	 * 1 -> Tests failed
	 * 2 -> Environment/snapshot issues
	 * 3 -> Config issues
	 * 4 -> Success but could not push snapshot to repo
	 *
	 * @param  InputInterface  $input Console input
	 * @param  OutputInterface $output Console output
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		Log::instance()->setOutput( $output );
		WPSnapshotsLog::instance()->setOutput( $output );
		WPSnapshotsLog::instance()->setVerbosityOffset( 1 );

		if ( ! function_exists( 'mysqli_init' ) ) {
			Log::instance()->write( 'WP Assure requires the mysqli PHP extension is installed.', 0, 'error' );
			return 3;
		}

		if ( GitLab::get()->isGitLab() ) {
			Log::instance()->write( 'Running WP Assure in GitLab.', 1 );
		}

		$suite_config_directory = $input->getArgument( 'suite_config_directory' );

		$suite_config = Config::create( $suite_config_directory );

		if ( false === $suite_config ) {
			return 3;
		}

		$repository_name = $input->getOption( 'repository' );

		if ( empty( $repository_name ) && ! empty( $suite_config['repository'] ) ) {
			$repository_name = $suite_config['repository'];
		}

		$repository = RepositoryManager::instance()->setup( $repository_name );

		if ( ! $repository ) {
			Log::instance()->write( 'Could not setup WP Snapshots repository.', 0, 'error' );

			return 2;
		}

		$suite_config['repository'] = $repository->getName();

		$local = $input->getOption( 'local' );

		if ( ! empty( $local ) ) {
			$wp_directory = $input->getOption( 'wp_directory' );

			if ( ! $wp_directory ) {
				$wp_directory = Utils\get_wordpress_path();
			}

			if ( empty( $wp_directory ) ) {
				Log::instance()->write( 'This does not seem to be a WordPress installation. No wp-config.php found in directory tree.', 0, 'error' );
				return 3;
			}
		}

		$enforce_clean_db = $input->getOption( 'enforce_clean_db' );

		if ( ! empty( $enforce_clean_db ) ) {
			$suite_config['enforce_clean_db'] = true;
		}

		if ( ! empty( $input->getOption( 'skip_before_scripts' ) ) ) {
			$suite_config['skip_before_scripts'] = true;
		}

		$screenshot_on_failure = $input->getOption( 'screenshot_on_failure' );

		if ( ! empty( $screenshot_on_failure ) ) {
			$suite_config['screenshot_on_failure'] = true;
		}

		if ( empty( $local ) ) {
			$option_snapshot_id = $input->getOption( 'snapshot_id' );

			if ( ! empty( $option_snapshot_id ) ) {
				$suite_config['snapshot_id'] = $option_snapshot_id;
			}
		}

		if ( ! empty( $suite_config['snapshot_id'] ) ) {
			if ( ! \WPSnapshots\Utils\is_snapshot_cached( $suite_config['snapshot_id'] ) ) {
				$snapshot = Snapshot::download( $suite_config['snapshot_id'], $repository->getName() );

				if ( ! is_a( $snapshot, '\WPSnapshots\Snapshot' ) ) {
					Log::instance()->write( 'Could not download snapshot. Does it exist?', 0, 'error' );
					return 2;
				}
			}
		} else {
			if ( empty( $local ) ) {
				Log::instance()->write( 'You must either provide --snapshot_id, have a snapshot ID in wpassure.json, or provide the --local parameter.', 0, 'error' );
				return 3;
			}

			Log::instance()->write( 'Creating snapshot...' );

			$snapshot = Snapshot::create(
				[
					'path'        => $wp_directory,
					'repository'  => $suite_config['repository'],
					'db_host'     => $input->getOption( 'db_host' ),
					'db_name'     => $input->getOption( 'db_name' ),
					'db_user'     => $input->getOption( 'db_user' ),
					'db_password' => $input->getOption( 'db_password' ),
					'project'     => 'wpassure-' . str_replace( ' ', '-', trim( strtolower( $suite_config['name'] ) ) ),
					'description' => 'WP Assure snapshot',
					'no_scrub'    => false,
					'exclude'     => [
						'vendor',
						'node_modules',
						'bower_components',
					],
				]
			);

			if ( ! is_a( $snapshot, '\WPSnapshots\Snapshot' ) ) {
				Log::instance()->write( 'Could not create snapshot.', 0, 'error' );
				return 2;
			}

			$suite_config['snapshot_id'] = $snapshot->id;

			Log::instance()->write( 'Snapshot ID is ' . $suite_config['snapshot_id'], 1 );
		}

		Log::instance()->write( 'Creating environment...' );

		$environment = EnvironmentFactory::create( $suite_config, $input->getOption( 'cache_environment' ) );

		if ( ! $environment ) {
			return 2;
		}

		Log::instance()->write( 'Running tests...' );

		$test_files = [];
		$test_dirs  = ! empty( $suite_config['tests'] ) && is_array( $suite_config['tests'] )
			? $suite_config['tests']
			: array( 'tests' . DIRECTORY_SEPARATOR . '*.php' );

		foreach ( $test_dirs as $test_path ) {
			$test_path = trim( $test_path );

			// Not absolute
			if ( ! preg_match( '#^/#', $test_path ) ) {
				$test_path = $suite_config['path'] . $test_path;
			}

			foreach ( glob( $test_path ) as $test_file ) {
				$test_files[] = $test_file;
			}
		}

		$error      = false;
		$test_files = array_unique( $test_files );

		$filter_test_files = $input->getOption( 'filter_test_files' );
		$filter_tests      = $input->getOption( 'filter_tests' );

		if ( ! empty( $filter_test_files ) ) {
			$filter_test_files = explode( ',', trim( $filter_test_files ) );
		}

		if ( ! empty( $suite_config['bootstrap'] ) ) {
			$bootstrap_path = $suite_config['bootstrap'];

			// Not absolute
			if ( ! preg_match( '#^/#', $bootstrap_path ) ) {
				$bootstrap_path = $suite_config['path'] . $bootstrap_path;
			}

			if ( file_exists( $bootstrap_path ) ) {
				include_once $bootstrap_path;
			} else {
				Log::instance()->write( 'Could not find bootstrap file at: ' . $bootstrap_path, 0, 'warning' );
			}
		}

		$suite = new PHPUnitTestSuite();

		foreach ( $test_files as $test_file ) {
			if ( empty( $filter_test_files ) || in_array( basename( $test_file ), $filter_test_files, true ) ) {
				$suite->addTestFile( $test_file );
			}
		}

		$suite_args = array();

		$colors = $input->getOption( 'colors' );

		$suite_args['colors'] = $colors ?: PHPUnitResultPrinter::COLOR_AUTO;

		if ( ! empty( $filter_tests ) ) {
			$suite_args['filter'] = $filter_tests;
		}

		$runner      = new \PHPUnit\TextUI\TestRunner();
		$test_result = $runner->doRun( $suite, $suite_args, false );

		$error = ! $test_result->wasSuccessful();

		if ( $local ) {
			if ( ( ! $error && $input->getOption( 'save' ) ) || $input->getOption( 'force_save' ) ) {
				Log::instance()->write( 'Pushing snapshot to repository...', 1 );
				Log::instance()->write( 'Snapshot ID - ' . $suite_config['snapshot_id'], 1 );
				Log::instance()->write( 'Snapshot Project Slug - ' . $snapshot->meta['project'], 1 );

				if ( $snapshot->push() ) {
					Log::instance()->write( 'Snapshot ID saved to wpassure.json', 0, 'success' );

					$suite_config['snapshot_id'] = $suite_config['snapshot_id'];
					$suite_config->write();
				} else {
					Log::instance()->write( 'Could not push snapshot to repository.', 0, 'error' );
					$environment->destroy();

					return 4;
				}
			}
		}

		if ( $error ) {
			$output->writeln( 'Done with errors.', 0, 'error' );
			return 1;
		} else {
			$output->writeln( 'Done.', 0, 'success' );
			return 0;
		}
	}

}
