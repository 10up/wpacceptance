<?php

namespace WPAssure\PHPUnit;

use WPAssure\Log;
use WPAssure\PHPUnit\Actor;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

trait WebDriver {

	/**
	 * Facebook WebDrive instance
	 *
	 * @access private
	 * @var \Facebook\WebDriver\Remote\RemoteWebDriver
	 */
	private $_webDriver = null;

	/**
	 * Environment instance
	 *
	 * @access private
	 * @var \WPAssure\Environment
	 */
	private $_environment = null;

	/**
	 * Sets environment instance.
	 *
	 * @access public
	 * @param \WPAssure\Environment $environment Environment instance.
	 */
	public function setEnvironment( \WPAssure\Environment $environment ) {
		$this->_environment = $environment;
	}

	/**
	 * Returns current environment instance.
	 *
	 * @access public
	 * @throws \WPAssure\Exception if environment instance is not set.
	 * @return \WPAssure\Environment Environment instance.
	 */
	public function getEnvironment() {
		if ( ! $this->_environment ) {
			throw new \WPAssure\Exception( 'Environment is not set.' );
		}

		return $this->_environment;
	}

	/**
	 * Returns web driver instance.
	 *
	 * @access protected
	 * @return \Facebook\WebDriver\Remote\RemoteWebDriver Instance of remote web driver.
	 */
	protected function _getWebDriver() {
		if ( is_null( $this->_webDriver ) ) {
			$environment = $this->getEnvironment();
			$host = $environment->getSeleniumServerUrl();

			$capabilities = DesiredCapabilities::chrome();
			$this->_webDriver = RemoteWebDriver::create( $host, $capabilities, 20000 );
		}

		return $this->_webDriver;
	}

	/**
	 * Returns anonymous actor.
	 *
	 * @access public
	 * @param string $name An actor name.
	 * @return \WPAssure\PHPUnit\Actor An actor instance.
	 */
	public function getAnonymousUser( $name = 'anonymous user' ) {
		$webdriver = $this->_getWebDriver();
		$environment = $this->getEnvironment();

		$actor = new Actor( $name );
		$actor->setWebDriver( $webdriver );
		$actor->setEnvironment( $environment );
		$actor->setTest( $this );

		return $actor;
	}

}
