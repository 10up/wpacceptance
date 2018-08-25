<?php
/**
 * Handle suite config file wpassure.json
 */

namespace WPAssure;

use WPAssure\Log;
use \ArrayAccess;

/**
 * Handle suite config files
 */
class Config implements ArrayAccess {

	/**
	 * Store WPAssure suite config
	 *
	 * @var array
	 */
	private $config;

	/**
	 * Initiate class
	 *
	 * @param  string $path Path to wpassure.json directory
	 */
	private function __construct( array $config ) {
		$this->config = $config;
	}

	/**
	 * Config factory method
	 *
	 * @param  string $path Path to wpassure.json directory
	 * @return Config|bool
	 */
	public static function create( $path = '' ) {
		Log::instance()->write( 'Parsing suite config.', 1 );

		if ( empty( $path ) ) {
			$path = getcwd();
		}

		if ( file_exists( $path . '/wpassure.json' ) ) {
			$raw_file = file_get_contents( $path . '/wpassure.json' );

			$config = json_decode( $raw_file, true );
		} else {
			Log::instance()->write( 'wpassure.json not found.', 0, 'error' );

			return false;
		}

		if ( empty( $config['name'] ) ) {
			Log::instance()->write( '`name` not set in wpassure.json', 0, 'error' );

			return false;
		}

		return new self( $config );
	}

	/**
	 * Set key in class
	 *
	 * @param  int|string $offset Array key
	 * @param  mixed $value  Array value
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
	 * Get array value by key
	 *
	 * @param  int|string $offset Array key
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		return isset( $this->config[ $offset] ) ? $this->config[ $offset ] : null;
	}
}
