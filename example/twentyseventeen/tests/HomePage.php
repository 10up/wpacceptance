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
	 * Fill out search form, press enter, search page shows with results
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
	 * Test that title is showing
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
	 * Test that main menu is showing
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
