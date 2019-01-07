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
	 * On small screen, menu is hidden initially and opens on click
	 */
	public function testSmallScreenOpen() {
		$I = $this->getAnonymousUser( [ 'screen_size' => 'small' ] );

		$I->moveTo( '/' );

		$I->dontSeeElement( '#top-menu' );

		$I->click( '.menu-toggle' );

		$I->seeElement( '#top-menu' );
	}

	/**
	 * On large screen, menu items are visible
	 */
	public function testLargeScreenVisible() {
		$I = $this->getAnonymousUser();

		$I->moveTo( '/' );

		$I->seeElement( '#top-menu' );
	}

}
