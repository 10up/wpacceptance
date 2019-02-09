<?php
/**
 * Test if homepage is showing properly
 *
 * @package wpacceptance
 */

/**
 * PHPUnit test class
 */
class HomePageTest extends \WPAcceptance\PHPUnit\TestCase {

	/**
	 * @testdox I fill out search form, press enter, and the search page shows with results.
	 */
	public function testSearchForm() {
		$I = $this->getAnonymousUser();

		$I->moveTo( '/' );

		$element = $I->fillField( '.search-form input[type=search]', 'test search' );

		$I->pressEnterKey( $element );

		$I->waitUntilTitleContains( 'Search Results' );

		$this->assertTrue( true );
	}

}
