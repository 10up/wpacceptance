<?php

namespace WPAssure\PHPUnit;

use WPAssure\Log;
use WPAssure\EnvironmentFactory;
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
	 * Returns web driver instance.
	 *
	 * @access protected
	 * @return \Facebook\WebDriver\Remote\RemoteWebDriver Instance of remote web driver.
	 */
	protected function _getWebDriver() {
		if ( is_null( $this->_webDriver ) ) {

			$capabilities = DesiredCapabilities::chrome();
			$this->_webDriver = RemoteWebDriver::create( EnvironmentFactory::get()->getSeleniumServerUrl(), $capabilities, 20000 );
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

		$actor = new Actor( $name );
		$actor->setWebDriver( $webdriver );
		$actor->setTest( $this );

		return $actor;
	}

}
