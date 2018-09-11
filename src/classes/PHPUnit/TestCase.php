<?php
/**
 * Base test class for WP Assure tests to extend
 *
 * @package  wpassure
 */

namespace WPAssure\PHPUnit;

/**
 * Class is abstract so PHPUnit doesn't flag it as empty
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase {

	use WebDriver;

}
