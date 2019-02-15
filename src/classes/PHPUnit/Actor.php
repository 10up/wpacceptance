<?php
/**
 * An Actor is used in a test to interact with the website
 *
 * @package  wpacceptance
 */

namespace WPAcceptance\PHPUnit;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

use WPAcceptance\Exception;
use WPAcceptance\Log;
use WPAcceptance\Utils;
use WPAcceptance\Exception\PageNotSet;
use WPAcceptance\Exception\ElementNotFound;
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

use Nesk\Rialto\Data\JsFunction;
use Nesk\Puphpeteer\Resources\ElementHandle;

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

	/**
	 * Current page
	 */
	private $page;

	private $page_response;

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
	public function __construct( string $name = 'user' ) {
		$this->name = $name;
	}

	public function setPage( $page ) {
		$this->page = $page;
	}

	/**
	 * Set actor name.
	 *
	 * @access public
	 * @param string $name Actor name.
	 */
	public function setActorName( string $name ) {
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

	public function getPage() {
		if ( ! $this->page ) {
			throw new PageNotSet( 'Page is not set.' );
		}

		return $this->page;
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
	protected function assertThat( $constraint, string $message = '' ) {
		TestCase::assertThat( $this, $constraint, $message );
	}

	/**
	 * Return a page source.
	 *
	 * @access public
	 * @return string A page source.
	 */
	public function getPageSource() {
		if ( empty( $this->page_response ) ) {
			throw new PageNotSet( 'Page is not set.' );
		}

		return $this->page_response->text();
	}

	/**
	 * Get current page title
	 *
	 * @access public
	 * @return  string
	 */
	public function getPageTitle() {
		return $this->getPage()->title();
	}

	/**
	 * Scroll in the browser
	 *
	 * @param  int $x X browser coordinate
	 * @param  int $y Y browser coordinate
	 */
	public function scrollTo( int $x, int $y ) {
		$this->executeJavaScript( 'window.scrollTo(' . $x . ', ' . $y . ')' );
	}

	/**
	 * Scroll to element
	 */
	public function scrollToElement( string $element_path ) {
		$this->executeJavaScript( 'document.querySelector("' . $element_path . '").scrollIntoView();' );
	}

	/**
	 * Execute javascript
	 *
	 * @param  string $script JS code
	 * @return  mixed Can be whatever JS returns
	 */
	public function executeJavaScript( string $script ) {
		return $this->getPage()->evaluate( JsFunction::createWithBody( $script ) );
	}

	/**
	 * Directly set element attribute via JS
	 *
	 * @param string $element_path    Element path
	 * @param string $attribute_name  Attribute name
	 * @param string $attribute_value Attribute value
	 */
	public function setElementAttribute( string $element_path, string $attribute_name, $attribute_value ) {
		$this->executeJavaScript( 'window.document.querySelector("' . addcslashes( $element_path, '"' ) . '").setAttribute("' . addcslashes( $attribute_name, '"' ) . '", "' . addcslashes( $attribute_value, '"' ) . '")' );
	}

	/**
	 * Take a screenshot of the viewport
	 *
	 * @access public
	 * @param string $name A filename without extension.
	 */
	public function takeScreenshot( string $name = null ) {
		if ( empty( $name ) ) {
			$name = uniqid( date( 'Y-m-d_H-i-s_' ) );
		}

		$filename = $name . '.jpg';
		$this->getPage()->screenshot( [ 'path' => $filename ] );
		Log::instance()->write( 'Screenshot saved to ' . $filename, 1 );
	}

	/**
	 * Move back to the previous page in the history.
	 *
	 * @access public
	 */
	public function moveBack() {
		$this->getPage()->goBack();
		Log::instance()->write( 'Back to ' . $web_driver->getCurrentURL(), 1 );
	}

	/**
	 * Move forward to the next page in the history.
	 *
	 * @access public
	 */
	public function moveForward() {
		$this->getPage()->goForward();
		Log::instance()->write( 'Forward to ' . $web_driver->getCurrentURL(), 1 );
	}

	/**
	 * Refresh the current page.
	 *
	 * @access public
	 */
	public function refresh() {
		$this->getPage()->reload();
		Log::instance()->write( 'Refreshed the current page', 1 );
	}

	/**
	 * Move mouse to element
	 */
	public function moveMouse( $element ) {
		$element = $this->getElement( $element );

		if ( empty( $element ) ) {
			return false;
		}

		$bounding_box = $element->boundingBox();

		$this->getPage()->mouse->move( $bounding_box['x'], $bounding_box['y'] );
	}

	/**
	 * Navigate to a new URL.
	 *
	 * @access public
	 * @param string $url_or_path Path (relative to main site in network) or full url
	 * @param int    $blog_id Optional blog id
	 */
	public function moveTo( string $url_or_path, int $blog_id = null ) {

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

		$this->page_response = $this->page->goto( $url, [ 'waitUntil' => 'domcontentloaded' ] );

		Log::instance()->write( 'Navigating to URL: ' . $url, 1 );
	}

	/**
	 * Resize viewport to a new dimension.
	 *
	 * @access public
	 * @param int $width A new width.
	 * @param int $height A new height.
	 */
	public function resizeViewport( int $width, int $height ) {
		$this->getPage()->setViewport(
			[
				'width'  => $width,
				'height' => $height,
			]
		);
	}

	/**
	 * Assert that the actor sees a cookie.
	 *
	 * @access public
	 * @param string $name The cookie name.
	 * @param mixed  $value Optional. The cookie value. If it's empty, value check will be ignored.
	 * @param string $message Optional. The message to use on a failure.
	 */
	public function seeCookie( string $name, $value = null, $message = '' ) {
		$this->assertThat(
			new CookieConstrain( Constraint::ACTION_SEE, $name, $value ),
			$message
		);
	}

	public function waitUntilNavigation( $condition = 'networkidle0' ) {
		return $this->getPage()->waitForNavigation( [ 'waitUntil' => $condition ] );
	}

	/**
	 * Wait until element is enabled
	 *
	 * @param  string $element_path Path to element to check
	 */
	public function waitUntilElementEnabled( string $element_path ) {
		$this->getPage()->waitForFunction( JsFunction::createWithBody( 'return ! document.querySelector("' . addcslashes( $element_path, '"' ) . '").disabled' ) );
	}

	/**
	 * Wait until element contains text
	 *
	 * @param  string  $text     Title string
	 * @param  string  $element_path Path to element to check
	 */
	public function waitUntilElementContainsText( $text, string $element_path ) {
		// Wait for element to exist
		$this->getPage()->waitForSelector( $element_path );

		// Wait for element to contain text
		$this->getPage()->waitForFunction( JsFunction::createWithBody( 'return document.querySelector("' . addcslashes( $element_path, '"' ) . '").innerText.includes("' . addcslashes( $text, '"' ) . '")' ) );
	}

	/**
	 * Wait until title contains
	 */
	public function waitUntilTitleContains( $title ) {
		$this->getPage()->waitForFunction( JsFunction::createWithBody( 'return document.title.includes("' . addcslashes( $title, '"' ) . '")' ) );
	}

	/**
	 * Wait until element is visible
	 *
	 * @param  string  $element_path Path to element to check
	 * @param  integer $max_wait  Max wait time in seconds
	 */
	public function waitUntilElementVisible( string $element_path ) {
		$this->getPage()->waitForSelector( $element_path, [ 'visible' => true ] );
	}

	/**
	 * Wait until page source contains text
	 */
	public function waitUntilPageSourceContains( $text ) {
		$this->getPage()->waitForFunction( JsFunction::createWithBody( 'return document.documentElement.outerHTML.includes("' . addcslashes( $text, '"' ) . '")' ) );
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
		$params['name']  = (string) $name;
		$params['value'] = (string) $value;

		if ( ! isset( $params['domain'] ) ) {
			$params['domain'] = '.' . parse_url( $this->test->getWPHomeUrl(), PHP_URL_HOST );
		}

		$this->getPage()->setCookie( $params );
	}

	/**
	 * Return value of a cookie.
	 *
	 * @access public
	 * @param string $name A cookie name.
	 * @return mixed A cookie value.
	 */
	public function getCookie( $name ) {
		$cookies = $this->getCookies();

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
		return $this->getPage()->cookies();
	}

	/**
	 * Delete a cookie with the give name.
	 *
	 * @access public
	 * @param string $name A cookie name to reset.
	 */
	public function deleteCookie( $name ) {
		$this->getPage()->deleteCookies( [ 'name' => $name ] );
	}

	/**
	 * Get element containing text
	 *
	 * @param  string $text Text to search for
	 */
	public function getElementContaining( $text ) {
		return $this->getPage()->querySelectorXPath( "//*[contains(text(), '" . $text . "')]" );
	}

	/**
	 * Return an element based on CSS selector.
	 *
	 * @access public
	 */
	public function getElement( $element ) {
		if ( $element instanceof ElementHandle ) {
			return $element;
		}

		$element = $this->getPage()->querySelector( $element );

		if ( empty( $element ) ) {
			throw ElementNotFound( 'Element not found.' );
		}

		return $element;
	}

	/**
	 * Return elements based on CSS selector.
	 *
	 * @access public
	 */
	public function getElements( $elements ) {
		if ( is_array( $elements ) ) {
			$items = [];

			foreach ( $elements as $element ) {
				if ( $element instanceof ElementHandle ) {
					$items[] = $element;
				} else {
					$items[] = $this->getElement( $lement );
				}
			}

			return $items;
		}

		return $this->getPage()->querySelectorAll( $elements );
	}

	/**
	 * Click an element.
	 *
	 * @access public
	 */
	public function click( $element ) {
		$element = $this->getElement( $element );

		$element->click();
	}

	/**
	 * Select option by value of a dropdown element.
	 *
	 * @access public
	 */
	public function selectOptionByValue( string $element_path, $option_value ) {
		$element = $this->getElement( $element_path );

		$this->getPage()->select( $element_path, $option_value );
	}

	/**
	 * Submit a form from an element.
	 *
	 * @access public
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
	public function loginAs( string $username, string $password = 'password' ) {
		$this->moveTo( 'wp-login.php' );

		$this->setElementAttribute( '#user_login', 'value', $username );

		usleep( 100 );

		$this->setElementAttribute( '#user_pass', 'value', $password );

		usleep( 100 );

		$this->click( '#wp-submit', true );

		$this->waitUntilElementVisible( '#wpadminbar' );
	}

	/**
	 * Check a checkbox or radio input.
	 *
	 * @access public
	 */
	public function checkOptions( $elements ) {
		$elements = $this->getElements( $elements );

		foreach ( $elements as $element ) {
			$type = $element->attribute( 'type' );

			if ( in_array( $type, array( 'checkbox', 'radio' ), true ) && empty( $element->getProperty( 'checked' ) ) && empty( $element->getProperty( 'selected' ) ) ) {
				$element->click();
			}
		}
	}

	/**
	 * Uncheck a checkbox.
	 *
	 * @access public
	 */
	public function uncheckOptions( $elements ) {
		$elements = $this->getElements( $elements );

		foreach ( $elements as $element ) {
			$type = $element->attribute( 'type' );

			if ( 'checkbox' === $type && ! empty( $element->getProperty( 'checked' ) ) ) {
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
	 */
	public function fillField( string $element_path, string $value ) {
		$this->getPage()->type( $element_path, $value, [ 'delay' => 20 ] );
	}

	/**
	 * Clear the value of a textarea or an input fields.
	 *
	 * @access public
	 */
	public function clearField( string $element_path ) {
		$this->fillField( $element_path, '' );
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
	 */
	public function seeElement( $element ) {
		TestCase::assertTrue( $this->elementIsVisible( $element ), $this->elementToString( $element ) . ' is not visible.' );
	}

	/**
	 * Check if the actor doesnt see an element on the current page. Element must not be visible to human eye.
	 *
	 * @access public
	 */
	public function dontSeeElement( $element, $message = '' ) {
		TestCase::assertFalse( $this->elementIsVisible( $element ), $this->elementToString( $element ) . ' is visible.' );
	}

	/**
	 * Check if the actor sees a text on the current page. You can use a regular expression to check a text.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: "/test/i".
	 *
	 * @access public
	 */
	public function seeText( $text, $element = null ) {
		if ( empty( $element ) ) {
			$element = $this->getElement( 'body' );
		}

		$content = trim( $this->getElementInnerText( $element ) );

		if ( empty( $content ) ) {
			return false;
		}

		TestCase::assertTrue( Utils\find_match( $content, $text ), $text . ' not found.' );
	}

	/**
	 * Check if the actor can't see a text on the current page. You can use a regular expression to check a text.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: "/test/i".
	 *
	 * @access public
	 */
	public function dontSeeText( $text, $element = null ) {
		if ( empty( $element ) ) {
			$element = $this->getElement( 'body' );
		}

		$content = trim( $this->getElementInnerText( $element ) );

		if ( empty( $content ) ) {
			return true;
		}

		TestCase::assertFalse( Utils\find_match( $content, $text ), $text . ' found.' );
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
	 * @access public
	 * @param string $key A key to press.
	 */
	public function pressKey( $element, $key ) {
		$element = $this->getElement( $element );

		if ( ! empty( $element ) ) {
			$element->press( $key );
		}
	}

	/**
	 * Press "enter" key on an element.
	 *
	 * @access public
	 */
	public function pressEnterKey( $element ) {
		$this->pressKey( $element, 'Enter' );
	}

	/**
	 * Return current active element.
	 *
	 * @access public
	 * @return \Facebook\WebDriver\Remote\RemoteWebElement An instance of web elmeent.
	 */
	public function getActiveElement() {
		return $this->getPage()->evaluate( JsFunction::createWithBody( 'return document.activeElement;' ) );
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
		return $this->getPage()->url();
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

	/**
	 * Convert an element to a piece of a failure message.
	 *
	 * @access protected
	 * @param ElementHandle|string $element An element to convert.
	 * @return string A message.
	 */
	public function elementToString( $element ) {
		$element = $this->getElement( $element );

		$message = $this->getElementTagName( $element );

		$id = trim( $this->getElementAttribute( $element, 'id' ) );
		if ( ! empty( $id ) ) {
			$message .= '#' . $id;
		}

		$class = trim( $this->getElementAttribute( $element, 'class' ) );

		if ( ! empty( $class ) ) {
			$classes = array_filter( array_map( 'trim', explode( ' ', $class ) ) );
			if ( ! empty( $classes ) ) {
				$message .= '.' . implode( '.', $classes );
			}
		}

		return $message;
	}

	public function elementIsVisible( $element ) {
		$element = $this->getElement( $element );

		return $this->getPage()->evaluate(
			JsFunction::createWithParameters( [ 'element' ] )
			->body(
				"
			    var style = window.getComputedStyle( element );
			    return style && style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
				"
			),
			$element
		);
	}

	public function getElementTagName( $element ) {
		$element = $this->getElement( $element );

		return $this->getPage()->evaluate(
			JsFunction::createWithParameters( [ 'element' ] )
			->body(
				'
			    return element.tagName;
				'
			),
			$element
		);
	}

	public function getElementAttribute( $element, string $attribute ) {
		$element = $this->getElement( $element );

		return $this->getPage()->evaluate(
			JsFunction::createWithParameters( [ 'element', 'attribute' ] )
			->body(
				'
			    return element.getAttribute( attribute );
				'
			),
			$element,
			$attribute
		);
	}

	public function getElementInnerText( $element ) {
		$element = $this->getElement( $element );

		return $this->getPage()->evaluate(
			JsFunction::createWithParameters( [ 'element' ] )
			->body(
				'
			    return element.innerText;
				'
			),
			$element
		);
	}

}
