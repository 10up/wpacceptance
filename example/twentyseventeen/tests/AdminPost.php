<?php
/**
 * Test that admins can manage posts
 *
 * @package wpassure
 */

/**
 * PHPUnit test class
 */
class AdminPostTest extends \WPAssure\PHPUnit\TestCase {

	/**
	 * On small screen, menu is hidden initially and opens on click
	 */
	public function testPostPublish() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post-new.php' );

		$I->waitUntilElementVisible( '#title' );

		$I->fillField( '#title', 'Test Post' );

		$I->setElementAttribute( '#content', 'value', 'Test content' );

		$I->click( '#publish' );

		$I->waitUntilElementVisible( '.notice-success' );

		$I->seeText( 'Post published', '.notice-success' );
	}

}
