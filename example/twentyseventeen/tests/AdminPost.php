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
	 * Test that post publishes in Gutenberg
	 *
	 * @testdox This is a test
	 */
	public function testPostPublish() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post-new.php' );

		$I->fillField( '#post-title-0', 'Test Post' );

		$I->click( '.editor-block-list__layout .wp-block' );

		$I->fillField( '.editor-block-list__layout .wp-block:first-child p', 'Test content' );

		$I->click( '.editor-post-publish-panel__toggle' );

		$I->waitUntilElementVisible( '.editor-post-publish-button' );

		$I->click( '.editor-post-publish-button' );

		$I->waitUntilElementVisible( '.components-notice' );

		$I->seeText( 'Post published', '.components-notice__content' );

		// Make sure data is in DB correctly
		$post_id = (int) $I->getElement( '#post_ID' )->getAttribute( 'value' );

		self::assertPostFieldContains( $post_id, 'post_title', 'Test Post' );
	}

}
