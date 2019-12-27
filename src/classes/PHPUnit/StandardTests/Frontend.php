<?php
/**
 * Standard front end tests
 *
 * @package wpacceptance
 */

namespace WPAcceptance\PHPUnit\StandardTests;

/**
 * PHPUnit test class
 */
trait Frontend {

	/**
	 * Check that all html outputted on homepage
	 */
	protected function _testPageLoaded() {
		$actor = $this->openBrowserPage();

		$actor->moveTo( '/' );

		$actor->seeTextInSource( '<html', '<html*> not found in page source.' );
		$actor->seeTextInSource( '</html>', '</html> not found in page source.' );
		$actor->seeTextInSource( '<body', '<body*> not found in page source.' );
		$actor->seeTextInSource( '</body>', '</body> not found in page source.' );
	}

	/**
	 * Check that required HTML tags are shown
	 */
	protected function _testRequiredHTMLTags() {
		$actor = $this->openBrowserPage();

		$actor->moveTo( '/' );

		$actor->seeTextInSource( '<title>', '<title> not found in page source.' );
	}

	/**
	 * Test that a post shows with the proper permalink structure
	 */
	protected function _testPostShows() {
		$this->createPost();

		$actor = $this->openBrowserPage();

		// First set permalinks
		$actor->login();

		$actor->moveTo( 'wp-admin/options-permalink.php' );

		$actor->click( 'input[value="/%postname%/"]' );

		$actor->click( '#submit' );

		$actor->waitUntilElementVisible( '#wpadminbar' );

		$actor->moveTo( 'test-post' );

		$actor->seeText( 'Test post content' );

		$actor->seeElement( 'body.single' );
	}
}
