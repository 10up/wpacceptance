<?php
/**
 * Acceptance tester class
 *
 * @package  wpassure
 */

namespace WPAssure;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * Acceptance tester class
 */
class AcceptanceTester {

	/**
	 * Facebook WebDrive instance
	 *
	 * @var RemoteWebDriver
	 */
	protected $drive;

	/**
	 * Environment instance
	 *
	 * @var Environment
	 */
	protected $environment;

	/**
	 * Create acceptance tester
	 *
	 * @param Environment $environment Environment object
	 */
	public function __construct( Environment $environment ) {
		$host = $environment->getSeleniumServerUrl();
		$capabilities = DesiredCapabilities::chrome();

		$this->driver      = RemoteWebDriver::create( $host, $capabilities, 20000 );
		$this->environment = $environment;
	}

	/**
	 * Navigate to a specific page given a path
	 *
	 * @param  string $url_path Path to navigate to
	 * @return [type]
	 */
	public function amOnPage( $url_path ) {
		$url_parts = parse_url( $url_path );

		$path = $url_parts['path'];

		if ( empty( $path ) ) {
			$path = '/';
		} elseif ( '/' !== substr( $path, 0, 1 ) ) {
			$path = '/' . $path;
		}

		$page = $this->environment->getWpHomepageUrl() . $path;

		Log::instance()->write( 'Navigating to URL: ' . $page, 1 );

		return $this->driver->navigate()->to( $page );
	}

	/**
	 * Take screenshot
	 *
	 * @return [type]
	 */
	public function takeScreenshot() {
		return $this->driver->takeScreenshot( 'screenshot.jpg' );
	}

	public function see() {

	}

	public function click() {

	}

}
