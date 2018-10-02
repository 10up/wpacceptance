<?php
/**
 * Test the front end
 *
 * @package wpassure
 */

/**
 * PHPUnit test class
 */
class FrontendTest extends \WPAssure\PHPUnit\TestCase {

	/**
	 * Check that all html outputted on homepage
	 */
	public function testPageLoaded() {
		$I = $this->getAnonymousUser();

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
		$I = $this->getAnonymousUser();

		$I->moveTo( '/' );

		$I->seeTextInSource( '<title>', '<title> not found in page source.' );
	}
}
