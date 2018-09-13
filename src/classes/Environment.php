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
	protected $_containers = [];

	/**
	 * Network ID
	 *
	 * @var string
	 */
	protected $_network_id;

	/**
	 * Docker instance
	 *
	 * @var Docker\Docker
	 */
	protected $_docker;

	/**
	 * WordPress port
	 *
	 * @var int
	 */
	protected $_wordpress_port;

	/**
	 * Gateway IP
	 *
	 * @var string
	 */
	protected $_gateway_ip;

	/**
	 * Selenium port
	 *
	 * @var int
	 */
	protected $_selenium_port;

	/**
	 * MySQL port
	 *
	 * @var int
	 */
	protected $_mysql_port;

	/**
	 * Suite config
	 *
	 * @var  array
	 */
	protected $_suite_config;

	/**
	 * Preserve containers or not
	 *
	 * @var boolean
	 */
	protected $_preserve_containers = false;

	/**
	 * Snapshot ID
	 *
	 * @var int
	 */
	protected $_snapshot_id;

	/**
	 * Snapshot instance
	 *
	 * @var \WPSnapshots\Snapshot
	 */
	protected $_snapshot;

	/**
	 * MySQL client instance
	 *
	 * @var MySQL
	 */
	protected $mysql_client;

	/**
	 * Environment constructor
	 *
	 * @param  string  $snapshot_id WPSnapshot ID to load into environment
	 * @param  array   $suite_config Config array
	 * @param  boolean $preserve_containers Keep containers alive or not
	 */
	public function __construct( $snapshot_id, $suite_config, $preserve_containers = false ) {
		$this->_network_id          = 'wpassure' . time();
		$this->_docker              = Docker::create();
		$this->_snapshot_id         = $snapshot_id;
		$this->_suite_config        = $suite_config;
		$this->_preserve_containers = $preserve_containers;
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

		$this->_snapshot = Snapshot::get( $this->_snapshot_id );

		$site_mapping = [];

		Log::instance()->write( 'Snapshot site mapping:', 1 );

		foreach ( $this->_snapshot->meta['sites'] as $site ) {
			$home_host = parse_url( $site['home_url'], PHP_URL_HOST );
			$site_host = parse_url( $site['site_url'], PHP_URL_HOST );

			$map = [
				'home_url' => str_replace( '//' . $home_host, '//wpassure.test:' . $this->_wordpress_port, $site['home_url'] ),
				'site_url' => str_replace( '//' . $site_host, '//wpassure.test:' . $this->_wordpress_port, $site['site_url'] ),
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

		$command = '/root/.composer/vendor/bin/wpsnapshots pull ' . $this->_snapshot_id . ' --confirm --config_db_name="' . $mysql_creds['DB_NAME'] . '" --config_db_user="' . $mysql_creds['DB_USER'] . '" --config_db_password="' . $mysql_creds['DB_PASSWORD'] . '" --config_db_host="' . $mysql_creds['DB_HOST'] . '" --confirm_wp_download --confirm_config_create --site_mapping="' . addslashes( json_encode( $site_mapping ) ) . '" ' . $verbose;

		Log::instance()->write( 'Running command:', 1 );
		Log::instance()->write( $command, 1 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $command ] );

		$exec_command      = $this->_docker->containerExec( 'wordpress-' . $this->_network_id, $exec_config );
		$exec_id           = $exec_command->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = $this->_docker->execStart( $exec_id, $exec_start_config );

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

		$exit_code = $this->_docker->execInspect( $exec_id )->getExitCode();

		if ( 0 !== $exit_code ) {
			Log::instance()->write( 'Failed to pull snapshot into WordPress container.', 0, 'error' );
			return false;
		}

		/**
		 * Determine where codebase is located in snapshot
		 */

		Log::instance()->write( 'Finding codebase in snapshot...', 1 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', 'find /var/www/html -name "wpassure.json" -not -path "/var/www/html/wp-includes/*" -not -path "/var/www/html/wp-admin/*"' ] );

		$exec_id           = $this->_docker->containerExec( 'wordpress-' . $this->_network_id, $exec_config )->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = $this->_docker->execStart( $exec_id, $exec_start_config );

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

		$exit_code = $this->_docker->execInspect( $exec_id )->getExitCode();

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

			$exec_id           = $this->_docker->containerExec( 'wordpress-' . $this->_network_id, $exec_config )->getId();
			$exec_start_config = new ExecIdStartPostBody();
			$exec_start_config->setDetach( false );

			$stream = $this->_docker->execStart( $exec_id, $exec_start_config );

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

			if ( $suite_config_name === $this->_suite_config['name'] ) {
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

		$exec_id           = $this->_docker->containerExec( 'wordpress-' . $this->_network_id, $exec_config )->getId();
		$exec_start_config = new ExecIdStartPostBody();
		$exec_start_config->setDetach( false );

		$stream = $this->_docker->execStart( $exec_id, $exec_start_config );

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

		$exit_code = $this->_docker->execInspect( $exec_id )->getExitCode();

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
		if ( $this->_preserve_containers ) {
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
			$create_image = $this->_docker->imageCreate(
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
		return $this->_network_id;
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

		$streamFactory = \Http\Discovery\StreamFactoryDiscovery::find();
		$serializer    = new \Symfony\Component\Serializer\Serializer( \Docker\API\Normalizer\NormalizerFactory::create(), [ new \Symfony\Component\Serializer\Encoder\JsonEncoder( new \Symfony\Component\Serializer\Encoder\JsonEncode(), new \Symfony\Component\Serializer\Encoder\JsonDecode() ) ] );

		/**
		 * Create MySQL
		 */

		$this->_mysql_port = $this->getOpenPort();

		$host_config = new HostConfig();
		$host_config->setNetworkMode( $this->_network_id );

		$port_binding = new PortBinding();
		$port_binding->setHostPort( $this->_mysql_port );
		$port_binding->setHostIp( '0.0.0.0' );

		$host_port_map           = new \ArrayObject();
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
				'MYSQL_DATABASE=wordpress',
			]
		);
		$container_config->setHostConfig( $host_config );

		$container_create = new ContainerCreate( $container_config );

		$container_body = $container_create->getBody( $serializer, $streamFactory );

		Log::instance()->write( 'Container Request Body (MySQL):', 2 );
		Log::instance()->write( $container_body[1], 2 );

		$this->_containers['mysql'] = $this->_docker->containerCreate( $container_config, [ 'name' => 'mysql-' . $this->_network_id ] );

		$this->mysql_stream = $this->_docker->containerAttach(
			'mysql-' . $this->_network_id, [
				'stream' => true,
				'stdin'  => true,
				'stdout' => true,
				'stderr' => true,
			]
		);

		/**
		 * Create WP container
		 */

		$this->_wordpress_port = $this->getOpenPort();

		$host_config = new HostConfig();

		$host_config->setNetworkMode( $this->_network_id );
		$host_config->setBinds(
			[
				\WPSnapshots\Utils\get_snapshot_directory() . $this->_snapshot_id . ':/root/.wpsnapshots/' . $this->_snapshot_id,
				\WPSnapshots\Utils\get_snapshot_directory() . 'config.json:/root/.wpsnapshots/config.json',
				$this->_suite_config['path'] . ':/root/repo',
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
		$port_binding->setHostPort( $this->_wordpress_port );
		$port_binding->setHostIp( '0.0.0.0' );

		$host_port_map           = new \ArrayObject();
		$host_port_map['80/tcp'] = [ $port_binding ];

		$host_config->setPortBindings( $host_port_map );
		$container_config->setHostConfig( $host_config );

		$container_create = new ContainerCreate( $container_config );

		$container_body = $container_create->getBody( $serializer, $streamFactory );

		Log::instance()->write( 'Container Request Body (WordPress):', 2 );
		Log::instance()->write( $container_body[1], 2 );

		$this->_containers['wordpress'] = $this->_docker->containerCreate( $container_config, [ 'name' => 'wordpress-' . $this->_network_id ] );

		/**
		 * Create selenium container
		 */

		$this->_selenium_port = $this->getOpenPort();

		$host_config = new HostConfig();
		$host_config->setNetworkMode( $this->_network_id );
		$host_config->setExtraHosts( [ 'wpassure.test:' . $this->_gateway_ip ] );
		$host_config->setShmSize( ( 1000 * 1000 * 1000 ) ); // 1GB in bytes

		$container_config = new ContainersCreatePostBody();
		$container_config->setImage( 'selenium/standalone-chrome:3.4.0' );

		$port_binding = new PortBinding();
		$port_binding->setHostPort( $this->_selenium_port );
		$port_binding->setHostIp( '0.0.0.0' );

		$host_port_map             = new \ArrayObject();
		$host_port_map['4444/tcp'] = [ $port_binding ];
		$host_config->setPortBindings( $host_port_map );

		$container_config->setHostConfig( $host_config );

		$container_create = new ContainerCreate( $container_config );

		$container_body = $container_create->getBody( $serializer, $streamFactory );

		Log::instance()->write( 'Container Request Body (Selenium):', 2 );
		Log::instance()->write( $container_body[1], 2 );

		$this->_containers['selenium'] = $this->_docker->containerCreate( $container_config, [ 'name' => 'selenium-' . $this->_network_id ] );

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

			$exec_id           = $this->_docker->containerExec( 'wordpress-' . $this->_network_id, $exec_config )->getId();
			$exec_start_config = new ExecIdStartPostBody();
			$exec_start_config->setDetach( false );

			$stream = $this->_docker->execStart( $exec_id, $exec_start_config );

			$stream->wait();

			$exit_code = $this->_docker->execInspect( $exec_id )->getExitCode();

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

		foreach ( $this->_containers as $container ) {
			$response = $this->_docker->containerStart( $container->getId() );
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

		foreach ( $this->_containers as $container ) {
			$this->_docker->containerStop( $container->getId() );
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

		foreach ( $this->_containers as $container ) {
			$this->_docker->containerDelete( $container->getId() );
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
		$network_config->setName( $this->_network_id );

		$this->network = $this->_docker->networkCreate( $network_config );

		$network     = $this->_docker->networkInspect( $this->_network_id );
		$ipam_config = $network->getIPAM()->getConfig();

		$this->_gateway_ip = $ipam_config[0]['Gateway'];

		Log::instance()->write( 'Network ID: ' . $this->_network_id, 1 );
		Log::instance()->write( 'Gateway IP: ' . $this->_gateway_ip, 1 );

		return true;
	}

	/**
	 * Delete Docker network
	 *
	 * @return  bool
	 */
	public function deleteNetwork() {
		Log::instance()->write( 'Deleting network...', 1 );

		$this->_docker->networkDelete( $this->_network_id );

		return true;
	}

	/**
	 * Get Selenium server URL
	 *
	 * @return string
	 */
	public function getSeleniumServerUrl() {
		return 'http://localhost:' . intval( $this->_selenium_port ) . '/wd/hub';
	}

	/**
	 * Get WordPress homepage URL
	 *
	 * @return string
	 */
	public function getWpHomepageUrl() {
		return 'http://wpassure.test:' . intval( $this->_wordpress_port );
	}

	/**
	 * Get Gateway IP
	 *
	 * @return string
	 */
	public function getGatewayIP() {
		return $this->_gateway_ip;
	}

	/**
	 * Get current snapshot
	 *
	 * @return \WPSnapshots\Snapshot
	 */
	public function getSnapshot() {
		return $this->_snapshot;
	}

	/**
	 * Get MySQL credentials to use in WordPress
	 *
	 * @return array
	 */
	public function getMySQLCredentials() {
		return [
			'DB_HOST'     => 'mysql-' . $this->_network_id,
			'DB_NAME'     => 'wordpress',
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
		if ( empty( $this->_mysql_client ) ) {
			$this->_mysql_client = new MySQL( $this->getMySQLCredentials(), $this->_mysql_port, $this->_snapshot->meta['table_prefix'] );
		}

		return $this->_mysql_client;
	}

}
