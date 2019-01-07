<?php
/**
 * An Actor is used in a test to interact with the website
 *
 * @package  wpacceptance
 */

namespace WPAcceptance\PHPUnit;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Exception\InvalidElementStateException;
use Facebook\WebDriver\Exception\UnknownServerException;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

use WPAcceptance\Exception;
use WPAcceptance\Log;
use WPAcceptance\Utils;
use WPAcceptance\EnvironmentFactory;
use WPAcceptance\PHPUnit\Constraint;
use WPAcceptance\PHPUnit\Constraints\Cookie as CookieConstrain;
use WPAcceptance\PHPUnit\Constraints\PageContains as PageContainsConstrain;
use WPAcceptance\PHPUnit\Constraints\PageSourceContains as PageSourceContainsConstrain;
use WPAcceptance\PHPUnit\Constraints\LinkOnPage as LinkOnPageConstrain;
use WPAcceptance\PHPUnit\Constraints\ElementVisible as ElementVisibleConstrain;
use WPAcceptance\PHPUnit\Constraints\UrlContains as UrlContainsConstrain;
use WPAcceptance\PHPUnit\Constraints\CheckboxChecked as CheckboxCheckedConstrain;
use WPAcceptance\PHPUnit\Constraints\FieldValueContains as FieldValueContainsConstrain;
use WPAcceptance\PHPUnit\Constraints\FieldInteractable as FieldInteractableConstrain;
use WPAcceptance\PHPUnit\Constraints\AttributeContains as AttributeContainsConstrain;
use WPAcceptance\PHPUnit\Constraints\NewDatabaseEntry as NewDatabaseEntry;

/**
 * Actor class
 */
class Actor {

	/**
	 * Actor's name.
	 *
	 * @access private
	 * @var string
	 */
	private $name;

	/**
	 * Facebook WebDrive instance.
	 *
	 * @access private
	 * @var \Facebook\WebDriver\Remote\RemoteWebDriver
	 */
	private $web_driver = null;

	/**
	 * Test case instance.
	 *
	 * @access private
	 * @var \PHPUnit\Framework\TestCase
	 */
	private $test = null;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $name Actor name.
	 */
	public function __construct( $name = 'user' ) {
		$this->name = $name;
	}

	/**
	 * Set actor name.
	 *
	 * @access public
	 * @param string $name Actor name.
	 */
	public function setActorName( $name ) {
		$this->name = $name;
	}

	/**
	 * Return actor name.
	 *
	 * @access public
	 * @return string Actor name.
	 */
	public function getActorName() {
		return $this->name;
	}

	/**
	 * Set a new instance of a web driver.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebDriver $web_driver A web driver instance.
	 */
	public function setWebDriver( $web_driver ) {
		$this->web_driver = $web_driver;
	}

	/**
	 * Return a web driver instance associated with the actor.
	 *
	 * @access public
	 * @throws Exception if a web driver is not assigned.
	 * @return \Facebook\WebDriver\Remote\RemoteWebDriver An instance of a web driver.
	 */
	public function getWebDriver() {
		if ( ! $this->web_driver ) {
			throw new Exception( 'WebDriver is not provided.' );
		}

		return $this->web_driver;
	}

	/**
	 * Set a new instance of PHPUnit test case.
	 *
	 * @access public
	 * @param \PHPUnit\Framework\TestCase $test A test case instance.
	 */
	public function setTest( TestCase $test ) {
		$this->test = $test;
	}

	/**
	 * Return an instance of a test case associated with the actor.
	 *
	 * @access public
	 * @throws Exception if a test case is not assigned.
	 * @return \PHPUnit\Framework\TestCase An instance of a test case.
	 */
	public function getTest() {
		if ( ! $this->test ) {
			throw new Exception( 'Test case is not provided.' );
		}

		return $this->test;
	}

	/**
	 * Perform assertion for a specific constraint.
	 *
	 * @access protected
	 * @param \WPAcceptance\PHPUnit\Constraint $constraint An instance of constraint class.
	 * @param string                       $message Optional. A message for a failure.
	 */
	protected function assertThat( $constraint, $message = '' ) {
		TestCase::assertThat( $this, $constraint, $message );
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
	 * Get current page title
	 *
	 * @access public
	 * @return  string
	 */
	public function getPageTitle() {
		return $this->getWebDriver()->getTitle();
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
	 * Scroll in the browser
	 *
	 * @param  int $x X browser coordinate
	 * @param  int $y Y browser coordinate
	 */
	public function scrollTo( $x, $y ) {
		$this->executeJavaScript( 'window.scrollTo(' . (int) $x . ', ' . (int) $y . ')' );
	}

	/**
	 * Scroll to element
	 *
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 */
	public function scrollToElement( $element ) {
		$element = $this->getElement( $element );

		$this->getWebDriver()->action()->moveToElement( $element )->perform();
	}

	/**
	 * Execute javascript
	 *
	 * @param  string $script JS code
	 * @return  mixed Can be whatever JS returns
	 */
	public function executeJavaScript( $script ) {
		return $this->getWebDriver()->executeScript( $script );
	}

	/**
	 * Directly set element attribute via JS
	 *
	 * @param string $element_path    Element path
	 * @param string $attribute_name  Attribute name
	 * @param string $attribute_value Attribute value
	 */
	public function setElementAttribute( $element_path, $attribute_name, $attribute_value ) {
		$this->executeJavaScript( 'window.document.querySelector("' . addcslashes( $element_path, '"' ) . '").setAttribute("' . addcslashes( $attribute_name, '"' ) . '", "' . addcslashes( $attribute_value, '"' ) . '")' );
	}

	/**
	 * Take a screenshot of the viewport
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
		$web_driver = $this->getWebDriver();
		$web_driver->navigate()->back();
		Log::instance()->write( 'Back to ' . $web_driver->getCurrentURL(), 1 );
	}

	/**
	 * Move forward to the next page in the history.
	 *
	 * @access public
	 */
	public function moveForward() {
		$web_driver = $this->getWebDriver();
		$web_driver->navigate()->forward();
		Log::instance()->write( 'Forward to ' . $web_driver->getCurrentURL(), 1 );
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
	 * Move mouse to element
	 *
	 * @param  string $element_path Path to element
	 */
	public function moveMouse( $element_path ) {
		$this->getWebDriver()->getMouse()->mouseMove( $this->getElement( $element_path )->getCoordinates() );
	}

	/**
	 * Navigate to a new URL.
	 *
	 * @access public
	 * @param string $url_or_path Path (relative to main site in network) or full url
	 * @param int    $blog_id Optional blog id
	 */
	public function moveTo( $url_or_path, $blog_id = null ) {

		$url_parts = parse_url( $url_or_path );

		if ( ! empty( $url_parts['host'] ) ) {
			// If we have full url
			$url = $url_parts['scheme'] . '://' . $url_parts['host'] . ':' . intval( EnvironmentFactory::get()->getWordPressPort() );
		} else {
			$url = $this->test->getWPHomeUrl( (int) $blog_id );
		}

		if ( ! empty( $url_parts['path'] ) ) {
			$url .= '/' . ltrim( $url_parts['path'], '/' );
		}

		if ( ! empty( $url_parts['query'] ) ) {
			$url .= '?' . $url_parts['query'];
		}

		$web_driver = $this->getWebDriver();
		$web_driver->get( $url );

		Log::instance()->write( 'Navigating to URL: ' . $url, 1 );
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

		$web_driver = $this->getWebDriver();
		$web_driver->manage()->window()->setSize( $dimension );
	}

	/**
	 * Assert that the actor sees a cookie.
	 *
	 * @access public
	 * @param string $name The cookie name.
	 * @param mixed  $value Optional. The cookie value. If it's empty, value check will be ignored.
	 * @param string $message Optional. The message to use on a failure.
	 */
	public function seeCookie( $name, $value = null, $message = '' ) {
		$this->assertThat(
			new CookieConstrain( Constraint::ACTION_SEE, $name, $value ),
			$message
		);
	}

	/**
	 * Wait until element is clickable
	 *
	 * @param  string  $element_path Path to element to check
	 * @param  integer $max_wait  Max wait time in seconds
	 */
	public function waitUntilElementClickable( $element_path, $max_wait = 10 ) {
		$web_driver = $this->getWebDriver();

		$web_driver->wait( $max_wait )->until( WebDriverExpectedCondition::elementToBeClickable( WebDriverBy::cssSelector( $element_path ) ) );
	}

	/**
	 * Wait until element contains text
	 *
	 * @param  string  $text     Title string
	 * @param  string  $element_path Path to element to check
	 * @param  integer $max_wait  Max wait time in seconds
	 */
	public function waitUntilElementContainsText( $text, $element_path, $max_wait = 10 ) {
		$web_driver = $this->getWebDriver();

		// First wait for element to exist
		$web_driver->wait( $max_wait )->until( WebDriverExpectedCondition::presenceOfElementLocated( WebDriverBy::cssSelector( $element_path ) ) );

		// Now wait for element to contain text
		$web_driver->wait( $max_wait )->until( WebDriverExpectedCondition::textToBePresentInElement( WebDriverBy::cssSelector( $element_path ), $text ) );
	}

	/**
	 * Wait until title contains
	 *
	 * @param  string  $title     Title string
	 * @param  integer $max_wait  Max wait time in seconds
	 */
	public function waitUntilTitleContains( $title, $max_wait = 10 ) {
		$web_driver = $this->getWebDriver();

		$web_driver->wait( $max_wait )->until( WebDriverExpectedCondition::titleContains( $title ) );
	}

	/**
	 * Wait until element is visible
	 *
	 * @param  string  $element_path Path to element to check
	 * @param  integer $max_wait  Max wait time in seconds
	 */
	public function waitUntilElementVisible( $element_path, $max_wait = 10 ) {
		$web_driver = $this->getWebDriver();

		$web_driver->wait( $max_wait )->until( WebDriverExpectedCondition::visibilityOfElementLocated( WebDriverBy::cssSelector( $element_path ) ) );
	}

	/**
	 * Wait until page source contains text/regex
	 *
	 * @param  string  $text Text or regex to look for
	 * @param  integer $max_wait  Max wait time in seconds
	 */
	public function waitUntilPageSourceContains( $text, $max_wait = 10 ) {
		$web_driver = $this->getWebDriver();

		$web_driver->wait( $max_wait )->until(
			function() use ( $text ) {
				$source = $this->getPageSource();

				return Utils\find_match( $source, $text );
			},
			'Error waiting for page source to contain text.'
		);
	}

	/**
	 * Wait until element is not disabled
	 *
	 * @param  string  $element_path Path to element to check
	 * @param  integer $max_wait  Max wait time in seconds
	 */
	public function waitUntilElementEnabled( $element_path, $max_wait = 10 ) {
		$web_driver = $this->getWebDriver();

		$web_driver->wait( $max_wait )->until(
			function() use ( $element_path ) {
				$element = $this->getElement( $element_path );

				return $element->isEnabled();
			},
			'Error waiting for element to be enabled.'
		);
	}

	/**
	 * Assert that the actor can't see a cookie.
	 *
	 * @access public
	 * @param string $name The cookie name.
	 * @param mixed  $value Optional. The cookie value. If it's empty, value check will be ignored.
	 * @param string $message Optional. The message to use on a failure.
	 */
	public function dontSeeCookie( $name, $value = null, $message = '' ) {
		$this->assertThat(
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
	 * @param array  $params Additional parameters for a cookie.
	 */
	public function setCookie( $name, $value, array $params = array() ) {
		$web_driver = $this->getWebDriver();

		$params['name']  = (string) $name;
		$params['value'] = (string) $value;

		if ( ! isset( $params['domain'] ) ) {
			$params['domain'] = '.' . parse_url( $this->test->getWPHomeUrl(), PHP_URL_HOST );
		}

		$web_driver->manage()->addCookie( $params );
	}

	/**
	 * Return value of a cookie.
	 *
	 * @access public
	 * @param string $name A cookie name.
	 * @return mixed A cookie value.
	 */
	public function getCookie( $name ) {
		$web_driver = $this->getWebDriver();
		$cookies    = $web_driver->manage()->getCookies();
		foreach ( $cookies as $cookie ) {
			if ( $cookie['name'] === $name ) {
				return $cookie['value'];
			}
		}

		return null;
	}

	/**
	 * Hide an element in the dom
	 *
	 * @throws ExpectationFailedException When the element is not found on the page.
	 * @param  strin $element_path A CSS selector for the element.
	 */
	public function hideElement( $element_path ) {
		$this->executeJavaScript( 'window.document.querySelector("' . addcslashes( $element_path, '"' ) . '").style.display = "none";' );
	}

	/**
	 * Get all cookies
	 *
	 * @access public
	 * @return  array
	 */
	public function getCookies() {
		$web_driver = $this->getWebDriver();
		return $web_driver->manage()->getCookies();
	}

	/**
	 * Delete a cookie with the give name.
	 *
	 * @access public
	 * @param string $name A cookie name to reset.
	 */
	public function resetCookie( $name ) {
		$web_driver = $this->getWebDriver();
		$web_driver->manage()->deleteCookieNamed( $name );
	}

	/**
	 * Get element containing text
	 *
	 * @param  string $text Text to search for
	 * @return \Facebook\WebDriver\Remote\RemoteWebElement An element instance.
	 */
	public function getElementContaining( $text ) {
		$web_driver = $this->getWebDriver();
		return $web_driver->findElement( WebDriverBy::xpath( "//*[contains(text(), '" . $text . "')]" ) );
	}

	/**
	 * Return an element based on CSS selector.
	 *
	 * @access public
	 * @throws ExpectationFailedException When the element is not found on the page.
	 * @param  \Facebook\WebDriver\Remote\RemoteWebElement|\Facebook\WebDriver\WebDriverBy|string $element A CSS selector for the element.
	 * @return \Facebook\WebDriver\Remote\RemoteWebElement An element instance.
	 */
	public function getElement( $element ) {
		if ( $element instanceof RemoteWebElement ) {
			return $element;
		}

		$web_driver = $this->getWebDriver();
		$by         = $element instanceof WebDriverBy ? $element : WebDriverBy::cssSelector( $element );

		try {
			return $web_driver->findElement( $by );
		} catch ( NoSuchElementException $e ) {
			$message = sprintf( 'No element found using %s "%s"', $by->getMechanism(), $by->getValue() );
			throw new ExpectationFailedException( $message );
		}
	}

	/**
	 * Return elements based on CSS selector.
	 *
	 * @access public
	 * @throws ExpectationFailedException When elements are not found on the page.
	 * @param \Facebook\WebDriver\WebDriverBy|array|string $elements A CSS selector for elements.
	 * @return array Array of elements.
	 */
	public function getElements( $elements ) {
		if ( is_array( $elements ) ) {
			$items = [];

			foreach ( $elements as $element ) {
				if ( $element instanceof RemoteWebElement ) {
					$items[] = $element;
				} else {
					$items[] = $this->getElement( $lement );
				}
			}

			return $items;
		}

		$web_driver = $this->getWebDriver();
		$by         = $elements instanceof WebDriverBy ? $elements : WebDriverBy::cssSelector( $elements );

		try {
			return $web_driver->findElements( $by );
		} catch ( NoSuchElementException $e ) {
			$message = sprintf( 'No elements found using %s "%s"', $by->getMechanism(), $by->getValue() );
			throw new ExpectationFailedException( $message );
		}
	}

	/**
	 * Click an element with JS.
	 *
	 * @access public
	 * @param string $element_path Path to element in DOM to click
	 */
	public function jsClick( $element_path ) {
		$element = $this->getElement( $element_path );

		$this->waitUntilElementClickable( $element_path );

		$this->waitUntilElementVisible( $element_path );

		$this->executeJavaScript( 'window.document.querySelector( "' . addcslashes( $element_path, '"' ) . '" ).click();' );
	}

	/**
	 * Click an element. Click can be buggy. Try jsClick as well.
	 *
	 * @access public
	 * @param string  $element_path Path to element in DOM to click
	 * @param boolean $expect_navigate If true, will try harder to ensure navigation occurs circumventing
	 *                                  buggy Selenium.
	 */
	public function click( $element_path, $expect_navigate = false ) {
		if ( $expect_navigate ) {
			$this->executeJavaScript( 'window.' . __FUNCTION__ . ' = 1;' );
		}

		$element = $this->getElement( $element_path );

		$this->waitUntilElementClickable( $element_path );

		$this->waitUntilElementVisible( $element_path );

		try {
			$element->sendKeys( '' );
			$this->executeJavaScript( 'window.document.querySelector( "' . $element_path . '" ).focus(); ' );
		} catch ( \Exception $e ) {
			// Just continue
		}

		try {
			$element->click();
		} catch ( UnknownServerException $e ) {
			// Weird hack to get around inconsistent click behavior
			$this->executeJavaScript( 'window.scrollTo( 0, ( window.document.documentElement.scrollTop + 100 ) )' );

			$element->click();
		}

		if ( $expect_navigate && ! empty( $this->executeJavaScript( 'return window.' . __FUNCTION__ . ' || false;' ) ) ) {
			$this->pressKey( $element, WebDriverKeys::ENTER );
		}

		if ( $expect_navigate && ! empty( $this->executeJavaScript( 'return window.' . __FUNCTION__ . ' || false;' ) ) ) {
			$element->click();
		}
	}

	/**
	 * Select options of a dropdown element.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 * @param string|array                                       $options Single or multiple options to select.
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
				} catch ( NoSuchElementException $e ) {
					// Do nothing
				}

				// try to select an option by visible text
				try {
					$select->selectByVisibleText( $option );
					continue;
				} catch ( NoSuchElementException $e ) {
					// Do nothing
				}

				// try to select an option by visible partial text
				try {
					$select->selectByVisiblePartialText( $option );
					continue;
				} catch ( NoSuchElementException $e ) {
					// Do nothing
				}

				// fallback to select by index
				try {
					$select->selectByIndex( $option );
				} catch ( NoSuchElementException $e ) {
					// Do nothing
				}
			}
		}
	}

	/**
	 * Unselect options of a dropdown element.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 * @param string|array                                       $options Single or multiple options to deselect.
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
				} catch ( NoSuchElementException $e ) {
					// Do nothing
				}

				// try to deselect an option by visible text
				try {
					$select->deselectByVisibleText( $option );
					continue;
				} catch ( NoSuchElementException $e ) {
					// Do nothing
				}

				// try to deselect an option by visible partial text
				try {
					$select->deselectByVisiblePartialText( $option );
					continue;
				} catch ( NoSuchElementException $e ) {
					// Do nothing
				}

				// fallback to deselect by index
				try {
					$select->deselectByIndex( $option );
				} catch ( NoSuchElementException $e ) {
					// Do nothing
				}
			}
		}
	}

	/**
	 * Submit a form from an element.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 */
	public function submitForm( $element ) {
		$element = $this->getElement( $element );

		$element->submit();
	}

	/**
	 * Login as a certain user
	 *
	 * @param  string $username Username
	 * @param  string $password Password
	 */
	public function loginAs( $username, $password = 'password' ) {
		static $cookies_by_username = [];

		$web_driver = $this->getWebDriver();

		if ( empty( $cookies_by_username[ $username ] ) ) {
			Log::instance()->write( 'Login not cached for ' . $username, 2 );

			$this->moveTo( 'wp-login.php' );

			$this->setElementAttribute( '#user_login', 'value', $username );

			usleep( 100 );

			$this->setElementAttribute( '#user_pass', 'value', $password );

			usleep( 100 );

			$this->click( '#wp-submit', true );

			$this->waitUntilElementVisible( '#wpadminbar' );

			$cookies_by_username[ $username ] = [];

			$cookies = $this->getCookies();

			foreach ( $cookies as $cookie ) {
				if ( preg_match( '#^(wordpress\_|wp\-)#', $cookie['name'] ) ) {
					$cookies_by_username[ $username ][] = $cookie;
				}
			}
		} else {
			Log::instance()->write( 'Login cached for ' . $username, 2 );

			foreach ( $cookies_by_username[ $username ] as $cookie ) {
				var_dump( $web_driver->manage()->addCookie( $cookie ) );
			}

			$this->moveTo( 'wp-admin' );
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
			if ( in_array( $type, array( 'checkbox', 'radio' ), true ) && ! $element->isSelected() ) {
				$element->click();
			}
		}
	}

	/**
	 * Uncheck a checkbox.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|array $elements A remote element or CSS selector.
	 */
	public function uncheckOptions( $elements ) {
		$elements = $this->getElements( $elements );

		foreach ( $elements as $element ) {
			$type = $element->getAttribute( 'type' );

			if ( 'checkbox' === $type && $element->isSelected() ) {
				$element->click();
			}
		}
	}

	/**
	 * Check if element is interactable
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 * @param string                                             $message Optional. The message to use on a failure.
	 */
	public function canInteractWithField( $element, $message = '' ) {
		$this->assertThat(
			new FieldInteractableConstrain( Constraint::ACTION_INTERACT, $element ),
			$message
		);
	}

	/**
	 * Check if element is not interactable
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 * @param string                                             $message Optional. The message to use on a failure.
	 */
	public function cannotInteractWithField( $element, $message = '' ) {
		$this->assertThat(
			new FieldInteractableConstrain( Constraint::ACTION_CANTINTERACT, $element ),
			$message
		);
	}

	/**
	 * Set a value for a field.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 * @param string                                             $value A new value.
	 */
	public function fillField( $element, $value ) {
		$element = $this->getElement( $element );
		$element->clear();
		$element->sendKeys( (string) $value );

		return $element;
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
	 * @param string                                             $file A path to a file.
	 */
	public function attachFile( $element, $file ) {
		$detector = new \Facebook\WebDriver\Remote\LocalFileDetector();
		$element  = $this->getElement( $element );
		$element->setFileDetector( $detector );
		$element->sendKeys( $file );
	}

	/**
	 * Check if the actor sees an element on the current page. Element must be visible to human eye.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A CSS selector for the element.
	 * @param string                                             $message Optional. The message to use on a failure.
	 */
	public function seeElement( $element, $message = '' ) {
		$this->assertThat(
			new ElementVisibleConstrain( Constraint::ACTION_SEE, $element ),
			$message
		);
	}

	/**
	 * Check if the actor doesnt see an element on the current page. Element must not be visible to human eye.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A CSS selector for the element.
	 * @param string                                             $message Optional. The message to use on a failure.
	 */
	public function dontSeeElement( $element, $message = '' ) {
		$this->assertThat(
			new ElementVisibleConstrain( Constraint::ACTION_DONTSEE, $element ),
			$message
		);
	}

	/**
	 * Check if the actor sees a text on the current page. You can use a regular expression to check a text.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: "/test/i".
	 *
	 * @access public
	 * @param string                                             $text A text to look for or a regular expression.
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A CSS selector for the element.
	 * @param string                                             $message Optional. The message to use on a failure.
	 */
	public function seeText( $text, $element = null, $message = '' ) {
		$this->assertThat(
			new PageContainsConstrain( Constraint::ACTION_SEE, $text, $element ),
			$message
		);
	}

	/**
	 * Check if the actor can't see a text on the current page. You can use a regular expression to check a text.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: "/test/i".
	 *
	 * @access public
	 * @param string                                             $text A text to look for or a regular expression.
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A CSS selector for the element.
	 * @param string                                             $message Optional. The message to use on a failure.
	 */
	public function dontSeeText( $text, $element = null, $message = '' ) {
		$this->assertThat(
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
		$this->assertThat(
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
		$this->assertThat(
			new PageSourceContainsConstrain( Constraint::ACTION_DONTSEE, $text ),
			$message
		);
	}

	/**
	 * Press a key on an element.
	 *
	 * @see \Facebook\WebDriver\WebDriverKeys
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 * @param string                                             $key A key to press.
	 */
	public function pressKey( $element, $key ) {
		$this->getElement( $element )->sendKeys( $key );
	}

	/**
	 * Press "enter" key on an element.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A remote element or CSS selector.
	 */
	public function pressEnterKey( $element ) {
		$this->pressKey( $element, WebDriverKeys::ENTER );
	}

	/**
	 * Return current active element.
	 *
	 * @access public
	 * @return \Facebook\WebDriver\Remote\RemoteWebElement An instance of web elmeent.
	 */
	public function getActiveElement() {
		return $this->getWebDriver()->switchTo()->activeElement();
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
		$this->assertThat(
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
		$this->assertThat(
			new LinkOnPageConstrain( Constraint::ACTION_DONTSEE, $text, $url ),
			$message
		);
	}

	/**
	 * Check if the actor can see a text in the current URL. You can use a regular expression to check the current URL.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @access public
	 * @param string $text A text to look for in the current URL.
	 * @param string $message Optional. The message to use on a failure.
	 */
	public function seeTextInUrl( $text, $message = '' ) {
		$this->assertThat(
			new UrlContainsConstrain( Constraint::ACTION_SEE, $text ),
			$message
		);
	}

	/**
	 * Check if the actor cann't see a text in the current URL. You can use a regular expression to check the current URL.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @access public
	 * @param string $text A text to look for in the current URL.
	 * @param string $message Optional. The message to use on a failure.
	 */
	public function dontSeeTextInUrl( $text, $message = '' ) {
		$this->assertThat(
			new UrlContainsConstrain( Constraint::ACTION_DONTSEE, $text ),
			$message
		);
	}

	/**
	 * Return current URL.
	 *
	 * @access public
	 * @return string The current URL.
	 */
	public function getCurrentUrl() {
		return $this->getWebDriver()->getCurrentURL();
	}

	/**
	 * Check if the current user can see a checkbox is checked.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A CSS selector for the element.
	 * @param string                                             $message Optional. The message to use on a failure.
	 */
	public function seeCheckboxIsChecked( $element, $message = '' ) {
		$this->assertThat(
			new CheckboxCheckedConstrain( Constraint::ACTION_SEE, $element ),
			$message
		);
	}

	/**
	 * Check if the current user cann't see a checkbox is checked.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A CSS selector for the element.
	 * @param string                                             $message Optional. The message to use on a failure.
	 */
	public function dontSeeCheckboxIsChecked( $element, $message = '' ) {
		$this->assertThat(
			new CheckboxCheckedConstrain( Constraint::ACTION_DONTSEE, $element ),
			$message
		);
	}

	/**
	 * Check if the user can see text inside of an attribute
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A CSS selector for the element.
	 * @param string                                             $attribute Attribute name
	 * @param string                                             $value A value to check.
	 * @param string                                             $message Optional. The message to use on a failure.
	 */
	public function seeValueInAttribute( $element, $attribute, $value, $message = '' ) {
		$this->assertThat(
			new AttributeContainsConstrain( Constraint::ACTION_SEE, $element, $attribute, $value ),
			$message
		);
	}

	/**
	 * Check if the user can not see text inside of an attribute
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A CSS selector for the element.
	 * @param string                                             $attribute Attribute name
	 * @param string                                             $value A value to check.
	 * @param string                                             $message Optional. The message to use on a failure.
	 */
	public function dontSeeValueInAttribute( $element, $attribute, $value, $message = '' ) {
		$this->assertThat(
			new AttributeContainsConstrain( Constraint::ACTION_DONTSEE, $element, $attribute, $value ),
			$message
		);
	}

	/**
	 * Check if the current user can see a value in a field. You can use a regular expression to check the value.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A CSS selector for the element.
	 * @param string                                             $value A value to check.
	 * @param string                                             $message Optional. The message to use on a failure.
	 */
	public function seeFieldValue( $element, $value, $message = '' ) {
		$this->assertThat(
			new FieldValueContainsConstrain( Constraint::ACTION_SEE, $element, $value ),
			$message
		);
	}

	/**
	 * Check if the current user can see a value in a field. You can use a regular expression to check the value.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @access public
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element A CSS selector for the element.
	 * @param string                                             $value A value to check.
	 * @param string                                             $message Optional. The message to use on a failure.
	 */
	public function dontSeeFieldValue( $element, $value, $message = '' ) {
		$this->assertThat(
			new FieldValueContainsConstrain( Constraint::ACTION_DONTSEE, $element, $value ),
			$message
		);
	}

}
