<?php
/**
 * Test that admins can manage posts
 *
 * @package wpacceptance
 */

/**
 * PHPUnit test class
 */
class AdminPostTest extends \WPAcceptance\PHPUnit\TestCase {

	/**
	 * Test that post publishes
	 */
	/*public function testPostPublish() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post-new.php' );

		$I->fillField( '#title', 'Test Post' );

		$I->setElementAttribute( '#content', 'value', 'Test content' );

		$I->click( '#publish' );

		$I->waitUntilElementVisible( '.notice-success' );

		$I->seeText( 'Post published', '.notice-success' );
	}*/

}
