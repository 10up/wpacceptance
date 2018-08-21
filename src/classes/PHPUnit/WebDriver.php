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
			$host = $this->_environment->getSeleniumServerUrl();

			$capabilities = DesiredCapabilities::chrome();
			$this->_webDriver = RemoteWebDriver::create( $host, $capabilities, 20000 );
		}

		return $this->_webDriver;
	}

	/**
	 * Returns a new actor that is initialized on a specific page.
	 * 
	 * @access public
	 * @param string $url_path The relative path to a landing page.
	 * @return \WPAssure\PHPUnit\Actor An actor instance.
	 */
	public function amOnPage( $url_path ) {
		$url_parts = parse_url( $url_path );

		$path = $url_parts['path'];

		if ( empty( $path ) ) {
			$path = '/';
		} elseif ( '/' !== substr( $path, 0, 1 ) ) {
			$path = '/' . $path;
		}

		$environment = $this->getEnvironment();
		$page = $environment->getWpHomepageUrl() . $path;

		Log::instance()->write( 'Navigating to URL: ' . $page, 1 );

		$webdriver = $this->_getWebDriver();
		$webdriver->get( $page );

		$actor = new Actor();
		$actor->setWebDriver( $webdriver );
		$actor->setTest( $this );

		return $actor;
	}

}
