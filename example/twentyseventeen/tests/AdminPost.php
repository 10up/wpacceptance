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
	 * @testdox I can successfully publish a post in Gutenberg.
	 */
	public function testPostPublish() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post-new.php' );

		$I->waitUntilElementVisible( '.nux-dot-tip__disable' );

		$I->click( '.nux-dot-tip__disable' );

		$I->fillField( '#post-title-0', 'Test Post' );

		$I->waitUntilElementVisible( '.editor-post-publish-button' );

		$I->click( '.editor-post-publish-button' );

		$I->waitUntilElementVisible( '.components-notice' );

		$I->seeText( 'Post published', '.components-notice__content' );

		// Make sure data is in DB correctly
		$post_id = (int) $I->getElement( '#post_ID' )->getAttribute( 'value' );

		self::assertPostFieldContains( $post_id, 'post_title', 'Test Post' );
	}

}
