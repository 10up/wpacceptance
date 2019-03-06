<?php
/**
 * Test gallery is functioning properly
 *
 * @package wpacceptance
 */

/**
 * PHPUnit test class
 */
class GalleryTest extends \WPAcceptance\PHPUnit\TestCase {

	/**
	 * @testdox Clicking gallery dots works.
	 */
	public function testGalleryNav() {
		$I = $this->openBrowserPage();

		$I->moveTo( '/' );

		$I->seeElement( '#metaslider_39 .slides li:nth-child(1)' );

		$I->moveMouse( '#metaslider_39' );

		$I->click( '#metaslider_39 .flex-control-nav li:nth-child(2) a' );

		sleep( 1 ); // Best to just sleep when dealing with fading

		$I->dontSeeElement( '#metaslider_39 .slides li:nth-child(1)' );
	}

	/**
	 * @testdox Clicking the next arrow performs a gallery slide to the correct image.
	 */
	public function testNextArrow() {
		$I = $this->openBrowserPage();

		$I->moveTo( '/' );

		$I->seeElement( '#metaslider_39 .slides li:nth-child(1)' );

		$I->moveMouse( '#metaslider_39' );

		$I->click( '#metaslider_39 .flex-next' );

		sleep( 1 ); // Best to just sleep when dealing with fading

		$I->seeElement( '#metaslider_39 .slides li:nth-child(2)' );

		$I->dontSeeElement( '#metaslider_39 .slides li:nth-child(1)' );
	}

	/**
	 * @testdox Clicking the previous arrow performs a gallery slide to the correct image.
	 */
	public function testPreviousArrow() {
		$I = $this->openBrowserPage();

		$I->moveTo( '/' );

		$I->seeElement( '#metaslider_39 .slides li:nth-child(1)' );

		$I->moveMouse( '#metaslider_39' );

		$I->click( '#metaslider_39 .flex-prev' );

		sleep( 1 ); // Best to just sleep when dealing with fading

		$I->seeElement( '#metaslider_39 .slides li:nth-child(5)' );

		$I->dontSeeElement( '#metaslider_39 .slides li:nth-child(1)' );
	}

	/**
	 * @testdox Next and previous arrows properly appear on hover.
	 */
	public function testShowNextPrev() {
		$I = $this->openBrowserPage();

		$I->moveTo( '/' );

		$I->dontSeeElement( '#metaslider_39 .flex-prev' );
		$I->dontSeeElement( '#metaslider_39 .flex-next' );

		$I->scrollToElement( '#metaslider_39' );

		$I->hover( '#metaslider_39' );

		sleep( 2 );

		$I->seeElement( '#metaslider_39 .flex-prev' );
		$I->seeElement( '#metaslider_39 .flex-next' );
	}

}
