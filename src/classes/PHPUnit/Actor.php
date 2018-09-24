<?php
/**
 * An Actor is used in a test to interact with the website
 *
 * @package  wpassure
 */

namespace WPAssure\PHPUnit;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverExpectedCondition;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

use WPAssure\Exception;
use WPAssure\Log;
use WPAssure\Utils;
use WPAssure\EnvironmentFactory;
use WPAssure\PHPUnit\Constraint;
use WPAssure\PHPUnit\Constraints\Cookie as CookieConstrain;
use WPAssure\PHPUnit\Constraints\PageContains as PageContainsConstrain;
use WPAssure\PHPUnit\Constraints\PageSourceContains as PageSourceContainsConstrain;
use WPAssure\PHPUnit\Constraints\LinkOnPage as LinkOnPageConstrain;
use WPAssure\PHPUnit\Constraints\ElementVisible as ElementVisibleConstrain;
use WPAssure\PHPUnit\Constraints\UrlContains as UrlContainsConstrain;
use WPAssure\PHPUnit\Constraints\CheckboxChecked as CheckboxCheckedConstrain;
use WPAssure\PHPUnit\Constraints\FieldValueContains as FieldValueContainsConstrain;
use WPAssure\PHPUnit\Constraints\FieldInteractable as FieldInteractableConstrain;
use WPAssure\PHPUnit\Constraints\NewDatabaseEntry as NewDatabaseEntry;

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
	private $webdriver = null;

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
	 * @param \Facebook\WebDriver\Remote\RemoteWebDriver $webdriver A web driver instance.
	 */
	public function setWebDriver( $webdriver ) {
		$this->webdriver = $webdriver;
	}

	/**
	 * Return a web driver instance associated with the actor.
	 *
	 * @access public
	 * @throws Exception if a web driver is not assigned.
	 * @return \Facebook\WebDriver\Remote\RemoteWebDriver An instance of a web driver.
	 */
	public function getWebDriver() {
		if ( ! $this->webdriver ) {
			throw new Exception( 'WebDriver is not provided.' );
		}

		return $this->webdriver;
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
	 * @param \WPAssure\PHPUnit\Constraint $constraint An instance of constraint class.
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
		$this->executeJavaScript( 'window.document.querySelector("' . $element_path . '").setAttribute("' . $attribute_name . '", "' . $attribute_value . '")' );
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
	 * @param string $url_path URL path
	 */
	public function moveTo( $url_path ) {

		$url_parts = parse_url( $url_path );

		$path = $url_parts['path'];

		if ( empty( $path ) ) {
			$path = '/';
		} elseif ( '/' !== substr( $path, 0, 1 ) ) {
			$path = '/' . $path;
		}

		$page = $this->test->getWordPressUrl() . $path;

		if ( ! empty( $url_parts['query'] ) ) {
			$page .= '?' . $url_parts['query'];
		}

		$webdriver = $this->getWebDriver();
		$webdriver->get( $page );

		Log::instance()->write( 'Navigating to URL: ' . $page, 1 );
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
	 * Wait until element contains text
	 *
	 * @param  string  $text     Title string
	 * @param  string  $element_path Path to element to check
	 * @param  integer $max_wait  Max wait time in seconds
	 */
	public function waitUntilElementContainsText( $text, $element_path, $max_wait = 10 ) {
		$webdriver = $this->getWebDriver();

		$webdriver->wait( $max_wait )->until( WebDriverExpectedCondition::textToByPresentInElement( WebDriverBy::cssSelector( $element_path ), $text ) );
	}

	/**
	 * Wait until title contains
	 *
	 * @param  string  $title     Title string
	 * @param  integer $max_wait  Max wait time in seconds
	 */
	public function waitUntilTitleContains( $title, $max_wait = 10 ) {
		$webdriver = $this->getWebDriver();

		$webdriver->wait( $max_wait )->until( WebDriverExpectedCondition::titleContains( $title ) );
	}

	/**
	 * Wait until element is visible
	 *
	 * @param  string  $element_path Path to element to check
	 * @param  integer $max_wait  Max wait time in seconds
	 */
	public function waitUntilElementVisible( $element_path, $max_wait = 10 ) {
		$webdriver = $this->getWebDriver();

		$webdriver->wait( $max_wait )->until( WebDriverExpectedCondition::visibilityOfElementLocated( WebDriverBy::cssSelector( $element_path ) ) );
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
		$webdriver = $this->getWebDriver();

		$params['name']  = $name;
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
		$cookies   = $webdriver->manage()->getCookies();
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
	 * @throws ExpectationFailedException When the element is not found on the page.
	 * @param  \Facebook\WebDriver\Remote\RemoteWebElement|\Facebook\WebDriver\WebDriverBy|string $element A CSS selector for the element.
	 * @return \Facebook\WebDriver\Remote\RemoteWebElement An element instance.
	 */
	public function getElement( $element ) {
		if ( $element instanceof RemoteWebElement ) {
			return $element;
		}

		$webdriver = $this->getWebDriver();
		$by        = $element instanceof WebDriverBy ? $element : WebDriverBy::cssSelector( $element );

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

		$webdriver = $this->getWebDriver();
		$by        = $elements instanceof WebDriverBy ? $elements : WebDriverBy::cssSelector( $elements );

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
	 * Login as a certain user
	 *
	 * @param  string $username Username
	 */
	public function loginAs( $username ) {
		$this->moveTo( 'wp-login.php' );

		$this->waitUntilElementVisible( '#user_login' );

		$this->setElementAttribute( '#user_login', 'value', $username );

		usleep( 100 );

		$this->setElementAttribute( '#user_pass', 'value', 'password' );

		usleep( 100 );

		$this->click( '#wp-submit' );

		$this->waitUntilElementVisible( '#wpadminbar' );
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
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|array $element A remote elemente or CSS selector.
	 */
	public function uncheckOptions( $element ) {
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
