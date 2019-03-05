<?php
/**
 * Test the front end
 *
 * @package wpacceptance
 */

/**
 * PHPUnit test class
 */
class BackendTest extends \WPAcceptance\PHPUnit\TestCase {

	/**
	 * Test that someone can login
	 */
	public function testLogin() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		$I->seeElement( '#wpadminbar', 'Could not login.' );
	}

	/**
	 * Test admin bar shows on the front end
	 */
	public function testAdminBarOnFront() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( '/' );

		$I->seeElement( '#wpadminbar', 'Admin bar not showing on front end.' );
	}

	/**
	 * Test that a post actually publishes
	 */
	public function testPostPublish() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post-new.php' );

		$I->waitUntilElementVisible( '#title' );

		$I->fillField( '#title', 'Test Post' );

		$I->setElementAttribute( '#content', 'value', 'Test content' );

		$I->click( '#publish' );

		$I->waitUntilElementVisible( '.notice-success' );

		$I->seeText( 'Post published', '.notice-success' );
	}

	/**
	 * Test that a user can edit their profile
	 */
	public function testProfileSave() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( '/wp-admin/profile.php' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		$I->fillField( '#first_name', 'Test Name' );

		$I->click( '#submit' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		$I->seeValueInAttribute( '#first_name', 'value', 'Test Name', 'Profile field did not update.' );
	}
}
