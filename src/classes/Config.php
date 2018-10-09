<?php
/**
 * Handle suite config file wpassure.json
 *
 * @package  wpsnapshots
 */

namespace WPAssure;

use WPAssure\Log;
use \ArrayAccess;
use WPAssure\Utils;

/**
 * Handle suite config files
 */
class Config implements ArrayAccess {

	/**
	 * Store WPAssure suite config
	 *
	 * @var array
	 */
	protected $config;

	/**
	 * Initiate class
	 *
	 * @param  array $config Configuration array
	 */
	public function __construct( array $config ) {
		$this->config = $config;
	}

	/**
	 * Config factory method
	 *
	 * @param  string $path Path to a directory with wpassure.json file or config file itself.
	 * @return Config|bool
	 */
	public static function create( $path = '' ) {
		Log::instance()->write( 'Parsing suite config.', 1 );

		if ( empty( $path ) ) {
			$path = Utils\trailingslash( getcwd() );
		} else {
			$path = Utils\normalize_path( $path );
		}

		$file_path = $path . 'wpassure.json';

		if ( file_exists( $file_path ) ) {
			$raw_file = file_get_contents( $file_path );
			$config   = json_decode( $raw_file, true );
		} else {
			Log::instance()->write( 'wpassure.json not found in ' . dirname( $file_path ), 0, 'error' );

			return false;
		}

		if ( empty( $config['name'] ) ) {
			Log::instance()->write( '`name` not set in wpassure.json', 0, 'error' );

			return false;
		}

		// $config['path'] === path to wpassure.json direcfory in host machine
		$config['path'] = Utils\trailingslash( dirname( $file_path ) );

		if ( empty( $config['repo_path'] ) ) {
			$config['host_repo_path'] = $config['path'];
		} else {
			if ( '.' === trim( $config['repo_path'] ) || './' === trim( $config['repo_path'] ) ) {
				$config['host_repo_path'] = $config['path'];

				unset( $config['repo_path'] );
			} else {
				if ( preg_match( '#.*/.$#', $config['repo_path'] ) ) {
					$config['repo_path'] = preg_replace( '#(.*)/.$#', '$1', $config['repo_path'] );
				}

				if ( false === stripos( $config['repo_path'], '%WP_ROOT%' ) ) {
					$config['host_repo_path'] = Utils\trailingslash( realpath( $config['path'] . $config['repo_path'] ) );
				} else {
					$wp_dir = Utils\trailingslash( realpath( Utils\get_wordpress_path( $config['path'] ) ) );

					$config['host_repo_path'] = Utils\trailingslash( $wp_dir . preg_replace( '#^/?%WP_ROOT%/?(.*)$#i', '$1', $config['repo_path'] ) );
				}
			}
		}

		if ( empty( $config['snapshot_id'] ) ) {
			$config['snapshot_id'] = false;
		}

		asort( $config );

		return new self( $config );
	}

	/**
	 * Write config to current wpassure.json file
	 */
	public function write() {
		Log::instance()->write( 'Writing config.', 1 );

		$file_config = $this->config;
		unset( $file_config['path'] );

		if ( isset( $file_config['snapshot_id'] ) && empty( $file_config['snapshot_id'] ) ) {
			unset( $file_config['snapshot_id'] );
		}

		file_put_contents( Utils\trailingslash( $this->config['path'] ) . 'wpassure.json', json_encode( $file_config, JSON_PRETTY_PRINT ) );
	}

	/**
	 * Set key in class
	 *
	 * @param  int|string $offset Array key
	 * @param  mixed      $value  Array value
	 */
	public function offsetSet( $offset, $value ) {
		if ( is_null( $offset ) ) {
			$this->config[] = $value;
		} else {
			$this->config[ $offset ] = $value;
		}
	}

	/**
	 * Check if key exists
	 *
	 * @param  int|string $offset Array key
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		return isset( $this->config[ $offset ] );
	}

	/**
	 * Delete array value by key
	 *
	 * @param  int|string $offset Array key
	 */
	public function offsetUnset( $offset ) {
		unset( $this->config[ $offset ] );
	}

	/**
	 * Get config array
	 *
	 * @return array
	 */
	public function toArray() {
		return $this->config;
	}

	/**
	 * Get array value by key
	 *
	 * @param  int|string $offset Array key
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		return isset( $this->config[ $offset ] ) ? $this->config[ $offset ] : null;
	}
}
