<?php

class ExampleTest extends \WPAssure\TestCase {

	public function test() {
		$I = $this->getAnonymousUser();
		$I->amOnPage( '/' );
		$I->click( '.path .to .an-element' );
	}

}
