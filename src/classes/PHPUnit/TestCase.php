<?php
/**
 * Base test class for WP Assure tests to extend
 *
 * @package  wpassure
 */

namespace WPAssure\PHPUnit;

use WPAssure\Log;
use WPAssure\EnvironmentFactory;

/**
 * Class is abstract so PHPUnit doesn't flag it as empty
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase {

	use WebDriver, Database;

	/**
	 * Store the last modifying query in the DB. We do this to determine if the DB is dirty (has changed)
	 *
	 * @var array
	 */
	private $last_modifying_query = [];

	/**
	 * Run before each test starts
	 */
	public function setUp() {
		parent::setUp();

		Log::instance()->write( 'Running test on MySQL DB: ' . self::getCurrentDatabaseName(), 2 );

		$this->last_modifying_query = $this->getLastModifyingQuery();

		if ( ! empty( $this->last_modifying_query ) ) {
			Log::instance()->write( 'Last modifying query at ' . $this->last_modifying_query['event_time'] . ': ' . $this->last_modifying_query['argument'], 2 );
		}
	}

	/**
	 * Ran after each test
	 */
	public function tearDown() {
		parent::tearDown();

		$new_last_modifying_query = $this->getLastModifyingQuery();

		if ( ! empty( $new_last_modifying_query ) && $new_last_modifying_query['event_time'] !== $this->last_modifying_query['event_time'] ) {
			Log::instance()->write( 'Test modified the database (' . $this->getName() . ').', 1, 'warning' );
			Log::instance()->write( 'Last query at ' . $new_last_modifying_query['event_time'] . ': ' . $new_last_modifying_query['argument'], 2 );

			$config = EnvironmentFactory::get()->getSuiteConfig();

			if ( ! empty( $config['test_clean_db'] ) ) {
				Log::instance()->write( 'Setting up clean database.', 1 );
				EnvironmentFactory::get()->makeCleanDB();
			}
		}

		$this->last_modifying_query = $new_last_modifying_query;
	}

}
