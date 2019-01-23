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

	/**
	 * @testdox I see the page title.
	 */
	public function testTitleShowing() {
		$I = $this->getAnonymousUser();

		$I->moveTo( '/' );

		$element = false;

		try {
			$element = $I->getElement( '.site-title' );
		} catch ( \Exception $e ) {
			// Continue to assertion
		}

		$this->assertNotEquals( $element, false );
	}

	/**
	 * @testdox I see the main menu.
	 */
	public function testMainMenuShowing() {
		$I = $this->getAnonymousUser();

		$I->moveTo( '/' );

		$element = false;

		try {
			$element = $I->getElement( '#site-navigation' );
		} catch ( \Exception $e ) {
			// Continue to assertion
		}

		$this->assertNotEquals( $element, false );
	}
}
