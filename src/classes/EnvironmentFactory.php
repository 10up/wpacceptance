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
	 * @return int
	 */
	public static function get( $index = 0 ) {
		return self::$environments[ $index ];
	}

	/**
	 * Create environment
	 *
	 * @param  string  $snapshot_id WPSnapshot ID to load into environment
	 * @param  array   $suite_config Config array
	 * @param  boolean $preserve_containers Keep containers alive or not
	 * @return  Environment|bool
	 */
	public static function create( $snapshot_id, $suite_config, $preserve_containers = false ) {
		$environment = new Environment( $snapshot_id, $suite_config, $preserve_containers );

		self::$environments[] = $environment;

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
			$environment->destroy();

			return false;
		}

		if ( ! $environment->pullSnapshot() ) {
			$environment->destroy();

			return false;
		}

		return $environment;
	}
}
