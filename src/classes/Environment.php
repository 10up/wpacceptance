<?php
/**
 * Create an environment
 *
 * @package wpassure
 */

namespace WPAssure;

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
use WPAssure\Utils as Utils;
use WPSnapshots\Snapshot;

/**
 * Create and manage the docker environment
 */
class Environment {

	/**
	 * Array of Docker containers
	 *
	 * @var array
	 */
	protected $containers = [];

	/**
	 * Network ID
	 *
	 * @var string
	 */
	protected $network_id;

	/**
	 * Docker instance
	 *
	 * @var Docker\Docker
	 */
	protected $docker;

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
	 * Selenium port
	 *
	 * @var int
	 */
	protected $selenium_port;

	/**
	 * MySQL port
	 *
	 * @var int
	 */
	protected $mysql_port;

	/**
	 * Suite config
	 *
	 * @var  array
	 */
	protected $suite_config;

	/**
	 * Preserve containers or not
	 *
	 * @var boolean
	 */
	protected $preserve_containers = false;

	/**
	 * Snapshot ID
	 *
	 * @var int
	 */
	protected $snapshot_id;

	/**
	 * Snapshot instance
	 *
	 * @var \WPSnapshots\Snapshot
	 */
	protected $snapshot;

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
	protected $current_mysql_db = 'wordpress_clean';

	/**
	 * Environment constructor
	 *
	 * @param  string  $snapshot_id WPSnapshot ID to load into environment
	 * @param  array   $suite_config Config array
	 * @param  boolean $preserve_containers Keep containers alive or not
	 */
	public function __construct( $snapshot_id, $suite_config, $preserve_containers = false ) {
		$this->network_id          = 'wpassure' . time();
		$this->docker              = Docker::create();
		$this->snapshot_id         = $snapshot_id;
		$this->suite_config        = $suite_config;
		$this->preserve_containers = $preserve_containers;
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

		$this->duplicateDB( 'wordpress_clean', $db_name );

		$this->current_mysql_db = $db_name;

		$db_number++;

		$this->mysql_client = new MySQL( $this->getMySQLCredentials(), $this->mysql_port, $this->snapshot->meta['table_prefix'] );

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

		$exec_command      = $this->docker->containerExec( 'mysql-' . $this->network_id, $exec_config );
		$exec_id           = $exec_command->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = $this->docker->execStart( $exec_id, $exec_start_config );

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

		$exit_code = $this->docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Could not duplicate MySQL database.', 0, 'error' );
			return false;
		}

		$command = 'sed -i -e "s/[\'\"]DB_NAME[\'\"],[ \t]*[\'\"].*[\'\"]/\'DB_NAME\', \'TEST\'/g" /var/www/html/wp-config.php';

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $command ] );

		$exec_command      = $this->docker->containerExec( 'wordpress-' . $this->network_id, $exec_config );
		$exec_id           = $exec_command->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = $this->docker->execStart( $exec_id, $exec_start_config );

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

		$exit_code = $this->docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Could not database in wp-config.php.', 0, 'error' );
			return false;
		}
	}

	/**
	 * Pull WP Snapshot into container
	 *
	 * @return  bool
	 */
	public function pullSnapshot() {
		/**
		 * Pulling snapshot
		 */

		Log::instance()->write( 'Pulling snapshot...', 1 );

		$this->snapshot = Snapshot::get( $this->snapshot_id );

		$site_mapping = [];

		Log::instance()->write( 'Snapshot site mapping:', 1 );

		foreach ( $this->snapshot->meta['sites'] as $site ) {
			$home_host = parse_url( $site['home_url'], PHP_URL_HOST );
			$site_host = parse_url( $site['site_url'], PHP_URL_HOST );

			$map = [
				'home_url' => str_replace( '//' . $home_host, '//wpassure.test:' . $this->wordpress_port, $site['home_url'] ),
				'site_url' => str_replace( '//' . $site_host, '//wpassure.test:' . $this->wordpress_port, $site['site_url'] ),
			];

			$site_mapping[] = $map;

			Log::instance()->write( 'Home URL: ' . $map['home_url'], 1 );
			Log::instance()->write( 'Site URL: ' . $map['site_url'], 1 );
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

		$command = '/root/.composer/vendor/bin/wpsnapshots pull ' . $this->snapshot_id . ' --confirm --config_db_name="' . $mysql_creds['DB_NAME'] . '" --config_db_user="' . $mysql_creds['DB_USER'] . '" --config_db_password="' . $mysql_creds['DB_PASSWORD'] . '" --config_db_host="' . $mysql_creds['DB_HOST'] . '" --confirm_wp_download --confirm_config_create --site_mapping="' . addslashes( json_encode( $site_mapping ) ) . '" ' . $verbose;

		Log::instance()->write( 'Running command:', 1 );
		Log::instance()->write( $command, 1 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $command ] );

		$exec_command      = $this->docker->containerExec( 'wordpress-' . $this->network_id, $exec_config );
		$exec_id           = $exec_command->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = $this->docker->execStart( $exec_id, $exec_start_config );

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

		$exit_code = $this->docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Failed to pull snapshot into WordPress container.', 0, 'error' );
			return false;
		}

		$this->mysql_client = new MySQL( $this->getMySQLCredentials(), $this->mysql_port, $this->snapshot->meta['table_prefix'] );

		/**
		 * Create duplicate WP DB to dirty
		 */
		$this->makeCleanDB();
		/**
		 * Determine where codebase is located in snapshot
		 */

		Log::instance()->write( 'Finding codebase in snapshot...', 1 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', 'find /var/www/html -name "wpassure.json" -not -path "/var/www/html/wp-includes/*" -not -path "/var/www/html/wp-admin/*"' ] );

		$exec_id           = $this->docker->containerExec( 'wordpress-' . $this->network_id, $exec_config )->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = $this->docker->execStart( $exec_id, $exec_start_config );

		$suite_config_files = [];

		$stream->onStdout(
			function( $stdout ) use ( &$suite_config_files ) {
				$suite_config_files[] = trim( $stdout );
			}
		);

		$stream->onStderr(
			function( $stderr ) {
				Log::instance()->write( $stderr, 1 );
			}
		);

		$stream->wait();

		$exit_code = $this->docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Failed to find codebase in snapshot.', 0, 'error' );
			return false;
		}

		$snapshot_repo_path = false;

		foreach ( $suite_config_files as $suite_config_file ) {
			$exec_config = new ContainersIdExecPostBody();
			$exec_config->setTty( true );
			$exec_config->setAttachStdout( true );
			$exec_config->setAttachStderr( true );
			$exec_config->setCmd( [ '/bin/sh', '-c', 'php -r "\$config = json_decode(file_get_contents(\"' . $suite_config_file . '\"), true); echo \$config[\"name\"];"' ] );

			$exec_id           = $this->docker->containerExec( 'wordpress-' . $this->network_id, $exec_config )->getId();
			$exec_start_config = new ExecIdStartPostBody();
			$exec_start_config->setDetach( false );

			$stream = $this->docker->execStart( $exec_id, $exec_start_config );

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

			if ( $suite_config_name === $this->suite_config['name'] ) {
				$snapshot_repo_path = dirname( $suite_config_file );
				break;
			}
		}

		if ( empty( $snapshot_repo_path ) ) {
			Log::instance()->write( 'Could not copy codebase files into snapshot. The snapshot must contain a codebase with a wpassure.json file.', 0, 'error' );
			return false;
		}

		/**
		 * Copy repo files into container
		 */

		Log::instance()->write( 'Copying codebase into container...', 1 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', 'cp -rf /root/repo/* ' . $snapshot_repo_path ] );

		$exec_id           = $this->docker->containerExec( 'wordpress-' . $this->network_id, $exec_config )->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = $this->docker->execStart( $exec_id, $exec_start_config );

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

		$exit_code = $this->docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Failed to copy codebase into WordPress container.', 0, 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Destroy environment
	 *
	 * @return  bool
	 */
	public function destroy() {
		if ( $this->preserve_containers ) {
			Log::instance()->write( 'Keeping containers alive...', 1 );
			return false;
		}

		Log::instance()->write( 'Destroying containers...', 1 );

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
				'name' => '10up/wpassure-wordpress',
				'tag'  => 'latest',
			],
			[
				'name' => 'selenium/standalone-chrome',
				'tag'  => '3.4.0',
			],
		];

		foreach ( $images as $image ) {
			$create_image = $this->docker->imageCreate(
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
	 * Get network ID
	 *
	 * @return string
	 */
	public function getNetworkId() {
		return $this->network_id;
	}

	/**
	 * Get open port
	 *
	 * @return int|boolean
	 */
	protected function getOpenPort() {
		static $used_ports = [];

		for ( $i = 1000; $i <= 9999; $i++ ) {
			if ( ! in_array( $i, $used_ports, true ) && Utils\is_open_port( '127.0.0.1', $i ) ) {
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
		$host_config->setNetworkMode( $this->network_id );
		$host_config->setBinds(
			[
				WPASSURE_DIR . '/docker/mysql:/etc/mysql/conf.d',
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

		$this->containers['mysql'] = $this->docker->containerCreate( $container_config, [ 'name' => 'mysql-' . $this->network_id ] );

		$this->mysql_stream = $this->docker->containerAttach(
			'mysql-' . $this->network_id,
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

		$host_config->setNetworkMode( $this->network_id );
		$host_config->setBinds(
			[
				\WPSnapshots\Utils\get_snapshot_directory() . $this->snapshot_id . ':/root/.wpsnapshots/' . $this->snapshot_id,
				\WPSnapshots\Utils\get_snapshot_directory() . 'config.json:/root/.wpsnapshots/config.json',
				$this->suite_config['path'] . ':/root/repo',
			]
		);

		$container_port_map           = new \ArrayObject();
		$container_port_map['80/tcp'] = new \stdClass();

		$container_config = new ContainersCreatePostBody();

		$container_config->setImage( '10up/wpassure-wordpress' );
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

		$this->containers['wordpress'] = $this->docker->containerCreate( $container_config, [ 'name' => 'wordpress-' . $this->network_id ] );

		/**
		 * Create selenium container
		 */

		$this->selenium_port = $this->getOpenPort();

		$host_config = new HostConfig();
		$host_config->setNetworkMode( $this->network_id );
		$host_config->setExtraHosts( [ 'wpassure.test:' . $this->gateway_ip ] );
		$host_config->setShmSize( ( 1000 * 1000 * 1000 ) ); // 1GB in bytes

		$container_config = new ContainersCreatePostBody();
		$container_config->setImage( 'selenium/standalone-chrome:3.4.0' );

		$port_binding = new PortBinding();
		$port_binding->setHostPort( $this->selenium_port );
		$port_binding->setHostIp( '0.0.0.0' );

		$host_port_map             = new \ArrayObject();
		$host_port_map['4444/tcp'] = [ $port_binding ];
		$host_config->setPortBindings( $host_port_map );

		$container_config->setHostConfig( $host_config );

		$container_create = new ContainerCreate( $container_config );

		$container_body = $container_create->getBody( $serializer, $stream_factory );

		Log::instance()->write( 'Container Request Body (Selenium):', 2 );
		Log::instance()->write( $container_body[1], 2 );

		$this->containers['selenium'] = $this->docker->containerCreate( $container_config, [ 'name' => 'selenium-' . $this->network_id ] );

		return true;
	}

	/**
	 * Wait for MySQL to be available by continually running mysqladmin command on WP container
	 *
	 * @return  bool
	 */
	public function waitForMySQL() {
		Log::instance()->write( 'Waiting for MySQL to start...', 1 );

		sleep( 1 );

		$mysql_creds = $this->getMySQLCredentials();

		for ( $i = 0; $i < 20; $i ++ ) {
			$exec_config = new ContainersIdExecPostBody();
			$exec_config->setTty( true );
			$exec_config->setAttachStdout( true );
			$exec_config->setAttachStderr( true );
			$exec_config->setCmd( [ '/bin/sh', '-c', 'mysqladmin ping -h"' . $mysql_creds['DB_HOST'] . '" -u ' . $mysql_creds['DB_USER'] . ' -p' . $mysql_creds['DB_PASSWORD'] ] );

			$exec_id           = $this->docker->containerExec( 'wordpress-' . $this->network_id, $exec_config )->getId();
			$exec_start_config = new ExecIdStartPostBody();
			$exec_start_config->setDetach( false );

			$stream = $this->docker->execStart( $exec_id, $exec_start_config );

			$stream->wait();

			$exit_code = $this->docker->execInspect( $exec_id )->getExitCode();

			if ( 0 === $exit_code ) {
				Log::instance()->write( 'MySQL connection available after ' . ( $i + 2 ) . ' seconds.', 2 );

				return true;
			}

			sleep( 1 );
		}

		Log::instance()->write( 'MySQL never became available.', 0, 'error' );

		return false;
	}

	/**
	 * Start containers
	 *
	 * @return  bool
	 */
	public function startContainers() {
		Log::instance()->write( 'Starting containers...', 1 );

		foreach ( $this->containers as $container ) {
			$response = $this->docker->containerStart( $container->getId() );
		}

		return $this->waitForMySQL();
	}

	/**
	 * Stop Docker containers
	 *
	 * @return  bool
	 */
	public function stopContainers() {
		Log::instance()->write( 'Stopping containers...', 1 );

		foreach ( $this->containers as $container ) {
			try {
				$this->docker->containerStop( $container->getId() );
			} catch ( \Exception $exception ) {
				// Proceed no matter what
			}
		}

		return true;
	}

	/**
	 * Delete Docker containers
	 *
	 * @return  bool
	 */
	public function deleteContainers() {
		Log::instance()->write( 'Deleting containers...', 1 );

		foreach ( $this->containers as $container ) {
			try {
				$this->docker->containerDelete( $container->getId() );
			} catch ( \Exception $exception ) {
				// Proceed no matter what
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
		$network_config->setName( $this->network_id );

		$this->network = $this->docker->networkCreate( $network_config );

		$network     = $this->docker->networkInspect( $this->network_id );
		$ipam_config = $network->getIPAM()->getConfig();

		$this->gateway_ip = $ipam_config[0]['Gateway'];

		Log::instance()->write( 'Network ID: ' . $this->network_id, 1 );
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

		$this->docker->networkDelete( $this->network_id );

		return true;
	}

	/**
	 * Get Selenium server URL
	 *
	 * @return string
	 */
	public function getSeleniumServerUrl() {
		return 'http://localhost:' . intval( $this->selenium_port ) . '/wd/hub';
	}

	/**
	 * Get WordPress homepage URL
	 *
	 * @return string
	 */
	public function getWpHomepageUrl() {
		return 'http://wpassure.test:' . intval( $this->wordpress_port );
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
	 * Get MySQL credentials to use in WordPress
	 *
	 * @return array
	 */
	public function getMySQLCredentials() {
		return [
			'DB_HOST'     => 'mysql-' . $this->network_id,
			'DB_NAME'     => $this->current_mysql_db,
			'DB_USER'     => 'root',
			'DB_PASSWORD' => 'password',
		];
	}

	/**
	 * Get MySQL client
	 *
	 * @return \WPAssure\MySQL
	 */
	public function getMySQLClient() {
		return $this->mysql_client;
	}

}
