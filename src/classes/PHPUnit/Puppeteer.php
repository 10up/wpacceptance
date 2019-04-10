<?php
/**
 * Functionality for setting up puppeteer. See https://github.com/nesk/PuPHPeteer
 *
 * @package  wpacceptance
 */

namespace WPAcceptance\PHPUnit;

use WPAcceptance\Log;
use WPAcceptance\EnvironmentFactory;
use WPAcceptance\GitLab;
use WPAcceptance\PHPUnit\Actor;

use Nesk\Puphpeteer\Puppeteer as Puphpeteer;

/**
 * Web Driver trait for use with PHPUnit test class
 */
trait Puppeteer {

	/**
	 * Puppeteer instance
	 *
	 * @var  object
	 */
	private $puppeteer = null;

	/**
	 * Puppeteer browser instance
	 *
	 * @var object
	 */
	private $browser = null;

	/**
	 * Instance of last actor
	 *
	 * @var Actor
	 */
	private $last_actor;

	/**
	 * Setup and initialize puppeteer
	 *
	 * @param boolean $force Force creation even if cached
	 * @return object
	 */
	protected function setupPuppeteer( $force = false ) {
		if ( empty( $this->puppeteer ) || $force ) {
			$options = [
				'idle_timeout' => 300,
				'read_timeout' => 120,
			];

			$this->puppeteer = new Puphpeteer( $options );
		}

		return $this->puppeteer;
	}

	/**
	 * Setup Browser with user defined options
	 *
	 * @param boolean $force Force creation even if cached
	 * @return object Browser instance
	 */
	protected function setupBrowser( $force = false ) {
		if ( empty( $this->browser ) || $force ) {
			$browser_args = [];
			$config       = EnvironmentFactory::get()->getSuiteConfig();

			$browser_args['slowMo'] = 5;

			if ( ! empty( $config['show_browser'] ) ) {
				$browser_args['headless'] = false;
			}

			if ( ! empty( $config['slowmo'] ) ) {
				$browser_args['slowMo'] = (int) $config['slowmo'];
			}

			if ( GitLab::get()->isGitLab() ) {
				$browser_args['args'] = [
					'--no-sandbox',
					'--disable-setuid-sandbox',
				];
			}

			$this->browser = $this->puppeteer->launch( $browser_args );
		}

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

	/**
	 * Open a page in the browser
	 *
	 * @param  array $options New page arguments
	 * @return Actor
	 */
	public function openBrowserPage( $options = [] ) {
		$this->setupPuppeteer();
		$this->setupBrowser();

		$page_args = [
			'--start-maximized',
		];

		$width = 0;

		if ( ! empty( $options['screen_size'] ) && 'small' === $options['screen_size'] ) {
			$width = 400;
		}

		$height = 0;

		try {
			$page = $this->browser->newPage( $page_args );
		} catch ( \Nesk\Rialto\Exceptions\Node\FatalException | \Nesk\Rialto\Exceptions\IdleTimeoutException $exception ) {
			$this->setupPuppeteer( true );
			$this->setupBrowser( true );

			$page = $this->browser->newPage( $page_args );
		}

		$actor = new Actor( 'Anonymous User' );
		$actor->setPage( $page );
		$actor->resizeViewport( $width, $height );
		$actor->setTest( $this );

		$this->last_actor = $actor;

		return $actor;
	}

}
