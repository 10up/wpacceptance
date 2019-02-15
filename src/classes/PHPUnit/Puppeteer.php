<?php
/**
 * Functionality for setting up puppeteer. See https://github.com/nesk/PuPHPeteer
 *
 * @package  wpacceptance
 */

namespace WPAcceptance\PHPUnit;

use WPAcceptance\Log;
use WPAcceptance\EnvironmentFactory;
use WPAcceptance\PHPUnit\Actor;

use Nesk\Puphpeteer\Puppeteer as Puphpeteer;

/**
 * Web Driver trait for use with PHPUnit test class
 */
trait Puppeteer {

	/**
	 * Puppeteer instance
	 */
	private $puppeteer = null;

	private $browser = null;

	/**
	 * Instance of last actor
	 *
	 * @var Actor
	 */
	private $last_actor;

	protected function setupPuppeteer() {
		if ( empty( $this->puppeteer ) ) {
			$this->puppeteer = new Puphpeteer();
		}

		return $this->puppeteer;
	}

	protected function setupBrowser() {
		if ( empty( $this->browser ) ) {
			$browser_args = [];
			$config       = EnvironmentFactory::get()->getSuiteConfig();

			if ( ! empty( $config['show_browser'] ) ) {
				$browser_args['headless'] = false;
			}

			if ( ! empty( $config['slowmo'] ) ) {
				$browser_args['slowMo'] = (int) $config['slowmo'];
			}

			$this->browser = $this->puppeteer->launch( $browser_args );
		}

		return $this->browser;
	}

	protected function getPuppeteer() {
		return $this->puppeteer;
	}

	protected function getBrowser() {
		return $this->browser;
	}

	/**
	 * Get WordPress home URL
	 *
	 * @param  mixed $id_or_url Pass in an ID or url to get the url of another blog on the
	 *                           network. Leaving blank gets the home URL for the main blog.
	 *
	 * @return string
	 */
	public function getWPHomeUrl( $id_or_url = '' ) {
		return EnvironmentFactory::get()->getWPHomeUrl( $id_or_url );
	}

	public function openBrowserPage( $options = [] ) {
		$this->setupPuppeteer();
		$this->setupBrowser();

		$page_args = [
			'--start-maximized',
		];

		$page = $this->browser->newPage( $page_args );

		$actor = new Actor( 'Anonymous User' );
		$actor->setPage( $page );
		$actor->resizeViewport( 0, 0);
		$actor->setTest( $this );

		$this->last_actor = $actor;

		return $actor;
	}

}
