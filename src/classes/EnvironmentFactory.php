<?php
/**
 * Environment factory for storing Environments
 *
 * @package wpassure
 */

namespace WPAssure;

/**
 * Environment factory class
 */
class EnvironmentFactory {
	/**
	 * Registered environments
	 *
	 * @var array
	 */
	public static $environments = [];

	/**
	 * Get an environment given an index
	 *
	 * @param  int $index Environments index
	 * @return \WPAssure\Environment|boolean;
	 */
	public static function get( $index = 0 ) {
		if ( ! empty( self::$environments[ $index ] ) ) {
			return self::$environments[ $index ];
		}

		return false;
	}

	/**
	 * Clean up environments on shutdown
	 */
	public static function handleShutdown() {
		foreach ( self::$environments as $environment ) {
			$environment->destroy();
		}
	}

	/**
	 * Build a environment object around an existing environment
	 *
	 * @param  string $environment_id Environment ID
	 */
	public static function createFromId( $environment_id, $preserve_containers = false ) {
		$environment = new Environment( null, null, $preserve_containers );

		if ( empty( self::$environments ) ) {
			register_shutdown_function( [ '\WPAssure\EnvironmentFactory', 'handleShutdown' ] );
		}

		$environment->initiateExistingEnvironment( $environment_id );

		if ( ! $environment->insertRepo() ) {
			return false;
		}

		if ( ! $environment->setupMySQL() ) {
			return false;
		}

		if ( ! $environment->runBeforeScripts() ) {
			return false;
		}

		self::$environments[] = $environment;

		return $environment;
	}

	/**
	 * Create environment
	 *
	 * @param  string  $snapshot_id WPSnapshot ID to load into environment
	 * @param  array   $suite_config Config array
	 * @param  boolean $preserve_containers Keep containers alive or not
	 * @return  \WPAssure\Environment|bool
	 */
	public static function create( $snapshot_id, $suite_config, $preserve_containers = false ) {
		$environment = new Environment( $snapshot_id, $suite_config, $preserve_containers );

		if ( empty( self::$environments ) ) {
			register_shutdown_function( [ '\WPAssure\EnvironmentFactory', 'handleShutdown' ] );
		}

		if ( ! $environment->createNetwork() ) {
			return false;
		}

		if ( ! $environment->downloadImages() ) {
			return false;
		}

		if ( ! $environment->createContainers() ) {
			return false;
		}

		if ( ! $environment->startContainers() ) {
			return false;
		}

		if ( ! $environment->pullSnapshot() ) {
			return false;
		}

		if ( ! $environment->setupMySQL() ) {
			return false;
		}

		if ( ! $environment->insertRepo() ) {
			return false;
		}

		if ( ! $environment->writeMetaToWPContainer() ) {
			return false;
		}

		if ( ! $environment->runBeforeScripts() ) {
			return false;
		}

		self::$environments[] = $environment;

		return $environment;
	}
}
