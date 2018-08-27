<?php

namespace WPAssure\PHPUnit;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\Exception\NoSuchElementException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use WPAssure\Exception;

class Actor {

	/**
	 * Facebook WebDrive instance
	 *
	 * @access private
	 * @var \Facebook\WebDriver\Remote\RemoteWebDriver
	 */
	private $_webdriver = null;

	/**
	 * Environment instance
	 *
	 * @access private
	 * @var \WPAssure\Environment
	 */
	private $_environment = null;

	/**
	 * Test case instance.
	 *
	 * @access private
	 * @var \PHPUnit\Framework\TestCase
	 */
	private $_test = null;

	/**
	 * Set a new instance of a web driver.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebDriver $webdriver A web driver instance.
	 */
	public function setWebDriver( $webdriver ) {
		$this->_webdriver = $webdriver;
	}

	/**
	 * Return a web driver instance associated with the actor.
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
	 * Set environment instance.
	 *
	 * @access public
	 * @param \WPAssure\Environment $environment Environment instance.
	 */
	public function setEnvironment( \WPAssure\Environment $environment ) {
		$this->_environment = $environment;
	}

	/**
	 * Return current environment instance.
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
	 * Set a new instance of PHPUnit test case.
	 *
	 * @access public
	 * @param \PHPUnit\Framework\TestCase $test A test case instance.
	 */
	public function setTest( TestCase $test ) {
		$this->_test = $test;
	}

	/**
	 * Return an instance of a test case associated with the actor.
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

	/**
	 * Return a new actor that is initialized on a specific page.
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

		$webdriver = $this->getWebDriver();
		$webdriver->get( $page );

		Log::instance()->write( 'Navigating to URL: ' . $page, 1 );
	}

	/**
	 * Return a page source.
	 *
	 * @access public
	 * @return string A page source.
	 */
	public function getPageSource() {
		return $this->getWebDriver()->getPageSource();
	}

	/**
	 * Accept the current native popup window created by window.alert, window.confirm,
	 * window.prompt fucntions.
	 *
	 * @access public
	 */
	public function acceptPopup() {
		$this->getWebDriver()->switchTo()->alert()->accept();
		Log::instance()->write( 'Accepted the current popup.', 1 );
	}

	/**
	 * Dismiss the current native popup window created by window.alert, window.confirm,
	 * window.prompt fucntions.
	 *
	 * @access public
	 */
	public function cancelPopup() {
		$this->getWebDriver()->switchTo()->alert()->dismiss();
		Log::instance()->write( 'Dismissed the current popup.', 1 );
	}

	/**
	 * Take a screenshot of the viewport.
	 *
	 * @access public
	 * @param string $name A filename without extension.
	 */
	public function takeScreenshot( $name = null ) {
		if ( empty( $name ) ) {
			$name = uniqid( date( 'Y-m-d_H-i-s_' ) );
		}

		$filename = $name . '.jpg';
		$this->getWebDriver()->takeScreenshot( $filename );
		Log::instance()->write( 'Screenshot saved to ' . $filename, 1 );
	}

	/**
	 * Move back to the previous page in the history.
	 *
	 * @access public
	 */
	public function moveBack() {
		$webdriver = $this->getWebDriver();
		$webdriver->navigate()->back();
		Log::instance()->write( 'Back to ' . $webdriver->getCurrentURL(), 1 );
	}

	/**
	 * Move forward to the next page in the history.
	 *
	 * @access public
	 */
	public function moveForward() {
		$webdriver = $this->getWebDriver();
		$webdriver->navigate()->forward();
		Log::instance()->write( 'Forward to ' . $webdriver->getCurrentURL(), 1 );
	}

	/**
	 * Refresh the current page.
	 *
	 * @access public
	 */
	public function refresh() {
		$this->getWebDriver()->navigate()->refresh();
		Log::instance()->write( 'Refreshed the current page', 1 );
	}

	/**
	 * Navigate to a new URL.
	 *
	 * @access public
	 * @param string $url A new URl.
	 */
	public function moveTo( $url ) {
		$webdriver = $this->getWebDriver();
		$webdriver->navigate()->to( $url );
		Log::instance()->write( 'Navigate to ' . $webdriver->getCurrentURL(), 1 );
	}

	/**
	 * Resize window to a new dimension.
	 *
	 * @access public
	 * @param int $width A new width.
	 * @param int $height A new height.
	 */
    public function resizeWindow( $width, $height ) {
		$dimension = new \Facebook\WebDriver\WebDriverDimension( $width, $height );

		$webdriver = $this->getWebDriver();
        $webdriver->manage()->window()->setSize( $dimension );
    }

    /**
     * Set a specific cookie.
     *
     * @access public
	 * @param string $name A name of a cookie.
	 * @param string $value Value for a cookie.
	 * @param array $params Additional parameters for a cookie.
	 */
    public function setCookie( $name, $value, array $params = array() ) {
		$webdriver = $this->getWebDriver();

		$params['name'] = $name;
        $params['value'] = $value;

        if ( ! isset( $params['domain'] ) ) {
            $params['domain'] = parse_url( $webdriver->getCurrentURL(), PHP_URL_HOST );
        }

        $webdriver->manage()->addCookie( $params );
    }

	/**
	 * Return value of a cookie.
	 *
	 * @access public
	 * @param string $name A cookie name.
	 * @return mixed A cookie value.
	 */
    public function getCookie( $name ) {
		$webdriver = $this->getWebDriver();
		$cookies = $webdriver->manage()->getCookies();
		foreach ( $cookies as $cookie ) {
			if ( $cookie['name'] === $name ) {
				return $cookie['value'];
			}
		}

        return null;
    }

    /**
     * Delete a cookie with the give name.
     *
	 * @access public
     * @param string $name A cookie name to reset.
     */
    public function resetCookie( $name ) {
		$webdriver = $this->getWebDriver();
        $webdriver->manage()->deleteCookieNamed( $name );
    }

	/**
	 * Return an element based on CSS selector.
	 *
	 * @access public
	 * @param string $element A CSS selector for the element.
	 * @return \Facebook\WebDriver\Remote\RemoteWebElement An element instance.
	 * @throws \PHPUnit\Framework\ExpectationFailedException when the element is not found on the page.
	 */
	public function getElement( $element ) {
		try {
			if ( $element instanceof \Facebook\WebDriver\Remote\RemoteWebElement ) {
				return $element;
			}

			return $this->getWebDriver()->findElement( WebDriverBy::cssSelector( $element ) );
		} catch ( NoSuchElementException $e ) {
			throw new ExpectationFailedException( "No element found at {$element}" );
		}
	}

	/**
	 * Click an element.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 */
	public function click( $element ) {
		$this->getElement( $element )->click();
	}

	/**
	 * Select options of a dropdown element.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 * @param string|array $options Single or multiple options to select.
	 */
	public function selectOption( $element, $options ) {
		$element = $this->getElement( $element );
		if ( $element->getTagName() === 'select' ) {
			$select = new WebDriverSelect( $element );
			if ( $select->isMultiple() ) {
				$select->deselectAll();
			}

			if ( ! is_array( $options ) ) {
				$options = array( $options );
			}

			foreach ( $options as $option ) {
				// try to select an option by value
				try {
					$select->selectByValue( $option );
					continue;
				} catch( NoSuchElementException $e ) {
				}

				// try to select an option by visible text
				try {
					$select->selectByVisibleText( $option );
					continue;
				} catch ( NoSuchElementException $e ) {
				}

				// try to select an option by visible partial text
				try {
					$select->selectByVisiblePartialText( $option );
					continue;
				} catch ( NoSuchElementException $e ) {
				}

				// fallback to select by index
				try {
					$select->selectByIndex( $option );
				} catch ( NoSuchElementException $e ) {
				}
			}
		}
	}

	/**
	 * Unselect options of a dropdown element.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 * @param string|array $options Single or multiple options to deselect.
	 */
	public function deselectOption( $element, $options ) {
		$element = $this->getElement( $element );
		if ( $element->getTagName() === 'select' ) {
			$select = new WebDriverSelect( $element );

			if ( ! is_array( $options ) ) {
				$options = array( $options );
			}

			foreach ( $options as $option ) {
				// try to deselect an option by value
				try {
					$select->deselectByValue( $option );
					continue;
				} catch( NoSuchElementException $e ) {
				}

				// try to deselect an option by visible text
				try {
					$select->deselectByVisibleText( $option );
					continue;
				} catch ( NoSuchElementException $e ) {
				}

				// try to deselect an option by visible partial text
				try {
					$select->deselectByVisiblePartialText( $option );
					continue;
				} catch ( NoSuchElementException $e ) {
				}

				// fallback to deselect by index
				try {
					$select->deselectByIndex( $option );
				} catch ( NoSuchElementException $e ) {
				}
			}
		}
	}

	/**
	 * Check a checkbox or radio input.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 */
	public function checkOption( $element ) {
		$element = $this->getElement( $element );
		$type = $element->getAttribute( 'type' );
		if ( in_array( $type, array( 'checkbox', 'radio' ) ) && ! $element->isSelected() ) {
			$element->click();
		}
	}

	/**
	 * Uncheck a checkbox.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 */
	public function uncheckOption( $element ) {
		$element = $this->getElement( $element );
		$type = $element->getAttribute( 'type' );
		if ( $type === 'checkbox' && $element->isSelected() ) {
			$element->click();
		}
	}

	/**
	 * Set a value for a field.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 * @param string $value A new value.
	 */
	public function fillField( $element, $value ) {
		$element = $this->getElement( $element );
		$element->clear();
		$element->sendKeys( (string) $value );
	}

	/**
	 * Clear the value of a textarea or an input field.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 */
	public function clearField( $element ) {
		$this->getElement( $element )->clear();
	}

	/**
	 * Attach a file to a field.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 * @param string $file A path to a file.
	 */
	public function attachFile( $element, $file ) {
		$detector = new \Facebook\WebDriver\Remote\LocalFileDetector();
		$element = $this->getElement( $element );
		$element->setFileDetector( $detector );
		$element->sendKeys( $file );
	}

}
