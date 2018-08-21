<?php

namespace WPAssure\PHPUnit;

use WPAssure\Exception;

class Actor {

	use WPAssure\PHPUnit\WebDriver\Popup,
	    WPAssure\PHPUnit\WebDriver\Navigation,
	    WPAssure\PHPUnit\WebDriver\Screenshot;

	/**
	 * Facebook WebDrive instance
	 *
	 * @access private
	 * @var \Facebook\WebDriver\Remote\RemoteWebDriver
	 */
	protected $_webdriver = null;

	/**
	 * Test case instance.
	 * 
	 * @access protected
	 * @var \PHPUnit\Framework\TestCase
	 */
	protected $_test = null;

	/**
	 * Sets a new instance of a web driver.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebDriver $webdriver A web driver instance.
	 */
	public function setWebDriver( $webdriver ) {
		$this->_webdriver = $webdriver;
	}

	/**
	 * Returns a web driver instance associated with the actor.
	 *
	 * @access public
	 * @throws \WPAssure\Exception if a web driver is not assigned.
	 * @return \Facebook\WebDriver\Remote\RemoteWebDriver An instance of a web driver.
	 */
	public function getWebDriver() {
		if ( ! $this->_webdriver ) {
			throw new Exception( 'WebDriver is not provided.' );
		}

		return $this->_webdriver;
	}

	/**
	 * Sets a new instance of PHPUnit test case.
	 *
	 * @access public
	 * @param \PHPUnit\Framework\TestCase $test A test case instance.
	 */
	public function setTest( $test ) {
		$this->_test = $test;
	}

	/**
	 * Returns an instance of a test case associated with the actor.
	 *
	 * @access public
	 * @throws \WPAssure\Exception if a test case is not assigned.
	 * @return \PHPUnit\Framework\TestCase An instance of a test case.
	 */
	public function getTest() {
		if ( ! $this->_test ) {
			throw new Exception( 'Test case is not provided.' );
		}

		return $this->_test;
	}

}
