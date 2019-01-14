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
	 * Test gallery dots
	 */
	public function testGalleryNav() {
		$I = $this->getAnonymousUser();

		$I->moveTo( '/' );

		$I->seeElement( '#metaslider_39 .slides li:nth-child(1)' );

		$I->moveMouse( '#metaslider_39' );

		$I->click( '#metaslider_39 .flex-control-nav li:nth-child(2) a' );

		sleep( 1 ); // Best to just sleep when dealing with fading

		$I->dontSeeElement( '#metaslider_39 .slides li:nth-child(1)' );
	}

	/**
	 * Click next arrow, slide changes
	 */
	public function testNextArrow() {
		$I = $this->getAnonymousUser();

		$I->moveTo( '/' );

		$I->seeElement( '#metaslider_39 .slides li:nth-child(1)' );

		$I->moveMouse( '#metaslider_39' );

		$I->click( '#metaslider_39 .flex-next' );

		sleep( 1 ); // Best to just sleep when dealing with fading

		$I->seeElement( '#metaslider_39 .slides li:nth-child(2)' );

		$I->dontSeeElement( '#metaslider_39 .slides li:nth-child(1)' );
	}

	/**
	 * Click previous arrow, slide changes
	 */
	public function testPreviousArrow() {
		$I = $this->getAnonymousUser();

		$I->moveTo( '/' );

		$I->seeElement( '#metaslider_39 .slides li:nth-child(1)' );

		$I->moveMouse( '#metaslider_39' );

		$I->click( '#metaslider_39 .flex-prev' );

		sleep( 1 ); // Best to just sleep when dealing with fading

		$I->seeElement( '#metaslider_39 .slides li:nth-child(5)' );

		$I->dontSeeElement( '#metaslider_39 .slides li:nth-child(1)' );
	}

	/**
	 * Make sure next and prev arrows properly appear on hover
	 */
	public function testShowNextPrev() {
		$I = $this->getAnonymousUser();

		$I->moveTo( '/' );

		$I->dontSeeElement( '#metaslider_39 .flex-prev' );
		$I->dontSeeElement( '#metaslider_39 .flex-next' );

		$I->moveMouse( '#metaslider_39' );

		$I->seeElement( '#metaslider_39 .flex-prev' );
		$I->seeElement( '#metaslider_39 .flex-next' );
	}

}
