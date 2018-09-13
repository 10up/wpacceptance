<?php
/**
 * Functionality for setting up web driver. See https://github.com/facebook/php-webdriver
 *
 * @package  wpassure
 */

namespace WPAssure\PHPUnit;

use WPAssure\Log;
use WPAssure\EnvironmentFactory;
use WPAssure\PHPUnit\Actor;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * Web Driver trait for use with PHPUnit test class
 */
trait WebDriver {

	/**
	 * Facebook WebDrive instance
	 *
	 * @access private
	 * @var \Facebook\WebDriver\Remote\RemoteWebDriver
	 */
	private $web_driver = null;

	/**
	 * Returns web driver instance.
	 *
	 * @access protected
	 * @return \Facebook\WebDriver\Remote\RemoteWebDriver Instance of remote web driver.
	 */
	protected function getWebDriver() {
		if ( is_null( $this->web_driver ) ) {

			$capabilities     = DesiredCapabilities::chrome();
			$this->web_driver = RemoteWebDriver::create( $this->getSeleniumServerUrl(), $capabilities, 20000 );
		}

		return $this->web_driver;
	}

	/**
	 * Get WordPress URL
	 *
	 * @return string
	 */
	public function getWordPressUrl() {
		return EnvironmentFactory::get()->getWpHomepageUrl();
	}

	/**
	 * Get Selenium host URL
	 *
	 * @return string
	 */
	public function getSeleniumServerUrl() {
		return EnvironmentFactory::get()->getSeleniumServerUrl();
	}

	/**
	 * Returns anonymous actor.
	 *
	 * @access public
	 * @param string $name An actor name.
	 * @return \WPAssure\PHPUnit\Actor An actor instance.
	 */
	public function getAnonymousUser( $name = 'anonymous user' ) {
		$webdriver = $this->getWebDriver();

		$actor = new Actor( $name );
		$actor->setWebDriver( $webdriver );
		$actor->setTest( $this );

		return $actor;
	}

}
