<?php
/**
 * Standard Block tests
 *
 * @package wpacceptance
 */

namespace WPAcceptance\PHPUnit\Gutenberg;

/**
 * PHPUnit test class
 */
trait Gutenberg {
	/**
	 * Test that the paragraph block is available and functional.
	 */
	protected function _testParagraphBlock() {
		$actor = $this->openBrowserPage();

		$actor->login();

		$actor->navigateToNewPost();

		$actor->dismissGutenbergNotices();

		$actor->addBlock( 'core/paragraph', 'paragraph' );

		$actor->seeBlockIsAdded( 'core/paragraph' );

		$actor->saveGutenbergDraft();

		usleep( 200000 );

		$actor->dontSeeGutenbergErrors();
	}
}
