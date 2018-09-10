<?php
/**
 * Test if homepage is showing properly
 *
 * @package wpassure
 */

/**
 * PHPUnit test class
 */
class HomePageShowingTest extends \WPAssure\PHPUnit\TestCase {

	/**
	 * Test that title is showing
	 */
	public function testTitleShowing() {
		$I = $this->getAnonymousUser();

		$I->amOnPage( '/' );

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

		$I->amOnPage( '/' );

		$element = false;

		try {
			$element = $I->getElement( '#site-navigation' );
		} catch ( \Exception $e ) {
			// Continue to assertion
		}

		$this->assertNotEquals( $element, false );
	}
}
