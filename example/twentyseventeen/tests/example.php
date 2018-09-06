<?php

class ExampleTest extends \WPAssure\PHPUnit\TestCase {

	public function test() {
		$I = $this->getAnonymousUser();
		$I->amOnPage( '/' );

		$element = false;

		try {
			$element = $I->getElement( '.site-title' );
		} catch ( \Exception $e ) {
			// Continue to assertion
		}

		$this->assertNotEquals( $element, false );
	}

}
