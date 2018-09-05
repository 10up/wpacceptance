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
	 * Suite config
	 *
	 * @var  array
	 */
	protected $suite_config;

	/**
	 * Environment constructor
	 *
	 * @param  string $snapshot_id WPSnapshot ID to load into environment
	 * @param  array  $suite_config Config array
	 */
	protected function __construct( $snapshot_id, $suite_config ) {
		$this->network_id   = 'wpassure' . time();
		$this->docker       = Docker::create();
		$this->snapshot_id  = $snapshot_id;
		$this->suite_config = $suite_config;
	}

	/**
	 * Create environment
	 *
	 * @param  string $snapshot_id WPSnapshot ID to load into environment
	 * @param  array  $suite_config Config array
	 * @return  self|bool
	 */
	public static function create( $snapshot_id, $suite_config ) {
		$enviromment = new self( $snapshot_id, $suite_config );

		if ( ! $enviromment->createNetwork() ) {
			return false;
		}

		if ( ! $enviromment->downloadImages() ) {
			return false;
		}

		if ( ! $enviromment->createContainers() ) {
			return false;
		}

		if ( ! $enviromment->startContainers() ) {
			$enviromment->destroy();

			return false;
		}

		if ( ! $enviromment->pullSnapshot() ) {
			$enviromment->destroy();

			return false;
		}

		return $enviromment;
	}

	/**
	 * Pull WP Snapshot into container
	 *
	 * @return  bool
	 */
	public function pullSnapshot() {
		/**
		 * Optionally update WP Snapshots
		 */
		if ( true ) {
			Log::instance()->write( 'Updating WP Snapshots...', 1 );

			$exec_config = new ContainersIdExecPostBody();
			$exec_config->setTty( true );
			$exec_config->setAttachStdout( true );
			$exec_config->setAttachStderr( true );
			$exec_config->setCmd( [ 'composer', 'global', 'update', '10up/wpsnapshots' ] );

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
		}

		/**
		 * Pulling snapshot
		 */

		Log::instance()->write( 'Pulling snapshot...', 1 );

		$snapshot = Snapshot::get( $this->snapshot_id );

		$site_mapping = [];

		Log::instance()->write( 'Snapshot site mapping:', 1 );

		foreach ( $snapshot->meta['sites'] as $site ) {
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

		$command = '/root/.composer/vendor/bin/wpsnapshots pull ' . $this->snapshot_id . ' --confirm --config_db_name="wordpress" --config_db_user="root" --config_db_password="password" --config_db_host="mysql-' . $this->network_id . '" --confirm_wp_download --confirm_config_create --site_mapping="' . addslashes( json_encode( $site_mapping ) ) . '"';

		Log::instance()->write( 'Running command:', 1 );
		Log::instance()->write( $command, 1 );

		$exec_config = new ContainersIdExecPostBody();
		$exec_config->setTty( true );
		$exec_config->setAttachStdout( true );
		$exec_config->setAttachStderr( true );
		$exec_config->setCmd( [ '/bin/sh', '-c', $command ] );

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

		return true;
	}

	/**
	 * Destroy environment
	 *
	 * @return  bool
	 */
	public function destroy() {
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
				'name' => 'wordpress',
				'tag'  => 'latest',
			],
			[
				'name' => 'nginx',
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
	 * Create Docker containers
	 *
	 * @return  bool
	 */
	public function createContainers() {
		Log::instance()->write( 'Creating containers...' );

		/**
		 * Create MySQL
		 */

		$host_config = new HostConfig();
		$host_config->setNetworkMode( $this->network_id );

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

		$this->containers['mysql'] = $this->docker->containerCreate( $container_config, [ 'name' => 'mysql-' . $this->network_id ] );

		$this->mysql_stream = $this->docker->containerAttach(
			'mysql-' . $this->network_id, [
				'stream' => true,
				'stdin'  => true,
				'stdout' => true,
				'stderr' => true,
			]
		);

		/**
		 * Create WP container
		 */

		$context = new Context( __DIR__ . '/../../docker/wordpress' );

		$input_stream = $context->toStream();

		$build_stream = $this->docker->imageBuild( $input_stream, [ 't' => 'wpassure-wordpress' ] );
		$build_stream->onFrame(
			function( BuildInfo $build_info ) {
					Log::instance()->write( $build_info->getStream(), 1 );
			}
		);

		$build_stream->wait();

		$this->wordpress_port = Utils\find_open_port( '127.0.0.1', 1000, 9999 );

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

		$container_config->setImage( 'wpassure-wordpress' );
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

		$this->containers['wordpress'] = $this->docker->containerCreate( $container_config, [ 'name' => 'wordpress-' . $this->network_id ] );

		/**
		 * Create selenium container
		 */

		for ( $i = 1000; $i <= 9999; $i++ ) {
			$this->selenium_port = $i;

			if ( $i !== $this->wordpress_port && Utils\is_open_port( '127.0.0.1', $i ) ) {
				break;
			}
		}

		$host_config = new HostConfig();
		$host_config->setNetworkMode( $this->network_id );
		$host_config->setExtraHosts( [ 'wpassure.test:' . $this->gateway_ip ] );

		$container_config = new ContainersCreatePostBody();
		$container_config->setImage( 'selenium/standalone-chrome:3.4.0' );

		$port_binding = new PortBinding();
		$port_binding->setHostPort( $this->selenium_port );
		$port_binding->setHostIp( '0.0.0.0' );

		$host_port_map             = new \ArrayObject();
		$host_port_map['4444/tcp'] = [ $port_binding ];
		$host_config->setPortBindings( $host_port_map );

		$container_config->setHostConfig( $host_config );

		$this->containers['selenium'] = $this->docker->containerCreate( $container_config, [ 'name' => 'selenium-' . $this->network_id ] );

		return true;
	}

	/**
	 * Start containers
	 *
	 * @return  bool
	 */
	public function startContainers() {
		Log::instance()->write( 'Starting containers...', 1 );

		foreach ( $this->containers as $container ) {
			$this->docker->containerStart( $container->getId() );
		}

		Log::instance()->write( 'Waiting for MySQL to start...', 1 );

		$mysql_started = false;

		$this->mysql_stream->onStdout(
			function( $stdout ) {
				if ( preg_match( '#MySQL init process done#i', $stdout ) ) {
					  $mysql_started = true;
				}
			}
		);

		for ( $i = 0; $i < 15; $i ++ ) {
			if ( $mysql_started ) {
				break;
			}

			sleep( 1 );
		}

		return true;
	}

	/**
	 * Stop Docker containers
	 *
	 * @return  bool
	 */
	public function stopContainers() {
		Log::instance()->write( 'Stopping containers...', 1 );

		foreach ( $this->containers as $container ) {
			$this->docker->containerStop( $container->getId() );
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
			$this->docker->containerDelete( $container->getId() );
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
	 * Get Selenium port
	 *
	 * @return int
	 */
	public function getSeleniumPort() {
		return $this->selenium_port;
	}

	/**
	 * Get WordPress port
	 *
	 * @return int
	 */
	public function getWordPressPort() {
		return $this->wordpress_port;
	}

	/**
	 * Get Gateway IP
	 *
	 * @return string
	 */
	public function getGatewayIP() {
		return $this->gateway_ip;
	}
}
