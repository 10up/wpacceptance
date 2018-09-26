<?php
/**
 * Test that admins can manage media
 *
 * @package wpassure
 */

/**
 * PHPUnit test class
 */
class AdminMediaTest extends \WPAssure\PHPUnit\TestCase {

	/**
	 * Check that we can add a featured image
	 */
	public function testFeaturedImage() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post-new.php' );

		$I->waitUntilElementVisible( '#title' );

		$I->fillField( '#title', 'Test Post' );

		// Set featured image
		$I->click( '#set-post-thumbnail' );
		$I->attachFile( '.media-modal-content input[type="file"]', __DIR__ . '/img/10up-logo.jpg' );

		$I->waitUntilElementEnabled( '.media-modal-content .media-button-select' );

		$I->click( '.media-modal-content .media-button-select' );

		$I->waitUntilElementVisible( '#remove-post-thumbnail' );

		$I->click( '#publish' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		// See featured image
		$I->seeElement( '#postimagediv img' );
	}

}
