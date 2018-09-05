<?php

class ExampleTest extends \WPAssure\PHPUnit\TestCase {

	public function test() {
		$I = $this->getAnonymousUser();
		$I->amOnPage( '/' );
		$I->click( '.path .to .an-element' );

		$this->assertTrue( true );
	}

}
