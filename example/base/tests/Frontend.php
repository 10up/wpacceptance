<?php
/**
 * Test the front end
 *
 * @package wpacceptance
 */

/**
 * PHPUnit test class
 */
class FrontendTest extends \WPAcceptance\PHPUnit\TestCase {

	/**
	 * Check that all html outputted on homepage
	 */
	public function testPageLoaded() {
		$I = $this->openBrowserPage();

		$I->moveTo( '/' );

		$I->seeTextInSource( '<html', '<html*> not found in page source.' );
		$I->seeTextInSource( '</html>', '</html> not found in page source.' );
		$I->seeTextInSource( '<body', '<html> not found in page source.' );
		$I->seeTextInSource( '</body>', '</html> not found in page source.' );
	}

	/**
	 * Check that required HTML tags are shown
	 */
	public function testRequiredHTMLTags() {
		$I = $this->openBrowserPage();

		$I->moveTo( '/' );

		$I->seeTextInSource( '<title>', '<title> not found in page source.' );
	}
}
