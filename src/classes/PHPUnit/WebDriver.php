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
use Facebook\WebDriver\Chrome\ChromeOptions;
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
	 * @param  array $options Chrome options to define
	 * @return \Facebook\WebDriver\Remote\RemoteWebDriver Instance of remote web driver.
	 */
	protected function createWebDriver( $options ) {
		if ( ! empty( $this->web_driver ) ) {
			$this->web_driver->close();
		}

		$chrome_options = new ChromeOptions();

		$processed_options = [];

		foreach ( $options as $key => $value ) {
			$processed_options[] = $key . '=' . $value;
		}

		$chrome_options->addArguments( $processed_options );

		$capabilities = DesiredCapabilities::chrome();
		$capabilities->setCapability( ChromeOptions::CAPABILITY, $chrome_options );

		$this->web_driver = RemoteWebDriver::create( $this->getSeleniumServerUrl(), $capabilities, 20000 );

		return $this->web_driver;
	}

	/**
	 * Get current Web Driver instance
	 *
	 * @return \Facebook\WebDriver\Remote\RemoteWebDriver
	 */
	protected function getWebDriver() {
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
	 * @param array $options Actor options
	 * @return \WPAssure\PHPUnit\Actor An actor instance.
	 */
	public function getAnonymousUser( $options = [] ) {
		if ( ! empty( $options['screen_size'] ) ) {
			if ( 'small' === $options['screen_size'] ) {
				$options['width']  = 375;
				$options['height'] = 667;
			} elseif ( 'extra-large' === $options['screen_size'] ) {
				$options['width']  = 1920;
				$options['height'] = 1080;
			} elseif ( 'large' === $options['screen_size'] ) {
				$options['width']  = 1366;
				$options['height'] = 768;
			}
		}

		$default_options = [
			'name'   => 'anonymous user',
			'width'  => 1366,
			'height' => 768,
		];

		$options = array_merge( $default_options, $options );

		$web_driver = $this->createWebDriver( [ '--window-size' => $options['width'] . ',' . $options['height'] ] );

		$actor = new Actor( $options['name'] );
		$actor->setWebDriver( $web_driver );
		$actor->setTest( $this );

		return $actor;
	}

}
