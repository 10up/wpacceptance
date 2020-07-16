<?php
/**
 * Create an environment
 *
 * @package wpacceptance
 */

namespace WPAcceptance;

use Docker\API\Model\NetworkCreate;
use Docker\API\Model\NetworksCreatePostBody;
use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\ContainersCreatePostBodyNetworkingConfig;
use Docker\API\Model\ContainersIdExecPostBody;
use Docker\API\Model\ExecIdStartPostBody;
use Docker\API\Model\HostConfig;
use Docker\API\Model\PortBinding;
use Docker\API\Model\BuildInfo;
use Docker\API\Endpoint\ContainerCreate;
use Docker\Context\Context;
use Docker\Docker;
use GrantLucas\PortFinder;
use WPAcceptance\Utils as Utils;
use WPSnapshots\Snapshot;
use WPInstructions;

/**
 * Create and manage the docker environment
 */
class Environment {

	/**
	 * Array of Docker containers
	 *
	 * @var array
	 */
	protected $containers = [
		'mysql',
		'wordpress',
	];

	/**
	 * Main domain for use with snapshot. Parsed out of snapshot data
	 *
	 * @var string
	 */
	protected $main_domain;

	/**
	 * Prepared raw instructions
	 *
	 * @var string
	 */
	protected $raw_instructions;

	/**
	 * Environment ID
	 *
	 * @var string
	 */
	protected $environment_id;

	/**
	 * This key represents the current snapshot/environment instruction set by the environment.
	 *
	 * @var string
	 */
	protected $current_environment_key;

	/**
	 * WordPress port
	 *
	 * @var int
	 */
	protected $wordpress_port;

	/**
	 * Gateway IP
	 *
	 * @var string
	 */
	protected $gateway_ip;

	/**
	 * MySQL port
	 *
	 * @var int
	 */
	protected $mysql_port;

	/**
	 * Suite config
	 *
	 * @var Config
	 */
	protected $suite_config;

	/**
	 * Preserve containers or not
	 *
	 * @var boolean
	 */
	protected $cache_environment = false;

	/**
	 * If a valid cached environment exists, don't use it. Don't cache the new environment.
	 *
	 * @var boolean
	 */
	protected $skip_environment_cache = false;

	/**
	 * Snapshot instance
	 *
	 * @var \WPSnapshots\Snapshot
	 */
	protected $snapshot;

	/**
	 * Instructions instance
	 *
	 * @var WPInstructions\Instructions
	 */
	public $instructions;

	/**
	 * MySQL client instance
	 *
	 * @var MySQL
	 */
	protected $mysql_client;

	/**
	 * Current MySQL db to use
	 *
	 * @var string
	 */
	protected $current_mysql_db;

	/**
	 * Path to wpacceptance.json directory in container
	 *
	 * @var string
	 */
	protected $container_project_path;

	/**
	 * Sites referenced by WordPress
	 *
	 * @var array
	 */
	protected $sites = [];

	/**
	 * Sites prepared for wpsnapshot pull command
	 *
	 * @var array
	 */
	protected $site_mapping = [];

	/**
	 * Whether we are in gitlab or not
	 *
	 * @var bool
	 */
	public $gitlab = false;

	/**
	 * How long should we wait for MySQL to be available
	 *
	 * @var  int
	 */
	protected $mysql_wait_time = 30;

	/**
	 * Host file manager
	 *
	 * @var HostFile
	 */
	protected $host_file;

	/**
	 * True if WP setup is happening
	 *
	 * @var boolean
	 */
	protected $wp_setup_in_process = false;

	/**
	 * Environment constructor
	 *
	 * @param  Config  $suite_config Config array
	 * @param  boolean $cache_environment Cache environment for later or not
	 * @param  boolean $skip_environment_cache If a valid cached environment exists, don't use it. Don't cache the new environment.
	 * @param  string  $environment_id Allow for manual environment ID override
	 * @param  int     $mysql_wait_time How long should we wait for MySQL to become available (seconds)
	 */
	public function __construct( $suite_config = null, $cache_environment = false, $skip_environment_cache = false, $environment_id = null, $mysql_wait_time = null ) {
		$this->suite_config           = $suite_config;
		$this->cache_environment      = $cache_environment;
		$this->skip_environment_cache = $skip_environment_cache;

		if ( ! empty( $mysql_wait_time ) ) {
			$this->mysql_wait_time = $mysql_wait_time;
		}

		// If we are skipping cache just get a semi random hash for the id so collisions dont occur
		$id = ( $skip_environment_cache ) ? md5( time() . '' . rand( 0, 10000 ) ) : self::generateEnvironmentId( $suite_config );

		$this->environment_id = ( ! empty( $environment_id ) ) ? $environment_id . '-wpa' : $id . '-wpa';
	}

	/**
	 * Setup host file adding entries if needed
	 *
	 * @return  boolean
	 */
	public function setupHosts() {
		Log::instance()->write( 'Setting up host(s)...', 1 );

		$this->host_file = new HostFile();

		$hosts = [];

		$host_line = $this->gateway_ip . ' ';

		foreach ( $this->sites as $site ) {
			$home_host = parse_url( $site['home_url'], PHP_URL_HOST );
			$site_host = parse_url( $site['site_url'], PHP_URL_HOST );

			$hosts[ $home_host ] = true;
			$hosts[ $site_host ] = true;
		}

		foreach ( $hosts as $host => $nothing ) {
			$host_line .= ' ' . $host;
		}

		$this->host_file->add( $this->getLocalIP(), array_keys( $hosts ) );

		Log::instance()->write( 'Hosts insert: ' . $host_line, 2 );

		$command = "echo '" . $host_line . "' >> /etc/hosts";

		Log::instance()->write( 'Running `' . $command . '` on wordpress', 2 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/bash', '-c', $command ] );

		$exec_id           = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config )->getId();
		$exec_start_config = new ExecIdStartPostBody();

		$exec_start_config->setDetach( false );
		$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

		$stream->onStdout(
			function( $stdout ) {
				Log::instance()->write( $stdout, 1 );
			}
		);

		$stream->onStderr(
			function( $stderr ) {
				Log::instance()->write( $stderr, 1 );
			}
		);

		$stream->wait();

		$exit_code = EnvironmentFactory::$docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Failed to setup hosts in wordpress container.', 0, 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Get local IP. Different for gitlab
	 *
	 * @return string
	 */
	public function getLocalIP() {
		return ( GitLab::get()->isGitLab() ) ? $this->getGatewayIP() : '127.0.0.1';
	}

	/**
	 * Get repo root in WP container. Different for gitlab
	 *
	 * @return string
	 */
	public function getWPContainerRepoRoot() {
		return ( GitLab::get()->isGitLab() ) ? '/gitlab/' . GitLab::get()->getProjectDirectory() : '/root/repo';
	}

	/**
	 * Attempt to populate environment from cache
	 *
	 * @return bool
	 */
	public function populateEnvironmentFromCache() {
		if ( $this->skip_environment_cache ) {
			Log::instance()->write( 'Skipping environment cache look up.', 1 );
			return false;
		}

		Log::instance()->write( 'Getting environment meta...', 1 );

		$command = 'cat /root/environment_meta.json';

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $command ] );

		try {
			$exec_command      = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config );
			$exec_id           = $exec_command->getId();
			$exec_start_config = new ExecIdStartPostBody();
			$exec_start_config->setDetach( false );

			$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );
		} catch ( \Exception $e ) {
			Log::instance()->write( 'Environment NOT found in cache.', 1 );
			return false;
		}

		Log::instance()->write( 'Environment found in cache.' );

		$environment_meta = '';

		$stream->onStdout(
			function( $stdout ) use ( &$environment_meta ) {
				$environment_meta = $stdout;
			}
		);

		$stream->wait();

		$environment_meta = json_decode( $environment_meta, true );

		$this->wordpress_port          = $environment_meta['wordpress_port'];
		$this->mysql_port              = $environment_meta['mysql_port'];
		$this->gateway_ip              = $environment_meta['gateway_ip'];
		$this->current_environment_key = $environment_meta['current_environment_key'];

		return true;
	}

	/**
	 * Drop all WP databases
	 *
	 * @return boolean
	 */
	public function dropDatabases() {
		Log::instance()->write( 'Dropping MySQL databases...', 1 );

		$command = 'mysql --password=password -e "show databases" -s | egrep "^wordpress" |  xargs -I "@@" mysql --password=password -e "DROP DATABASE @@"';

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $command ] );

		$exec_command      = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-mysql', $exec_config );
		$exec_id           = $exec_command->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

		$stream->onStdout(
			function( $stdout ) {
				Log::instance()->write( $stdout, 2 );
			}
		);

		$stream->onStderr(
			function( $stderr ) {
				Log::instance()->write( $stderr, 2 );
			}
		);

		$stream->wait();

		$exit_code = EnvironmentFactory::$docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Could not MySQL drop databases.', 0, 'error' );
			return false;
		}

		Log::instance()->write( 'Creating wordpress_clean database...', 1 );

		$command = 'mysql --password=password -e "create database wordpress_clean"';

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $command ] );

		$exec_command      = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-mysql', $exec_config );
		$exec_id           = $exec_command->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

		$stream->onStdout(
			function( $stdout ) {
				Log::instance()->write( $stdout, 2 );
			}
		);

		$stream->onStderr(
			function( $stderr ) {
				Log::instance()->write( $stderr, 2 );
			}
		);

		$stream->wait();

		$exit_code = EnvironmentFactory::$docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Could not create wordpress_clean database.', 0, 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Clone the clean WordPress DB into another DB. Set the new db name as the current DB
	 *
	 * @return string
	 */
	public function makeCleanDB() {
		static $db_number = 0;

		$db_name = 'wordpress';

		if ( 0 < $db_number ) {
			$db_name .= $db_number;
		}

		Log::instance()->write( 'Setting up clean MySQL database: ' . $db_name, 1 );

		$this->duplicateDB( 'wordpress_clean', $db_name );

		$this->current_mysql_db = $db_name;

		$db_number++;

		/**
		 * Insert new db name into wp-config.php
		 */
		$command = 'sed -i -e "s/[\'\"]DB_NAME[\'\"],[ \t]*[\'\"].*[\'\"]/\'DB_NAME\', \'' . $db_name . '\'/g" /var/www/html/wp-config.php';

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $command ] );

		$exec_command      = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config );
		$exec_id           = $exec_command->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

		$stream->onStdout(
			function( $stdout ) {
				Log::instance()->write( $stdout, 2 );
			}
		);

		$stream->onStderr(
			function( $stderr ) {
				Log::instance()->write( $stderr, 2 );
			}
		);

		$stream->wait();

		$exit_code = EnvironmentFactory::$docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Could not modify database in wp-config.php.', 0, 'error' );
			return false;
		}

		$table_prefix = ( ! empty( $this->suite_config['snapshots'] ) ) ? $this->snapshot->meta['table_prefix'] : 'wp_';

		$this->mysql_client = new MySQL( $this->getMySQLCredentials(), $this->getLocalIP(), $this->mysql_port, $table_prefix );

		return $this->current_mysql_db;
	}

	/**
	 * Duplicate a MySQL DB
	 *
	 * @param  string $original Original db name
	 * @param  string $copy     New DB name
	 */
	protected function duplicateDB( $original, $copy ) {
		Log::instance()->write( 'Duplicating MySQL database...', 1 );

		$command = 'echo "create database if not exists ' . $copy . '" | mysql --password=password && mysqldump ' . $original . ' --password=password | mysql ' . $copy . ' --password=password';

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $command ] );

		$exec_command      = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-mysql', $exec_config );
		$exec_id           = $exec_command->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

		$stream->onStdout(
			function( $stdout ) {
				Log::instance()->write( $stdout, 2 );
			}
		);

		$stream->onStderr(
			function( $stderr ) {
				Log::instance()->write( $stderr, 2 );
			}
		);

		$stream->wait();

		$exit_code = EnvironmentFactory::$docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Could not duplicate MySQL database.', 0, 'error' );
			return false;
		}
	}

	/**
	 * Run before scripts
	 *
	 * @return bool
	 */
	public function runBeforeScripts() {
		if ( empty( $this->suite_config['before_scripts'] ) ) {
			return true;
		}

		if ( ! empty( $this->suite_config['skip_before_scripts'] ) ) {
			return true;
		}

		foreach ( $this->suite_config['before_scripts'] as $script ) {
			Log::instance()->write( 'Running script: ' . $script, 1 );

			$command = 'cd ' . $this->container_project_path . ' && ' . $script;

			$exec_config = new ContainersIdExecPostBody();
			$exec_config->setTty( true );
			$exec_config->setAttachStdout( true );
			$exec_config->setAttachStderr( true );
			$exec_config->setCmd( [ '/bin/bash', '-c', $command ] );

			$exec_command      = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config );
			$exec_id           = $exec_command->getId();
			$exec_start_config = new ExecIdStartPostBody();
			$exec_start_config->setDetach( false );

			$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

			$stream->onStdout(
				function( $stdout ) {
					Log::instance()->write( $stdout, 2 );
				}
			);

			$stream->onStderr(
				function( $stderr ) {
					Log::instance()->write( $stderr, 2 );
				}
			);

			$stream->wait();

			$exit_code = EnvironmentFactory::$docker->execInspect( $exec_id )->getExitCode();

			if ( 0 !== $exit_code ) {
				Log::instance()->write( 'Before script returned a non-zero exit code: ' . $script, 0, 'warning' );
			}
		}

		return true;
	}

	/**
	 * Remove WordPress install
	 *
	 * @return boolean
	 */
	public function removeWordPressInstall() {
		Log::instance()->write( 'Removing WordPress install...', 1 );

		$command = 'cd /var/www/html && rm -rf *';

		Log::instance()->write( 'Running command: ' . $command, 2 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $command ] );

		$exec_command      = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config );
		$exec_id           = $exec_command->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

		$stream->onStdout(
			function( $stdout ) {
				Log::instance()->write( $stdout, 1 );
			}
		);

		$stream->onStderr(
			function( $stderr ) {
				Log::instance()->write( $stderr, 1 );
			}
		);

		$stream->wait();

		$exit_code = EnvironmentFactory::$docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Failed to remove WordPress install.', 0, 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Destroy WP environment in between test executions
	 */
	public function destroyWordPressEnvironment() {
		// First drop DBs
		$this->dropDatabases();

		// Remove an existing WP install if it's there
		$this->removeWordPressInstall();
	}

	/**
	 * Setup WP environment via snapshot or instructions
	 *
	 * @param string $snapshot_id_or_environment_instructions Either snapshot ID or instructions
	 * @param  string $environment_type Either snapshot or environment_instructions
	 * @return boolean
	 */
	public function setupWordPressEnvironment( $snapshot_id_or_environment_instructions, string $environment_type ) {
		Log::instance()->write( 'Setting up WordPress environment...', 1 );

		$this->wp_setup_in_process = true;

		$this->snapshot     = null;
		$this->instructions = null;

		$this->current_mysql_db = 'wordpress_clean';

		if ( 'snapshot' === $environment_type ) {
			Log::instance()->write( 'Setting up WordPress test environment from snapshot.' );

			$new_environment_key = $snapshot_id_or_environment_instructions;

			if ( ! $this->prepareSnapshotEnvironment( $snapshot_id_or_environment_instructions ) ) {
				$this->wp_setup_in_process = false;

				return false;
			}

			// We don't need to pull the snapshot if it's already loaded in the environment
			if ( $new_environment_key !== $this->current_environment_key ) {
				$this->destroyWordPressEnvironment();

				if ( ! $this->pullSnapshot( $snapshot_id_or_environment_instructions ) ) {
					$this->wp_setup_in_process = false;

					return false;
				}
			} else {
				Log::instance()->write( 'WordPress environment found in cache.', 1 );
			}

			$this->current_environment_key = $new_environment_key;

			if ( ! $this->insertProject() ) {
				$this->wp_setup_in_process = false;

				return false;
			}
		} elseif ( 'environment_instructions' === $environment_type ) {
			Log::instance()->write( 'Setting up WordPress test environment from instructions.' );

			$new_environment_key = preg_replace( '#[\n\r\s]+#s', '', $snapshot_id_or_environment_instructions );

			if ( $new_environment_key !== $this->current_environment_key ) {
				$this->destroyWordPressEnvironment();
			}

			if ( ! $this->insertProject() ) {
				$this->wp_setup_in_process = false;

				return false;
			}

			if ( ! $this->prepareInstructionsEnvironment( $snapshot_id_or_environment_instructions ) ) {
				$this->wp_setup_in_process = false;

				return false;
			}

			// We don't need to run the instructions if it's already loaded in the environment
			if ( $new_environment_key !== $this->current_environment_key ) {
				if ( ! $this->executeInstructions() ) {
					$this->wp_setup_in_process = false;

					return false;
				}
			} else {
				Log::instance()->write( 'WordPress environment found in cache.', 1 );
			}

			$this->current_environment_key = $new_environment_key;
		} else {
			Log::instance()->write( 'No environment instructions or snapshot.', 0, 'error' );

			$this->wp_setup_in_process = false;

			return false;
		}

		$this->wp_setup_in_process = false;

		if ( ! $this->updateFilePermissions() ) {
			return false;
		}

		if ( ! $this->setupHosts() ) {
			return false;
		}

		if ( ! $this->setupMySQL() ) {
			return false;
		}

		if ( ! $this->runBeforeScripts() ) {
			return false;
		}

		if ( ! $this->writeMetaToWPContainer() ) {
			return false;
		}

		return true;
	}

	/**
	 * Prepare instructions environment
	 *
	 * @param  string $raw_instructions Raw instructions
	 * @return boolean|string
	 */
	protected function prepareInstructionsEnvironment( $raw_instructions ) {
		Log::instance()->write( 'Preparing instructions environment...', 1 );

		WPInstructions\Instruction::registerInstructionType( new WPInstructions\InstructionTypes\InstallWordPress() );

		$this->raw_instructions = $raw_instructions;
		$this->instructions     = new WPInstructions\Instructions( $this->raw_instructions );

		$this->instructions->prepare();

		$instruction_array = $this->instructions->getInstructions();

		if ( empty( $instruction_array ) ) {
			Log::instance()->write( 'No valid environment instructions.', 0, 'error' );

			return false;
		}

		$instruction_array[0]->prepare();

		$options = $instruction_array[0]->getPreparedOptions();

		if ( 'install wordpress' !== $instruction_array[0]->getAction() ) {
			Log::instance()->write( 'Your first environment instruction must be installing WordPress.', 0, 'error' );

			return false;
		}

		if ( empty( $options['site url'] ) && ! empty( $options['home url'] ) ) {
			$options['site url'] = $options['home url'];
		}

		if ( ! empty( $options['site url'] ) && empty( $options['home url'] ) ) {
			$options['home url'] = $options['site url'];
		}

		if ( empty( $options['home url'] ) || empty( $options['site url'] ) ) {
			Log::instance()->write( 'Your install WordPress instruction must set a home url and site url. Try using wpacceptance.test', 0, 'error' );

			return false;
		}

		$replaced_hosts = [];

		$home_host = parse_url( $options['home url'], PHP_URL_HOST );
		$site_host = parse_url( $options['site url'], PHP_URL_HOST );

		$this->raw_instructions = preg_replace( '#https?://' . $home_host . '#i', 'http://' . $home_host . ':' . $this->wordpress_port, $this->raw_instructions );
		$replaced_hosts[] = $home_host;

		if ( ! in_array( $site_host, $replaced_hosts, true ) ) {
			$this->raw_instructions = preg_replace( '#https?://' . $site_host . '#i', 'http://' . $site_host . ':' . $this->wordpress_port, $this->raw_instructions );

			$replaced_hosts[] = $site_host;
		}

		$this->sites = [
			[
				'home_url'    => preg_replace( '#^https?://' . $home_host . '(.*)$#i', 'http://' . $home_host . ':' . $this->wordpress_port . '$1', $options['home url'] ),
				'site_url'    => preg_replace( '#^https?://' . $site_host . '(.*)$#i', 'http://' . $site_host . ':' . $this->wordpress_port . '$1', $options['site url'] ),
				'blog_id'     => 1,
				'main_domain' => true,
			],
		];

		foreach ( $instruction_array as $instruction ) {
			if ( 'add site' === $instruction->getAction() ) {
				$options = $instruction_array[0]->getPreparedOptions();

				if ( empty( $options['site url'] ) && ! empty( $options['home url'] ) ) {
					$options['site url'] = $options['home url'];
				}

				if ( ! empty( $options['site url'] ) && empty( $options['home url'] ) ) {
					$options['home url'] = $options['site url'];
				}

				$home_host = parse_url( $options['home url'], PHP_URL_HOST );
				$site_host = parse_url( $options['site url'], PHP_URL_HOST );

				if ( ! in_array( $home_host, $replaced_hosts, true ) ) {
					$this->raw_instructions = preg_replace( '#https?://' . $home_host . '#i', 'http://' . $home_host . ':' . $this->wordpress_port, $this->raw_instructions );

					$replaced_hosts[] = $home_host;
				}

				if ( ! in_array( $site_host, $replaced_hosts, true ) ) {
					$this->raw_instructions = preg_replace( '#https?://' . $site_host . '#i', 'http://' . $site_host . ':' . $this->wordpress_port, $this->raw_instructions );

					$replaced_hosts[] = $site_host;
				}

				$this->sites[] = [
					'home_url'    => preg_replace( '#^https?://' . $home_host . '(.*)$#i', 'http://' . $home_host . ':' . $this->wordpress_port . '$1', $options['home url'] ),
					'site_url'    => preg_replace( '#^https?://' . $site_host . '(.*)$#i', 'http://' . $site_host . ':' . $this->wordpress_port . '$1', $options['site url'] ),
					'blog_id'     => count( $this->sites ) + 1,
					'main_domain' => false,
				];
			}
		}

		return true;
	}

	/**
	 * Create WP environment from instructions
	 *
	 * @return boolean
	 */
	protected function executeInstructions() {
		Log::instance()->write( 'Saving instructions to container...', 1 );

		$command = 'rm -f /var/www/html/WPInstructions && touch /var/www/html/WPInstructions && echo "' . addslashes( $this->raw_instructions ) . '" >> /var/www/html/WPInstructions';

		Log::instance()->write( $command, 2 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $command ] );

		$exec_command      = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config );
		$exec_id           = $exec_command->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

		$stream->onStdout(
			function( $stdout ) {
				Log::instance()->write( $stdout, 1 );
			}
		);

		$stream->onStderr(
			function( $stderr ) {
				Log::instance()->write( $stderr, 1 );
			}
		);

		$stream = $stream->wait();

		Log::instance()->write( 'Running instructions in container...', 1 );

		$mysql_creds = $this->getMySQLCredentials();

		$verbose = '';

		if ( 1 === Log::instance()->getVerbosity() ) {
			$verbose = '-v';
		} elseif ( 2 === Log::instance()->getVerbosity() ) {
			$verbose = '-vv';
		} elseif ( 3 === Log::instance()->getVerbosity() ) {
			$verbose = '-vvv';
		}

		$command = '/root/.composer/vendor/bin/wpinstructions run --config_db_name="' . $mysql_creds['DB_NAME'] . '" --config_db_user="' . $mysql_creds['DB_USER'] . '" --config_db_password="' . $mysql_creds['DB_PASSWORD'] . '" --config_db_host="' . $mysql_creds['DB_HOST'] . '" ' . $verbose;

		Log::instance()->write( 'Running command:', 1 );
		Log::instance()->write( $command, 1 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $command ] );

		$exec_command      = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config );
		$exec_id           = $exec_command->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

		$stream->onStdout(
			function( $stdout ) {
				Log::instance()->write( $stdout, 1 );
			}
		);

		$stream->onStderr(
			function( $stderr ) {
				Log::instance()->write( $stderr, 1 );
			}
		);

		$stream->wait();

		$exit_code = EnvironmentFactory::$docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Failed to run environment instructions in WordPress container.', 0, 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Prepare snapshot environment
	 *
	 * @param  string $snapshot_id Snapshot id
	 * @return boolean
	 */
	protected function prepareSnapshotEnvironment( $snapshot_id ) {
		Log::instance()->write( 'Setting up snapshot environment...', 1 );

		$this->snapshot = Snapshot::getLocal( $snapshot_id, $this->suite_config['repository'] );

		if ( empty( $this->snapshot ) || empty( $this->snapshot->meta ) || empty( $this->snapshot->meta['sites'] ) ) {
			Log::instance()->write( 'Snapshot invalid.', 0, 'error' );

			return false;
		}

		Log::instance()->write( 'WordPress version is ' . $this->snapshot->meta['wp_version'], 1 );

		$this->site_mapping = [];

		Log::instance()->write( 'Snapshot site mapping:', 1 );

		foreach ( $this->snapshot->meta['sites'] as $site ) {
			$home_host = parse_url( $site['home_url'], PHP_URL_HOST );
			$site_host = parse_url( $site['site_url'], PHP_URL_HOST );

			$map = [
				'home_url' => preg_replace( '#^https?://' . $home_host . '(.*)$#i', 'http://' . $home_host . ':' . $this->wordpress_port . '$1', $site['home_url'] ),
				'site_url' => preg_replace( '#^https?://' . $site_host . '(.*)$#i', 'http://' . $site_host . ':' . $this->wordpress_port . '$1', $site['site_url'] ),
			];

			if ( ! empty( $site['blog_id'] ) ) {
				$map['blog_id'] = (int) $site['blog_id'];

				Log::instance()->write( 'Blog ID: ' . $map['blog_id'], 1 );
			}

			$this->site_mapping[] = $map;

			Log::instance()->write( 'Home URL: ' . $map['home_url'], 1 );
			Log::instance()->write( 'Site URL: ' . $map['site_url'], 1 );

			if ( ! empty( $this->snapshot->meta['multisite'] ) ) {
				// We have to do this for backwards compat where wpsnapshots didnt set domain_current_site
				if ( ! empty( $this->snapshot->meta['blog_id_current_site'] ) ) {
					if ( (int) $this->snapshot->meta['blog_id_current_site'] === (int) $site['blog_id'] ) {
						$map['main_domain'] = true;

						$this->main_domain = $home_host;
					}
				} else {
					// Just set first site as main domain if we don't have blog_id_current_site
					if ( empty( $this->main_domain ) ) {
						$map['main_domain'] = true;

						$this->main_domain = $home_host;
					}
				}
			}

			$this->sites[] = $map;
		}

		return true;
	}

	/**
	 * Pull WP Snapshot into container
	 *
	 * @param  string $snapshot_id Snapshot ID to use
	 * @return  bool
	 */
	protected function pullSnapshot( $snapshot_id ) {
		/**
		 * Pulling snapshot
		 */

		Log::instance()->write( 'Pulling snapshot ' . $snapshot_id . '...', 1 );

		$main_domain_param = '';

		if ( ! empty( $this->snapshot->meta['multisite'] ) ) {
			if ( ! empty( $this->snapshot->meta['domain_current_site'] ) ) {
				$main_domain_param = ' --main_domain="' . $this->snapshot->meta['domain_current_site'] . ':' . $this->wordpress_port . '" ';
			} else {
				// We have to do this for backwards compat where wpsnapshots didnt set domain_current_site
				$main_domain_param = ' --main_domain="' . $this->main_domain . ':' . $this->wordpress_port . '" ';
			}
		}

		$mysql_creds = $this->getMySQLCredentials();

		$verbose = '';

		if ( 1 === Log::instance()->getVerbosity() ) {
			$verbose = '-v';
		} elseif ( 2 === Log::instance()->getVerbosity() ) {
			$verbose = '-vv';
		} elseif ( 3 === Log::instance()->getVerbosity() ) {
			$verbose = '-vvv';
		}

		$command = '/root/.composer/vendor/bin/wpsnapshots pull ' . $snapshot_id . ' --repository=' . $this->suite_config['repository'] . ' --confirm --confirm_wp_version_change --confirm_ms_constant_update --config_db_name="' . $mysql_creds['DB_NAME'] . '" --config_db_user="' . $mysql_creds['DB_USER'] . '" --config_db_password="' . $mysql_creds['DB_PASSWORD'] . '" --config_db_host="' . $mysql_creds['DB_HOST'] . '" --confirm_wp_download --confirm_config_create ' . $main_domain_param . ' --site_mapping="' . addslashes( json_encode( $this->site_mapping ) ) . '" ' . $verbose;

		Log::instance()->write( 'Running command:', 1 );
		Log::instance()->write( $command, 1 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $command ] );

		$exec_command      = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config );
		$exec_id           = $exec_command->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

		$stream->onStdout(
			function( $stdout ) {
				Log::instance()->write( $stdout, 1 );
			}
		);

		$stream->onStderr(
			function( $stderr ) {
				Log::instance()->write( $stderr, 1 );
			}
		);

		$stream->wait();

		$exit_code = EnvironmentFactory::$docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Failed to pull snapshot into WordPress container.', 0, 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Setup MySQL
	 *
	 * @return boolean
	 */
	public function setupMySQL() {
		Log::instance()->write( 'Setting up MySQL...', 1 );

		$table_prefix = ( ! empty( $this->snapshot ) ) ? $this->snapshot->meta['table_prefix'] : 'wp_';

		$this->mysql_client = new MySQL( $this->getMySQLCredentials(), $this->getLocalIP(), $this->mysql_port, $table_prefix );

		// Enable general logging
		$this->mysql_client->query( 'SET global general_log = 1' );
		$this->mysql_client->query( 'SET global log_output = "table"' );

		/**
		 * Create duplicate WP DB to dirty
		 */
		if ( empty( $this->suite_config['disable_clean_db'] ) ) {
			$this->makeCleanDB();
		}

		return true;
	}

	/**
	 * Find wpacceptance.json in WP container
	 *
	 * @return string|boolean
	 */
	protected function findWPAcceptanceFile() {
		/**
		 * Determine where codebase is located in snapshot
		 */
		Log::instance()->write( 'Finding wpacceptance.json in container...', 1 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', 'find /var/www/html -name "wpacceptance.json" -not -path "/var/www/html/wp-includes/*" -not -path "/var/www/html/wp-admin/*"' ] );

		$exec_id           = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config )->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

		$suite_config_files = [];

		$stream->onStdout(
			function( $stdout ) use ( &$suite_config_files ) {
				$files = preg_split( '/\R/', $stdout );

				foreach ( $files as $file ) {
					$file = trim( $file );

					if ( ! empty( $file ) ) {
						$suite_config_files[] = $file;
					}
				}
			}
		);

		$stream->onStderr(
			function( $stderr ) {
				Log::instance()->write( $stderr, 1 );
			}
		);

		$stream->wait();

		$exit_code = EnvironmentFactory::$docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			return false;
		}

		/**
		 * Finding matching wpacceptance project names
		 */
		foreach ( $suite_config_files as $suite_config_file ) {
			$exec_config = new ContainersIdExecPostBody();
			$exec_config->setTty( true );
			$exec_config->setAttachStdout( true );
			$exec_config->setAttachStderr( true );
			$exec_config->setCmd( [ '/bin/sh', '-c', 'php -r "\$config = json_decode(file_get_contents(\"' . $suite_config_file . '\"), true); echo \$config[\"name\"];"' ] );

			$exec_id           = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config )->getId();
			$exec_start_config = new ExecIdStartPostBody();
			$exec_start_config->setDetach( false );

			$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

			$suite_config_files = [];
			$suite_config_name  = '';

			$stream->onStdout(
				function( $name ) use ( &$suite_config_name ) {
					$suite_config_name = $name;
				}
			);

			$stream->onStderr(
				function( $stderr ) {
					Log::instance()->write( $stderr, 1 );
				}
			);

			$stream->wait();

			if ( trim( $suite_config_name ) === trim( $this->suite_config['name'] ) ) {
				return Utils\trailingslash( dirname( $suite_config_file ) );
			}
		}

		return false;
	}

	/**
	 * Mount project to container
	 *
	 * @return boolean
	 */
	public function insertProject() {
		if ( ! empty( $this->snapshot ) ) {
			$this->container_project_path = $this->findWPAcceptanceFile();

			if ( empty( $this->container_project_path ) ) {
				Log::instance()->write( 'Could not copy codebase files into snapshot. The snapshot must contain a codebase with a wpacceptance.json file.', 0, 'error' );

				return false;
			}
		} else {
			if ( empty( $this->suite_config['project_path'] ) ) {
				Log::instance()->write( 'No project path set in wpacceptance.json.', 0, 'error' );

				return false;
			}

			$this->container_project_path = preg_replace( '#^/?%WP_ROOT%/?(.*)$#i', '/var/www/html/$1', $this->suite_config['project_path'] );
		}

		/**
		 * Copy project files into container
		 */

		Log::instance()->write( 'Copying codebase into container...', 1 );
		Log::instance()->write( 'Container project path: ' . $this->container_project_path, 2 );

		$excludes = '';

		if ( ! empty( $this->suite_config['exclude'] ) ) {
			foreach ( $this->suite_config['exclude'] as $exclude ) {
				$exclude = preg_replace( '#^\.?/(.*)$#i', '$1', $exclude );

				$excludes .= '--exclude="' . $exclude . '" ';
			}
		}

		$rsync_command = 'mkdir -p ' . $this->container_project_path . ' && rsync -a -I --exclude=".git" ' . $excludes . ' ' . $this->getWPContainerRepoRoot() . '/ ' . $this->container_project_path;

		Log::instance()->write( $rsync_command, 2 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $rsync_command ] );

		$exec_id           = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config )->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

		$stream->onStdout(
			function( $stdout ) {
				Log::instance()->write( $stdout, 1 );
			}
		);

		$stream->onStderr(
			function( $stderr ) {
				Log::instance()->write( $stderr, 1 );
			}
		);

		$stream->wait();

		$exit_code = EnvironmentFactory::$docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Failed to copy codebase into WordPress container.', 0, 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Update file permissions
	 *
	 * @return boolean
	 */
	public function updateFilePermissions() {
		Log::instance()->write( 'Updating file permissions...', 1 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', 'chmod -R 0777 /var/www/html/wp-content/' ] );

		$exec_id           = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config )->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

		$suite_config_files = [];

		$stream->onStdout(
			function( $stdout ) {
				Log::instance()->write( $stdout, 1 );
			}
		);

		$stream->onStderr(
			function( $stderr ) {
				Log::instance()->write( $stderr, 1 );
			}
		);

		$stream->wait();

		$exit_code = EnvironmentFactory::$docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Failed to update file permissions.', 0, 'error' );
		}

		return true;
	}

	/**
	 * Destroy environment
	 *
	 * @return  bool
	 */
	public function destroy() {
		if ( $this->cache_environment && ! $this->skip_environment_cache && ! $this->wp_setup_in_process ) {
			Log::instance()->write( 'Caching environment.' );
			return false;
		}

		Log::instance()->write( 'Destroying environment...', 1 );

		$this->stopContainers();
		$this->deleteContainers();
		$this->deleteNetwork();

		return true;
	}

	/**
	 * Download Docker images
	 *
	 * @return  bool
	 */
	public function downloadImages() {
		Log::instance()->write( 'Downloading Docker images...', 1 );

		$images = [
			[
				'name' => 'mysql',
				'tag'  => '5.7',
			],
			[
				'name' => '10up/wpacceptance-wordpress',
				'tag'  => 'latest',
			],
		];

		foreach ( $images as $image ) {
			$create_image = EnvironmentFactory::$docker->imageCreate(
				'',
				[
					'fromImage' => $image['name'],
					'tag'       => $image['tag'],
				]
			);

			$create_image->wait();
		}

		return true;
	}

	/**
	 * Get environment ID
	 *
	 * @return string
	 */
	public function getEnvironmentId() {
		return $this->environment_id;
	}

	/**
	 * Get open port
	 *
	 * @return int|boolean
	 */
	protected function getOpenPort() {
		static $used_ports = [];

		for ( $i = 1000; $i <= 9999; $i++ ) {
			if ( ! in_array( $i, $used_ports, true ) && Utils\is_open_port( $this->getLocalIP(), $i ) ) {
				$used_ports[] = $i;

				return $i;
			}
		}

		return false;
	}

	/**
	 * Create Docker containers
	 *
	 * @return  bool
	 */
	public function createContainers() {
		Log::instance()->write( 'Creating containers...' );

		$stream_factory = \Http\Discovery\StreamFactoryDiscovery::find();
		$serializer     = new \Symfony\Component\Serializer\Serializer( \Docker\API\Normalizer\NormalizerFactory::create(), [ new \Symfony\Component\Serializer\Encoder\JsonEncoder( new \Symfony\Component\Serializer\Encoder\JsonEncode(), new \Symfony\Component\Serializer\Encoder\JsonDecode() ) ] );

		/**
		 * Create MySQL
		 */

		$this->mysql_port = $this->getOpenPort();

		$host_config = new HostConfig();
		$host_config->setNetworkMode( $this->environment_id );
		$host_config->setBinds(
			[
				WPACCEPTANCE_DIR . '/docker/mysql:/etc/mysql/conf.d',
			]
		);

		$port_binding = new PortBinding();
		$port_binding->setHostPort( $this->mysql_port );
		$port_binding->setHostIp( '0.0.0.0' );

		$host_port_map             = new \ArrayObject();
		$host_port_map['3306/tcp'] = [ $port_binding ];

		$host_config->setPortBindings( $host_port_map );

		$container_config = new ContainersCreatePostBody();
		$container_config->setImage( 'mysql:5.7' );
		$container_config->setAttachStdin( true );
		$container_config->setAttachStdout( true );
		$container_config->setAttachStderr( true );
		$container_config->setEnv(
			[
				'MYSQL_ROOT_PASSWORD=password',
				'MYSQL_DATABASE=wordpress_clean',
			]
		);
		$container_config->setHostConfig( $host_config );

		$container_create = new ContainerCreate( $container_config );

		$container_body = $container_create->getBody( $serializer, $stream_factory );

		Log::instance()->write( 'Container Request Body (MySQL):', 2 );
		Log::instance()->write( $container_body[1], 2 );

		EnvironmentFactory::$docker->containerCreate( $container_config, [ 'name' => $this->environment_id . '-mysql' ] );

		$this->mysql_stream = EnvironmentFactory::$docker->containerAttach(
			$this->environment_id . '-mysql',
			[
				'stream' => true,
				'stdin'  => true,
				'stdout' => true,
				'stderr' => true,
			]
		);

		/**
		 * Create WP container
		 */

		$this->wordpress_port = $this->getOpenPort();

		$host_config = new HostConfig();

		$host_config->setNetworkMode( $this->environment_id );

		$container_config = new ContainersCreatePostBody();

		if ( GitLab::get()->isGitLab() ) {
			$container_config->setEnv( [ 'WPSNAPSHOTS_DIR=/gitlab/.wpsnapshots/' ] );

			$binds = [
				GitLab::get()->getVolumeName() . ':/gitlab:cached',
			];
		} else {
			$binds = [
				$this->suite_config['path'] . ':/root/repo:cached',
			];

			if ( ! empty( $this->suite_config['snapshots'] ) ) {
				foreach ( $this->suite_config['snapshots'] as $snap ) {
					$binds[] = \WPSnapshots\Utils\get_snapshot_directory() . $snap['snapshot_id'] . ':/root/.wpsnapshots/' . $snap['snapshot_id'] . ':cached';
				}

				$binds[] = \WPSnapshots\Utils\get_snapshot_directory() . 'config.json:/root/.wpsnapshots/config.json:cached';
			}
		}

		$host_config->setBinds( $binds );

		Log::instance()->write( 'Mapping ' . $this->suite_config['path'] . ' to ' . $this->getWPContainerRepoRoot(), 2 );

		$container_port_map           = new \ArrayObject();
		$container_port_map['80/tcp'] = new \stdClass();

		$container_config->setImage( '10up/wpacceptance-wordpress' );
		$container_config->setAttachStdin( true );
		$container_config->setAttachStdout( true );
		$container_config->setExposedPorts( $container_port_map );
		$container_config->setAttachStderr( true );
		$container_config->setTty( true );

		$port_binding = new PortBinding();
		$port_binding->setHostPort( $this->wordpress_port );
		$port_binding->setHostIp( '0.0.0.0' );

		$host_port_map           = new \ArrayObject();
		$host_port_map['80/tcp'] = [ $port_binding ];

		$host_config->setPortBindings( $host_port_map );
		$container_config->setHostConfig( $host_config );

		$container_create = new ContainerCreate( $container_config );

		$container_body = $container_create->getBody( $serializer, $stream_factory );

		Log::instance()->write( 'Container Request Body (WordPress):', 2 );
		Log::instance()->write( $container_body[1], 2 );

		EnvironmentFactory::$docker->containerCreate( $container_config, [ 'name' => $this->environment_id . '-wordpress' ] );

		return true;
	}

	/**
	 * Wait for MySQL to be available by continually running mysqladmin command on WP container
	 *
	 * @return  bool
	 */
	public function waitForMySQL() {
		Log::instance()->write( 'Waiting up to ' . $this->mysql_wait_time . ' seconds for MySQL to start...', 1 );

		sleep( 1 );

		$mysql_creds = $this->getMySQLCredentials();

		$exit_code = null;

		for ( $i = 0; $i < $this->mysql_wait_time; $i ++ ) {
			$exec_config = new ContainersIdExecPostBody();
			$exec_config->setTty( true );
			$exec_config->setAttachStdout( true );
			$exec_config->setAttachStderr( true );
			$exec_config->setCmd( [ '/bin/sh', '-c', 'mysqladmin ping -h"' . $mysql_creds['DB_HOST'] . '" -u ' . $mysql_creds['DB_USER'] . ' -p' . $mysql_creds['DB_PASSWORD'] ] );

			$exec_id           = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config )->getId();
			$exec_start_config = new ExecIdStartPostBody();
			$exec_start_config->setDetach( false );

			$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

			$stream->wait();

			$exit_code = EnvironmentFactory::$docker->execInspect( $exec_id )->getExitCode();

			if ( 0 === $exit_code ) {
				break;
			}

			sleep( 1 );
		}

		$logs = (string) EnvironmentFactory::$docker->containerLogs(
			$this->environment_id . '-mysql',
			[
				'stdout' => true,
				'stderr' => true,
				'follow' => false,
			],
			Docker::FETCH_RESPONSE
		)->getBody();

		Log::instance()->write( 'MySQL Logs:', 2 );
		Log::instance()->write( $logs, 2 );

		if ( 0 === $exit_code ) {
			Log::instance()->write( 'MySQL connection available after ' . ( $i + 2 ) . ' seconds.', 2 );

			return true;
		}

		Log::instance()->write( 'MySQL Host: ' . $mysql_creds['DB_HOST'], 2 );
		Log::instance()->write( 'MySQL DB User: ' . $mysql_creds['DB_USER'], 2 );
		Log::instance()->write( 'MySQL DB Password: ' . $mysql_creds['DB_PASSWORD'], 2 );

		Log::instance()->write( 'MySQL never became available.', 0, 'error' );

		return false;
	}

	/**
	 * Start containers
	 *
	 * @param  array $containers List of containers to start. Null means all of them.
	 * @return  bool
	 */
	public function startContainers( array $containers = null ) {
		if ( null === $containers ) {
			$containers = $this->containers;
		}

		Log::instance()->write( 'Starting containers...', 1 );

		foreach ( $containers as $container ) {
			$response = EnvironmentFactory::$docker->containerStart( $this->environment_id . '-' . $container );
		}

		return $this->waitForMySQL();
	}

	/**
	 * Stop Docker containers
	 *
	 * @param  array $containers List of containers to stop. Null means all of them
	 * @return  bool
	 */
	public function stopContainers( array $containers = null ) {
		Log::instance()->write( 'Stopping containers...', 1 );

		if ( null === $containers ) {
			$containers = $this->containers;
		}

		foreach ( $containers as $container ) {
			try {
				EnvironmentFactory::$docker->containerStop( $this->environment_id . '-' . $container );
			} catch ( \Exception $exception ) {
				Log::instance()->write( 'Could not stop container: ' . $this->environment_id . '-' . $container, 1 );
			}
		}

		return true;
	}

	/**
	 * Delete Docker containers
	 *
	 * @param  array $containers List of containers to delete. Null means all of them
	 * @return bool
	 */
	public function deleteContainers( array $containers = null ) {
		Log::instance()->write( 'Deleting containers...', 1 );

		if ( null === $containers ) {
			$containers = $this->containers;
		}

		foreach ( $containers as $container ) {
			try {
				EnvironmentFactory::$docker->containerDelete(
					$this->environment_id . '-' . $container,
					[
						'v'     => true,
						'force' => true,
					]
				);
			} catch ( \Exception $exception ) {
				Log::instance()->write( 'Could not delete container: ' . $this->environment_id . '-' . $container, 1 );
			}
		}

		return true;
	}

	/**
	 * Create Docker network
	 *
	 * @return  bool
	 */
	public function createNetwork() {
		Log::instance()->write( 'Creating network...', 1 );

		$network_config = new NetworksCreatePostBody();
		$network_config->setName( $this->environment_id );

		try {
			EnvironmentFactory::$docker->networkCreate( $network_config );
		} catch ( \Exception $e ) {
			Log::instance()->write( 'Could not create network.', 0, 'error' );

			return false;
		}

		$network = EnvironmentFactory::$docker->networkInspect( $this->environment_id );
		if ( empty( $network ) ) {
			Log::instance()->write( 'Could not create network. This network address might already exist. Try `docker network prune`.', 0, 'error' );

			return false;
		}

		$ipam_config = $network->getIPAM()->getConfig();

		$this->gateway_ip = $ipam_config[0]['Gateway'];

		Log::instance()->write( 'Environment ID: ' . $this->environment_id, 1 );
		Log::instance()->write( 'Gateway IP: ' . $this->gateway_ip, 1 );

		return true;
	}

	/**
	 * Delete Docker network
	 *
	 * @return  bool
	 */
	public function deleteNetwork() {
		Log::instance()->write( 'Deleting network...', 1 );

		try {
			EnvironmentFactory::$docker->networkDelete( $this->environment_id );
		} catch ( \Exception $exception ) {
			// Proceed no matter what
		}

		return true;
	}

	/**
	 * Get WordPress homepage URL.
	 *
	 * @param  mixed $id_or_url Pass in an ID or url to get the url of another blog on the
	 *                           network. Leaving blank gets the home URL for the main blog.
	 * @return string
	 */
	public function getWPHomeUrl( $id_or_url = '' ) {
		$url = '';

		foreach ( $this->sites as $site ) {
			// If we have no url, use the first one
			if ( empty( $url ) ) {
				$url = $site['home_url'];
			}

			if ( ! empty( $site['main_domain'] ) ) {
				$url = $site['home_url'];
			}

			// If an id or url is provided, always use this
			if ( ! empty( $id_or_url ) ) {
				if ( is_numeric( $id_or_url ) ) {
					if ( (int) $id_or_url === (int) $site['blog_id'] ) {
						$url = $site['home_url'];
					}
				} else {
					// Make sure everything has a trailing slash to populate `path`
					$param_url_parts = parse_url( rtrim( $id_or_url, '/' ) . '/' );
					$home_url_parts  = parse_url( rtrim( $site['home_url'], '/' ) . '/' );

					if ( $param_url_parts['host'] === $home_url_parts['host'] && $param_url_parts['path'] === $home_url_parts['path'] ) {
						$url = $site['home_url'];
					}
				}
			}
		}

		// Make sure we have a trailingslash
		$url_parts = parse_url( rtrim( $url, '/' ) . '/' );

		// Rebuild url
		$url = rtrim( $url_parts['scheme'] . '://' . $url_parts['host'] . ':' . intval( $this->wordpress_port ) . $url_parts['path'], '/' );

		return $url;
	}

	/**
	 * Get Gateway IP
	 *
	 * @return string
	 */
	public function getGatewayIP() {
		return $this->gateway_ip;
	}

	/**
	 * Get current snapshot
	 *
	 * @return \WPSnapshots\Snapshot
	 */
	public function getSnapshot() {
		return $this->snapshot;
	}

	/**
	 * Get current instructions
	 *
	 * @return \WPInstructions\Instructions
	 */
	public function getInstructions() {
		return $this->instructions;
	}

	/**
	 * Get MySQL credentials to use in WordPress
	 *
	 * @return array
	 */
	public function getMySQLCredentials() {
		return [
			'DB_HOST'     => $this->environment_id . '-mysql',
			'DB_NAME'     => $this->current_mysql_db,
			'DB_USER'     => 'root',
			'DB_PASSWORD' => 'password',
		];
	}

	/**
	 * Get MySQL client
	 *
	 * @return \WPAcceptance\MySQL
	 */
	public function getMySQLClient() {
		return $this->mysql_client;
	}

	/**
	 * Get suite config
	 *
	 * @return \WPAcceptance\Config
	 */
	public function getSuiteConfig() {
		return $this->suite_config;
	}

	/**
	 * Get meta data about environment
	 *
	 * @return array
	 */
	public function getEnvironmentMeta() {
		return [
			'wordpress_port'          => $this->wordpress_port,
			'mysql_port'              => $this->mysql_port,
			'environment_id'          => $this->environment_id,
			'gateway_ip'              => $this->gateway_ip,
			'current_environment_key' => $this->current_environment_key,
		];
	}

	/**
	 * Get WP container port
	 *
	 * @return int
	 */
	public function getWordPressPort() {
		return $this->wordpress_port;
	}

	/**
	 * Write meta data to WP container
	 */
	public function writeMetaToWPContainer() {
		Log::instance()->write( 'Saving environment meta data to container...', 1 );

		$command = 'rm -f /root/environment_meta.json && touch /root/environment_meta.json && echo "' . addslashes( json_encode( $this->getEnvironmentMeta(), JSON_UNESCAPED_SLASHES ) ) . '" >> /root/environment_meta.json';

		Log::instance()->write( $command, 2 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $command ] );

		$exec_command      = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config );
		$exec_id           = $exec_command->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );

		$stream->onStdout(
			function( $stdout ) {
				Log::instance()->write( $stdout, 1 );
			}
		);

		$stream->onStderr(
			function( $stderr ) {
				Log::instance()->write( $stderr, 1 );
			}
		);

		$stream = $stream->wait();

		return true;
	}

	/**
	 * WP CLI Runner
	 *
	 * @param string $command A WP CLI command.
	 * @return array
	 */
	public function wpCliRunner( $command ) {
		Log::instance()->write( 'Running wp command: ' . $command, 0 );

		$command = 'wp ' . $command . ' --allow-root';

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/bash', '-c', $command ] );

		$exec_command      = EnvironmentFactory::$docker->containerExec( $this->environment_id . '-wordpress', $exec_config );
		$exec_id           = $exec_command->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = EnvironmentFactory::$docker->execStart( $exec_id, $exec_start_config );
		$output = '';
		$error  = '';

		$stream->onStdout(
			function( $stdout ) use ( &$output ) {
				Log::instance()->write( $stdout, 1 );
				$output .= $stdout;
			}
		);

		$stream->onStderr(
			function( $stderr ) use ( &$error ) {
				Log::instance()->write( $stderr, 1 );
				$error .= $stderr;
			}
		);

		$stream->wait();

		$exit_code = EnvironmentFactory::$docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Before script returned a non-zero exit code: ' . $exit_code, 0, 'warning' );
		}

		$result = [
			'stdout' => $output,
			'stderr' => $error,
			'code'   => $exit_code,
		];

		return $result;
	}

	/**
	 * Generate an environment ID from config. This is a cache of critical suite config parameters
	 *
	 * @param  array $suite_config Suite config
	 * @return string
	 */
	public static function generateEnvironmentId( $suite_config ) {
		$string = 'name=' . $suite_config['name'] . ',path=' . $suite_config['path'] . ',project_path=' . $suite_config['project_path'];

		return md5( $string );
	}
}
