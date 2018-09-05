<?php

namespace WPAssure\PHPUnit;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

use WPAssure\Exception;
use WPAssure\PHPUnit\Constraint;
use WPAssure\PHPUnit\Constraints\Cookie as CookieConstrain;
use WPAssure\PHPUnit\Constraints\PageContains as PageContainsConstrain;
use WPAssure\PHPUnit\Constraints\PageSourceContains as PageSourceContainsConstrain;
use WPAssure\PHPUnit\Constraints\LinkOnPage as LinkOnPageConstrain;

class Actor {

	/**
	 * Actor's name.
	 *
	 * @access private
	 * @var string
	 */
	private $_name;

	/**
	 * Facebook WebDrive instance.
	 *
	 * @access private
	 * @var \Facebook\WebDriver\Remote\RemoteWebDriver
	 */
	private $_webdriver = null;

	/**
	 * Environment instance.
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
	 * Constructor.
	 *
	 * @access public
	 * @param string $name Actor name.
	 */
	public function __construct( $name = 'user' ) {
		$this->_name = $name;
	}

	/**
	 * Set actor name.
	 *
	 * @access public
	 * @param string $name Actor name.
	 */
	public function setActorName( $name ) {
		$this->_name = $name;
	}

	/**
	 * Return actor name.
	 *
	 * @access public
	 * @return string Actor name.
	 */
	public function getActorName() {
		return $this->_name;
	}

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
	 * Perform assertion for a specific constraint.
	 *
	 * @access protected
	 * @param \WPAssure\PHPUnit\Constraint $constraint An instance of constraint class.
	 * @param string $message Optional. A message for a failure.
	 */
	protected function _assertThat( $constraint, $message = '' ) {
		TestCase::assertThat( $this, $constraint, $message );
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
	 * Assert that the actor sees a cookie.
	 *
	 * @access public
	 * @param string $name The cookie name.
	 * @param mixed $value Optional. The cookie value. If it's empty, value check will be ignored.
	 * @param string $message Optional. The message to use on a failure.
	 */
	public function seeCookie( $name, $value = null, $message = '' ) {
		$this->_assertThat(
			new CookieConstrain( Constraint::ACTION_SEE, $name, $value ),
			$message
		);
	}

	/**
	 * Assert that the actor can't see a cookie.
	 *
	 * @access public
	 * @param string $name The cookie name.
	 * @param mixed $value Optional. The cookie value. If it's empty, value check will be ignored.
	 * @param string $message Optional. The message to use on a failure.
	 */
	public function dontSeeCookie( $name, $value = null, $message = '' ) {
		$this->_assertThat(
			new CookieConstrain( Constraint::ACTION_DONTSEE, $name, $value ),
			$message
		);
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
	 * @throws \PHPUnit\Framework\ExpectationFailedException when the element is not found on the page.
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|\Facebook\WebDriver\WebDriverBy|string $element A CSS selector for the element.
	 * @return \Facebook\WebDriver\Remote\RemoteWebElement An element instance.
	 */
	public function getElement( $element ) {
		if ( $element instanceof RemoteWebElement ) {
			return $element;
		}

		$webdriver = $this->getWebDriver();
		$by = $element instanceof WebDriverBy ? $element : WebDriverBy::cssSelector( $element );

		try {
			return $webdriver->findElement( $by );
		} catch ( NoSuchElementException $e ) {
			$message = sprintf( 'No element found using %s "%s"', $by->getMechanism(), $by->getValue() );
			throw new ExpectationFailedException( $message );
		}
	}

	/**
	 * Return elements based on CSS selector.
	 *
	 * @access public
	 * @throws \PHPUnit\Framework\ExpectationFailedException when elements are not found on the page.
	 * @param \Facebook\WebDriver\WebDriverBy|array|string $elements A CSS selector for elements.
	 * @return array Array of elements.
	 */
	public function getElements( $elements ) {
		if ( is_array( $elements ) ) {
			$items = array();
			foreach ( $elements as $element ) {
				if ( $element instanceof RemoteWebElement ) {
					$items[] = $element;
				}
			}

			return $items;
		}

		$webdriver = $this->getWebDriver();
		$by = $element instanceof WebDriverBy ? $element : WebDriverBy::cssSelector( $element );

		try {
			return $webdriver->findElements( $by );
		} catch ( NoSuchElementException $e ) {
			$message = sprintf( 'No elements found using %s "%s"', $by->getMechanism(), $by->getValue() );
			throw new ExpectationFailedException( $message );
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
	public function selectOptions( $element, $options ) {
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
	public function deselectOptions( $element, $options ) {
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
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|array $elements A remote elements or CSS selector.
	 */
	public function checkOptions( $elements ) {
		$elements = $this->getElements( $elements );
		foreach ( $elements as $element ) {
			$type = $element->getAttribute( 'type' );
			if ( in_array( $type, array( 'checkbox', 'radio' ) ) && ! $element->isSelected() ) {
				$element->click();
			}
		}
	}

	/**
	 * Uncheck a checkbox.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|array $element A remote elemente or CSS selector.
	 */
	public function uncheckOptions( $element ) {
		$elements = $this->getElements( $elements );
		foreach ( $elements as $element ) {
			$type = $element->getAttribute( 'type' );
			if ( $type === 'checkbox' && $element->isSelected() ) {
				$element->click();
			}
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
	 * Clear the value of a textarea or an input fields.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|array $elements A remote elements or CSS selector.
	 */
	public function clearFields( $elements ) {
		$elements = $this->getElements( $elements );
		foreach ( $elements as $element ) {
			$element->clear();
		}
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

	/**
	 * Check if the actor sees a text on the current page. You can use a regular expression to check a text.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: "/test/i".
	 *
	 * @access public
	 * @param string $text A text to look for or a regular expression.
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A CSS selector for the element.
	 * @param string $message Optional. The message to use on a failure.
	 */
	public function seeText( $text, $element = null, $message = '' ) {
		$this->_assertThat(
			new PageContainsConstrain( Constraint::ACTION_SEE, $text, $element ),
			$message
		);
	}

	/**
	 * Check if the actor can't see a text on the current page. You can use a regular expression to check a text.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: "/test/i".
	 *
	 * @access public
	 * @param string $text A text to look for or a regular expression.
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A CSS selector for the element.
	 * @param string $message Optional. The message to use on a failure.
	 */
	public function dontSeeText( $text, $element = null, $message = '' ) {
		$this->_assertThat(
			new PageContainsConstrain( Constraint::ACTION_DONTSEE, $text, $element ),
			$message
		);
	}

	/**
	 * Check if the actor sees a text in the page source. You can use a regular expression to check a text.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @access public
	 * @param string $text A text to look for or a regular expression.
	 * @param string $message Optional. The message to use on a failure.
	 */
	public function seeTextInSource( $text, $message = '' ) {
		$this->_assertThat(
			new PageSourceContainsConstrain( Constraint::ACTION_SEE, $text ),
			$message
		);
	}

	/**
	 * Check if the actor can't see a text in the page source. You can use a regular expression to check a text.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @access public
	 * @param string $text A text to look for or a regular expression.
	 * @param string $message Optional. The message to use on a failure.
	 */
	public function dontSeeTextInSource( $text, $message = '' ) {
		$this->_assertThat(
			new PageSourceContainsConstrain( Constraint::ACTION_DONTSEE, $text ),
			$message
		);
	}

	/**
	 * Check if the actor sees a link on the current page with specific text and url. You can use
	 * a regular expression to check URL in the href attribute. Please, use forward slashes to define your
	 * regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @access public
	 * @param string $text A text to find a link.
	 * @param string $url Optional. The url of the link.
	 * @param string $message Optional. The message to use on a failure.
	 */
	public function seeLink( $text, $url = '', $message = '' ) {
		$this->_assertThat(
			new LinkOnPageConstrain( Constraint::ACTION_SEE, $text, $url ),
			$message
		);
	}

	/**
	 * Check if the actor doesn't see a link on the current page with specific text and url. You can use
	 * a regular expression to check URL in the href attribute. Please, use forward slashes to define your
	 * regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @access public
	 * @param string $text A text to find a link.
	 * @param string $url Optional. The url of the link.
	 * @param string $message Optional. The message to use on a failure.
	 */
	public function dontSeeLink( $text, $url = '', $message = '' ) {
		$this->_assertThat(
			new LinkOnPageConstrain( Constraint::ACTION_DONTSEE, $text, $url ),
			$message
		);
	}

}
