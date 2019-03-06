<?php
/**
 * Test menu is functioning properly
 *
 * @package wpacceptance
 */

/**
 * PHPUnit test class
 */
class MainMenuTest extends \WPAcceptance\PHPUnit\TestCase {

	/**
	 * @testdox On a small screen, the menu is hidden initially and opens on click
	 */
	public function testSmallScreenOpen() {
		$I = $this->openBrowserPage( [ 'screen_size' => 'small' ] );

		$I->moveTo( '/' );

		$I->dontSeeElement( '#top-menu' );

		$I->click( '.menu-toggle' );

		$I->seeElement( '#top-menu' );
	}

	/**
	 * @testdox On a large screen, I see menu items.
	 */
	public function testLargeScreenVisible() {
		$I = $this->openBrowserPage();

		$I->moveTo( '/' );

		$I->seeElement( '#top-menu' );
	}

}
