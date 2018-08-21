<?php

namespace WPAssure\PHPUnit\WebDriver;

use WPAssure\Log;

trait Navigation {

	/**
	 * Moves back to the previous page in the history.
	 *
	 * @access public
	 */
	public function moveBack() {
		$webdriver = $this->getWebDriver();
		$webdriver->navigate()->back();
		Log::instance()->write( 'Back to ' . $webdriver->getCurrentURL(), 1 );
	}

	/**
	 * Moves forward to the next page in the history.
	 *
	 * @access public
	 */
	public function moveForward() {
		$webdriver = $this->getWebDriver();
		$webdriver->navigate()->forward();
		Log::instance()->write( 'Forward to ' . $webdriver->getCurrentURL(), 1 );
	}

	/**
	 * Refreshes the current page.
	 *
	 * @access public
	 */
	public function refresh() {
		$this->getWebDriver()->navigate()->refresh();
		Log::instance()->write( 'Refreshed the current page', 1 );
	}

	/**
	 * Navigates to a new URL.
	 *
	 * @access public
	 * @param string $url A new URl.
	 */
	public function moveTo( $url ) {
		$this->getWebDriver()->navigate()->to( $url );
		Log::instance()->write( 'Navigate to ' . $webdriver->getCurrentURL(), 1 );
	}

}
