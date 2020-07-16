<?php
/**
 * Run test suite command
 *
 * @package wpacceptance
 */

namespace WPAcceptance\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use PHPUnit\Framework\TestSuite as PHPUnitTestSuite;
use PHPUnit\TextUI\ResultPrinter as PHPUnitResultPrinter;

use WPAcceptance\EnvironmentFactory;
use WPAcceptance\Log;
use WPAcceptance\Utils;
use WPAcceptance\Config;
use WPAcceptance\GitLab;
use WPSnapshots\RepositoryManager;
use WPSnapshots\Snapshot;
use WPSnapshots\Log as WPSnapshotsLog;
use PHPUnit\Util\TestDox\CliTestDoxPrinter;

/**
 * Run test suite
 */
class Run extends Command {

	/**
	 * Setup up command
	 */
	protected function configure() {
		$this->setName( 'run' );
		$this->setDescription( 'Run a WP Acceptance test suite.' );

		$this->addArgument( 'suite_config_directory', InputArgument::OPTIONAL, 'Path to a directory that contains wpacceptance.json.' );

		$this->addOption( 'cache_environment', false, InputOption::VALUE_NONE, 'Cache environment for repeat use.' );
		$this->addOption( 'skip_environment_cache', false, InputOption::VALUE_NONE, "If a valid cached environment exists, don't use it. Don't cache the new environment." );
		$this->addOption( 'screenshot_on_failure', false, InputOption::VALUE_NONE, 'Take screenshot on test failure or error.' );

		$this->addOption( 'local', false, InputOption::VALUE_NONE, 'Run tests against local WordPress install.' );
		$this->addOption( 'skip_before_scripts', false, InputOption::VALUE_NONE, 'Do not run before scripts.' );
		$this->addOption( 'enforce_clean_db', false, InputOption::VALUE_NONE, 'Ensure each test has a clean version of the snapshot database.' );
		$this->addOption( 'save', false, InputOption::VALUE_NONE, 'If tests are successful, save snapshot ID to wpacceptance.json and push it to the remote repository.' );
		$this->addOption( 'force_save', false, InputOption::VALUE_NONE, 'No matter the outcome of the tests, save snapshot ID to wpacceptance.json and push it to the remote repository.' );
		$this->addOption( 'show_browser', false, InputOption::VALUE_NONE, 'Show the browser where testing is occuring.' );

		$this->addOption( 'snapshot_id', null, InputOption::VALUE_REQUIRED, 'WP Snapshot ID.' );
		$this->addOption( 'snapshot_name', null, InputOption::VALUE_REQUIRED, 'WP Snapshot Name.' );
		$this->addOption( 'environment_instructions_key', null, InputOption::VALUE_REQUIRED, 'Specify a set of environment instructions within an array of environment instructions. 0 would be the first set of instructions.' );
		$this->addOption( 'environment_id', null, InputOption::VALUE_REQUIRED, 'Manually set environment ID.' );
		$this->addOption( 'repository', null, InputOption::VALUE_REQUIRED, 'WP Snapshots repository to use.' );
		$this->addOption( 'wp_directory', null, InputOption::VALUE_REQUIRED, 'Path to WordPress wp-config.php directory.' );
		$this->addOption( 'db_host', null, InputOption::VALUE_REQUIRED, 'Database host.' );
		$this->addOption( 'db_name', null, InputOption::VALUE_REQUIRED, 'Database name.' );
		$this->addOption( 'db_user', null, InputOption::VALUE_REQUIRED, 'Database user.' );
		$this->addOption( 'db_password', null, InputOption::VALUE_REQUIRED, 'Database password.' );

		$this->addOption( 'mysql_wait_time', null, InputOption::VALUE_REQUIRED, 'Determine how long WP Acceptance should wait in seconds for MySQL to be available.' );
		$this->addOption( 'slowmo', null, InputOption::VALUE_REQUIRED, 'Slow down tests so errors can be more easily observed. Value provided in milliseconds. Needs to be used with --show_browser.' );
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

		putenv( 'NODE_PATH="' . WPACCEPTANCE_DIR . '/node_modules"' );
		$_ENV['NODE_PATH'] = WPACCEPTANCE_DIR . '/node_modules';

		exec( 'cd ' . escapeshellarg( WPACCEPTANCE_DIR ) . ' && npm install' );

		if ( ! function_exists( 'mysqli_init' ) ) {
			Log::instance()->write( 'WP Acceptance requires the mysqli PHP extension is installed.', 0, 'error' );
			return 3;
		}

		if ( GitLab::get()->isGitLab() ) {
			Log::instance()->write( 'Running WP Acceptance in GitLab.', 1 );
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
			if ( ! empty( $repository_name ) ) {
				Log::instance()->write( 'Could not setup WP Snapshots repository.', 0, 'error' );

				return 2;
			}
		}

		$suite_config['repository'] = ( ! empty( $repository ) ) ? $repository->getName() : '';

		$suite_config['show_browser'] = $input->getOption( 'show_browser' );

		$suite_config['slowmo'] = (int) $input->getOption( 'slowmo' );

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

		// Prepare environment instructions
		if ( ! empty( $suite_config['environment_instructions'] ) && is_string( $suite_config['environment_instructions'][0] ) ) {
			$suite_config['environment_instructions'] = [
				$suite_config['environment_instructions'],
			];
		}

		// Override with command line snapshot id if one exists
		if ( empty( $local ) ) {
			if ( empty( $suite_config['environment_instructions'] ) ) {
				$option_snapshot_name = $input->getOption( 'snapshot_name' );

				if ( ! empty( $option_snapshot_name ) && ! empty( $suite_config['snapshots'] ) ) {
					$snapshot_match = array_filter( $snapshots, function( $snapshot ) {
						return $snapshot['snapshot_name'] === $option_snapshot_name;
					} );

					if ( ! empty( $snapshot_match ) ) {
						$suite_config['snapshot_id'] = $snapshot_match['snapshot_id'];
					}
				}

				$option_snapshot_id = $input->getOption( 'snapshot_id' );

				if ( ! empty( $option_snapshot_id ) ) {
					$suite_config['snapshot_id'] = $option_snapshot_id;
				}
			} else {
				$option_environment_instructions_key = $input->getOption( 'environment_instructions_key' );

				if ( null !== $option_environment_instructions_key ) {
					$suite_config['environment_instructions'] = [
						$suite_config['environment_instructions'][ (int) $option_environment_instructions_key ],
					];
				}
			}
		}

		// Add snapshot_id to snapshots
		if ( ! empty( $suite_config['snapshot_id'] ) ) {
			if ( ! empty( $suite_config['snapshots'] ) ) {
				$new_snapshots = $suite_config['snapshots'];
				$snapshot_ids  = array_column( $suite_config['snapshots'], 'snapshot_id' );

				$snapshot_id_in_snapshots = in_array( $suite_config['snapshot_id'], $snapshot_ids, true );

				// Add the snapshot_id if not already in the array.
				if ( ! $snapshot_id_in_snapshots ) {
					$new_snapshots[] = [
						'snapshot_id'   => $suite_config['snapshot_id'],
						'snapshot_name' => 'Snapshot from ' . $suite_config['snapshot_id'],
					];
				}
			} else {
				$new_snapshots[] = [
					'snapshot_id'   => $suite_config['snapshot_id'],
					'snapshot_name' => 'Snapshot from ' . $suite_config['snapshot_id'],
				];
			}

			$suite_config['snapshots'] = $new_snapshots;
		}

		if ( empty( $local ) ) {
			if ( empty( $suite_config['snapshots'] ) && empty( $suite_config['environment_instructions'] ) ) {
				Log::instance()->write( 'You must either have environment isntructions in wpacceptance.json, have a snapshot ID or snapshots in wpacceptance.json, provide --snapshot_id, or provide the --local parameter.', 0, 'error' );

				return 3;
			}

			if ( empty( $suite_config['environment_instructions'] ) ) {
				foreach ( $suite_config['snapshots'] as $snapshot_array ) {
					$snapshot = Snapshot::get( $snapshot_array['snapshot_id'], $repository->getName() );

					if ( ! is_a( $snapshot, '\WPSnapshots\Snapshot' ) ) {
						Log::instance()->write( 'Could not download or find cached snapshot ' . $snapshot_array['snapshot_id'] . '. Does it exist?', 0, 'error' );
						return 2;
					}
				}
			}
		} else {
			Log::instance()->write( 'Creating snapshot...' );

			$snapshot = Snapshot::create(
				[
					'path'        => $wp_directory,
					'repository'  => $suite_config['repository'],
					'db_host'     => $input->getOption( 'db_host' ),
					'db_name'     => $input->getOption( 'db_name' ),
					'db_user'     => $input->getOption( 'db_user' ),
					'db_password' => $input->getOption( 'db_password' ),
					'project'     => 'wpacceptance-' . str_replace( ' ', '-', trim( strtolower( $suite_config['name'] ) ) ),
					'description' => 'WP Acceptance snapshot',
					'no_scrub'    => false,
					'exclude'     => [
						'vendor',
						'node_modules',
						'bower_components',
						'object-cache.php', // Don't even try object caching
					],
				]
			);

			if ( ! is_a( $snapshot, '\WPSnapshots\Snapshot' ) ) {
				Log::instance()->write( 'Could not create snapshot.', 0, 'error' );
				return 2;
			}

			$suite_config['snapshots'] = [
				[
					'snapshot_id' => $snapshot->id,
				],
			];

			Log::instance()->write( 'Snapshot ID is ' . $snapshot->id, 1 );
		}

		Log::instance()->write( 'Creating environment...' );

		$skip_environment_cache = ( ! empty( $local ) ) ? true : $input->getOption( 'skip_environment_cache' );

		$environment_id = $input->getOption( 'environment_id' );

		if ( empty( $environment_id ) && GitLab::get()->isGitLab() ) {
			$environment_id = GitLab::get()->getPipelineId();
		}

		$environment = EnvironmentFactory::create( $suite_config, $input->getOption( 'cache_environment' ), $skip_environment_cache, $environment_id, $input->getOption( 'mysql_wait_time' ) );

		if ( ! $environment ) {
			return 2;
		}

		Log::instance()->write( 'Running tests...' );

		$test_execution = 0;

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

		$this->suite = new PHPUnitTestSuite();

		foreach ( $test_files as $test_file ) {
			if ( empty( $filter_test_files ) || in_array( basename( $test_file ), $filter_test_files, true ) ) {
				$this->suite->addTestFile( $test_file );
			}
		}

		$this->suite_args = array();

		$colors = $input->getOption( 'colors' );

		$this->suite_args['colors'] = $colors ?: PHPUnitResultPrinter::COLOR_AUTO;

		if ( ! empty( $filter_tests ) ) {
			$this->suite_args['filter'] = $filter_tests;
		}

		$this->suite_args['testdox'] = true;

		if ( class_exists( CliTestDoxPrinter::class ) ) {
			$this->suite_args['printer'] = CliTestDoxPrinter::class;
		}

		if ( ! empty( $suite_config['snapshots'] ) ) {
			foreach ( $suite_config['snapshots'] as $snapshot_array ) {
				if ( ! $environment->setupWordPressEnvironment( $snapshot_array['snapshot_id'], 'snapshot' ) ) {
					Log::instance()->write( 'Could not setup WordPress environment.', 0, 'error' );

					$test_execution = 1;

					continue;
				}
				Log::instance()->write( sprintf( 'Running tests for %s.', $snapshot_array['snapshot_name'] ), 0, 'notice' );

				$result = $this->runTests( $suite_config, $input, $output );

				if ( $result ) {
					if ( $input->getOption( 'local' ) ) {
						if ( ( $result && $input->getOption( 'save' ) ) || $input->getOption( 'force_save' ) ) {
							$snapshot = Snapshot::get( $snapshot_array['snapshot_id'] );

							Log::instance()->write( 'Pushing snapshot to repository...', 1 );
							Log::instance()->write( 'Snapshot ID - ' . $snapshot_array['snapshot_id'], 1 );
							Log::instance()->write( 'Snapshot Project Slug - ' . $snapshot->meta['project'], 1 );

							if ( $snapshot->push() ) {
								Log::instance()->write( 'Snapshot saved to wpacceptance.json', 0, 'success' );

								$new_snapshots = [];

								if ( ! empty( $suite_config['snapshots'] ) ) {
									$new_snapshots = $suite_config['snapshots'];
								}

								$new_snapshots[] = [
									'snapshot_id' => $suite_config['snapshot_id'],
								];

								$suite_config['snapshots'] = $new_snapshots;

								$suite_config->write();
							} else {
								Log::instance()->write( 'Could not push snapshot to repository.', 0, 'error' );
								$environment->destroy();

								return 4;
							}
						}
					}
				}

				if ( ! $result ) {
					Log::instance()->write( 'Done with errors.', 0, 'error' );

					$test_execution = 1;
				} else {
					Log::instance()->write( 'Done.', 0, 'success' );
				}
			}
		} else {
			foreach ( $suite_config['environment_instructions'] as $environment_instructions ) {
				if ( ! $environment->setupWordPressEnvironment( implode( "\n", $environment_instructions ), 'environment_instructions' ) ) {
					Log::instance()->write( 'Could not setup WordPress environment.', 0, 'error' );

					$test_execution = 1;

					continue;
				}

				$result = $this->runTests( $suite_config, $input, $output );

				if ( ! $result ) {
					Log::instance()->write( 'Done with errors.', 0, 'error' );

					$test_execution = 1;
				} else {
					Log::instance()->write( 'Done.', 0, 'success' );
				}
			}
		}

		// If we are running more than one environment, output a final message on status of all environments
		if ( ( ! empty( $suite_config['environment_instructions'] ) && 1 < count( $suite_config['environment_instructions'] ) ) || ( ! empty( $suite_config['snapshots'] ) && 1 < count( $suite_config['snapshots'] ) ) ) {
			if ( 0 !== $test_execution ) {
				$output->writeln( 'All tests finished. Errors occurred.', 0, 'error' );
			} else {
				$output->writeln( 'All tests finished successful.', 0, 'success' );
			}
		}

		return $test_execution;
	}

	/**
	 * Run test suite with current environment
	 *
	 * @return boolean
	 */
	protected function runTests() {
		$runner      = new \PHPUnit\TextUI\TestRunner();
		$test_result = $runner->doRun( $this->suite, $this->suite_args, false );

		return $test_result->wasSuccessful();
	}

}
