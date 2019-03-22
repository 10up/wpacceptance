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
		$actor->seeTextInSource( '<body', '<html> not found in page source.' );
		$actor->seeTextInSource( '</body>', '</html> not found in page source.' );
	}

	/**
	 * Check that required HTML tags are shown
	 */
	protected function _testRequiredHTMLTags() {
		$actor = $this->openBrowserPage();

		$actor->moveTo( '/' );

		$actor->seeTextInSource( '<title>', '<title> not found in page source.' );
	}
}
