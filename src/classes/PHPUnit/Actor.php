<?php
/**
 * An Actor is used in a test to interact with the website
 *
 * @package  wpacceptance
 */

namespace WPAcceptance\PHPUnit;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

use WPAcceptance\Log;
use WPAcceptance\Utils;
use WPAcceptance\Exception\PageNotSet as PageNotSet;
use WPAcceptance\Exception\ElementNotFound as ElementNotFound;
use WPAcceptance\EnvironmentFactory;

use Nesk\Rialto\Data\JsFunction;
use Nesk\Puphpeteer\Resources\ElementHandle;

/**
 * Actor class
 */
class Actor {

	/**
	 * Actor's name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Current page
	 *
	 * @var  object
	 */
	private $page;

	/**
	 * Response to current page navigation
	 *
	 * @var object
	 */
	private $page_response;

	/**
	 * Test case instance.
	 *
	 * @var \PHPUnit\Framework\TestCase
	 */
	private $test = null;

	/**
	 * Constructor.
	 *
	 * @param string $name Actor name.
	 */
	public function __construct( string $name = 'user' ) {
		$this->name = $name;
	}

	/**
	 * Set current page
	 *
	 * @param object $page Puppeteer page
	 */
	public function setPage( $page ) {
		$this->page = $page;
	}

	/**
	 * Set actor name.
	 *
	 * @param string $name Actor name.
	 */
	public function setActorName( string $name ) {
		$this->name = $name;
	}

	/**
	 * Return actor name.
	 *
	 * @return string Actor name.
	 */
	public function getActorName() {
		return $this->name;
	}

	/**
	 * Get current Puppeteer page
	 *
	 * @throws  PageNotSet Puppeteer page not set
	 * @return object
	 */
	public function getPage() {
		if ( ! $this->page ) {
			throw new PageNotSet( 'Page is not set.' );
		}

		return $this->page;
	}

	/**
	 * Set a new instance of PHPUnit test case.
	 *
	 * @param \PHPUnit\Framework\TestCase $test A test case instance.
	 */
	public function setTest( TestCase $test ) {
		$this->test = $test;
	}

	/**
	 * Return an instance of a test case associated with the actor.
	 *
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
	 * Return a page source.
	 *
	 * @throws  PageNotSet Page not set exception
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
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 */
	public function scrollToElement( $element ) {
		$element = $this->getElement( $element );

		$this->getPage()->evaluate(
			JsFunction::createWithParameters( [ 'element' ] )
			->body(
				'element.scrollIntoView'
			),
			$element
		);
	}

	/**
	 * Execute javascript
	 *
	 * @param  string $script JS code
	 * @return mixed Can be whatever JS returns
	 */
	public function executeJavaScript( string $script ) {
		return $this->getPage()->evaluate( JsFunction::createWithBody( $script ) );
	}

	/**
	 * Directly set element attribute via JS
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @param string               $attribute_name  Attribute name
	 * @param string               $attribute_value Attribute value
	 */
	public function setElementAttribute( $element, string $attribute_name, $attribute_value ) {
		$element = $this->getElement( $element );

		$this->getPage()->evaluate(
			JsFunction::createWithParameters( [ 'element' ] )
			->body(
				'element.setAttribute( "' . addcslashes( $attribute_name, '"' ) . '", "' . addcslashes( $attribute_value, '"' ) . '" );'
			),
			$element
		);
	}

	/**
	 * Directly set element property via JS
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @param  string               $property_name Property name
	 * @param  string               $property_value Property value
	 */
	public function setElementProperty( $element, string $property_name, $property_value ) {
		$element = $this->getElement( $element );

		$this->getPage()->evaluate(
			JsFunction::createWithParameters( [ 'element' ] )
			->body(
				'element.' . $property_name . ' = "' . addcslashes( $property_value, '"' ) . '";'
			),
			$element
		);
	}

	/**
	 * Take a screenshot of the viewport
	 *
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
	 */
	public function moveBack() {
		$this->getPage()->goBack();
		Log::instance()->write( 'Back to ' . $web_driver->getCurrentURL(), 1 );
	}

	/**
	 * Move forward to the next page in the history.
	 */
	public function moveForward() {
		$this->getPage()->goForward();
		Log::instance()->write( 'Forward to ' . $web_driver->getCurrentURL(), 1 );
	}

	/**
	 * Refresh the current page.
	 */
	public function refresh() {
		$this->getPage()->reload();
		Log::instance()->write( 'Refreshed the current page', 1 );
	}

	/**
	 * Move mouse to element
	 *
	 * @param  string $element_path Path to element
	 */
	public function hover( string $element_path ) {
		$this->getPage()->hover( $element_path );
	}

	/**
	 * Move mouse to element
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
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
	 * @param string $url_or_path Path (relative to main site in network) or full url
	 * @param int    $blog_id Optional blog id
	 * @param array  $wait_until Conditions to wait for to determine if new page is loaded
	 */
	public function moveTo( string $url_or_path, int $blog_id = null, array $wait_until = [ 'domcontentloaded', 'networkidle2', 'load' ] ) {

		$url_parts = parse_url( $url_or_path );

		if ( ! empty( $url_parts['host'] ) ) {
			// If we have full url
			$url = 'http://' . $url_parts['host'] . ':' . intval( EnvironmentFactory::get()->getWordPressPort() );
		} else {
			$url = $this->test->getWPHomeUrl( (int) $blog_id );
		}

		if ( ! empty( $url_parts['path'] ) ) {
			$url .= '/' . ltrim( $url_parts['path'], '/' );
		}

		if ( ! empty( $url_parts['query'] ) ) {
			$url .= '?' . $url_parts['query'];
		}

		$args = [
			'waitUntil' => $wait_until,
			'timeout'   => 90000,
		];

		$this->page_response = $this->page->goto( $url, $args );

		Log::instance()->write( 'Navigating to URL: ' . $url, 1 );
	}

	/**
	 * Resize viewport to a new dimension.
	 *
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
	 * @param string $name The cookie name.
	 * @param mixed  $value Optional. The cookie value. If it's empty, value check will be ignored.
	 */
	public function seeCookie( string $name, $value = null ) {
		$cookie = $this->getCookie( $name );

		TestCase::assertTrue( isset( $cookie ), 'Cookie does not exist.' );

		if ( isset( $value ) ) {
			TestCase::assertEquals( $value, $cookie, 'Cookie value does not match.' );
		}
	}

	/**
	 * Assert that the actor can't see a cookie.
	 *
	 * @param string $name The cookie name.
	 * @param mixed  $value Optional. The cookie value. If it's empty, value check will be ignored.
	 */
	public function dontSeeCookie( $name, $value = null ) {
		$cookie = $this->getCookie( $name );

		TestCase::assertFalse( isset( $cookie ), 'Cookie exists.' );

		if ( isset( $value ) ) {
			TestCase::assertNotEquals( $value, $cookie, 'Cookie values match.' );
		}
	}

	/**
	 * Wait until navigation
	 *
	 * @param  string $condition Navigation condition to check
	 */
	public function waitUntilNavigation( $condition = [ 'domcontentloaded', 'networkidle2', 'load' ] ) {
		usleep( 500 );

		$args = [
			'waitUntil' => $condition,
			'timeout'   => 90000,
		];

		$this->getPage()->waitForNavigation( $args );
	}

	/**
	 * Wait until element is enabled
	 *
	 * @param  string $element_path Path to element to check
	 */
	public function waitUntilElementEnabled( string $element_path ) {
		$this->getPage()->waitForFunction( JsFunction::createWithBody( 'return ! document.querySelector("' . addcslashes( $element_path, '"' ) . '").disabled' ), [ 'timeout' => 90000 ] );
	}

	/**
	 * Wait until element contains text
	 *
	 * @param  string $text     Title string
	 * @param  string $element_path Path to element to check
	 */
	public function waitUntilElementContainsText( $text, string $element_path ) {
		// Wait for element to exist
		$this->getPage()->waitForSelector( $element_path );

		// Wait for element to contain text
		$this->getPage()->waitForFunction( JsFunction::createWithBody( 'return document.querySelector("' . addcslashes( $element_path, '"' ) . '").innerText.includes("' . addcslashes( $text, '"' ) . '")' ), [ 'timeout' => 90000 ] );
	}

	/**
	 * Wait until title contains
	 *
	 * @param  string $title Title to wait for
	 */
	public function waitUntilTitleContains( $title ) {
		$this->getPage()->waitForFunction( JsFunction::createWithBody( 'return document.title.includes("' . addcslashes( $title, '"' ) . '")' ), [ 'timeout' => 90000 ] );
	}

	/**
	 * Wait until element is visible
	 *
	 * @param  string $element_path Path to element to check
	 */
	public function waitUntilElementVisible( string $element_path ) {
		$args = [
			'visible' => true,
			'timeout' => 90000,
		];

		$this->getPage()->waitForSelector( $element_path, $args );
	}

	/**
	 * Wait until page source contains text
	 *
	 * @param  string $text Text to wait for
	 */
	public function waitUntilPageSourceContains( $text ) {
		$this->getPage()->waitForFunction( JsFunction::createWithBody( 'return document.documentElement.outerHTML.includes("' . addcslashes( $text, '"' ) . '")' ), [ 'timeout' => 90000 ] );
	}

	/**
	 * Wait until property contains a value
	 *
	 * @param  string $value Value to wait for
	 * @param  string $element_path Path to element to check
	 * @param  string $property Property name
	 */
	public function waitUntilPropertyContains( $value, string $element_path, $property ) {
		$this->getPage()->waitForFunction( JsFunction::createWithBody( 'return document.querySelector("' . addcslashes( $element_path, '"' ) . '").' . $property . '.includes("' . addcslashes( $value, '"' ) . '")' ), [ 'timeout' => 90000 ] );
	}

	/**
	 * Set a specific cookie.
	 *
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
	 * @param  ElementHandle|string $element Either element object or selector string
	 */
	public function hideElement( $element ) {
		$element = $this->getElement( $element );

		return $this->getPage()->evaluate(
			JsFunction::createWithParameters( [ 'element' ] )
			->body(
				'element.style.display = "none";'
			),
			$element
		);
	}

	/**
	 * Get all cookies
	 *
	 * @return  array
	 */
	public function getCookies() {
		return $this->getPage()->cookies();
	}

	/**
	 * Delete a cookie with the give name.
	 *
	 * @param string $name A cookie name to reset.
	 */
	public function deleteCookie( $name ) {
		$this->getPage()->deleteCookies( [ 'name' => $name ] );
	}

	/**
	 * Get element containing text
	 *
	 * @param  string $text Text to search for
	 * @return  ElementHandle
	 */
	public function getElementContaining( $text ) {
		return $this->getPage()->querySelectorXPath( "//*[contains(text(), '" . $text . "')]" );
	}

	/**
	 * Return an element based on CSS selector.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @param  ElementHandle|string $container When provided searches elements inside this container
	 * @throws ElementNotFound Element not found in DOM
	 * @return  ElementHandle
	 */
	public function getElement( $element, $container = null ) {
		if ( $element instanceof ElementHandle ) {
			return $element;
		}

		if ( empty( $container ) ) {
			$container = $this->getPage();
		} else {
			$container = $this->getElement( $container );
		}

		$found_element = $container->querySelector( $element );

		if ( empty( $found_element ) ) {
			throw new ElementNotFound( 'Element not found.' );
		}

		return $found_element;
	}

	/**
	 * Return elements based on CSS selector.
	 *
	 * @param  array|string $elements Elements to get
	 * @return  array Array of ElementHandle
	 */
	public function getElements( $elements ) {
		if ( is_array( $elements ) ) {
			$items = [];

			foreach ( $elements as $element ) {
				if ( $element instanceof ElementHandle ) {
					$items[] = $element;
				} else {
					$items[] = $this->getElement( $element );
				}
			}

			return $items;
		}

		return $this->getPage()->querySelectorAll( $elements );
	}

	/**
	 * Click an element with only JS.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 */
	public function jsClick( $element ) {
		$element = $this->getElement( $element );

		$this->getPage()->evaluate(
			JsFunction::createWithParameters( [ 'element' ] )
			->body(
				'element.click();'
			),
			$element
		);
	}

	/**
	 * Click an element.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 */
	public function click( $element ) {
		$element = $this->getElement( $element );

		$element->click();
	}

	/**
	 * Select option by value of a dropdown element.
	 *
	 * @param  string $element_path Element selector
	 * @param  mixed  $option_value Value to select
	 */
	public function selectOptionByValue( string $element_path, $option_value ) {
		$this->getPage()->select( $element_path, $option_value );
	}

	/**
	 * Submit a form from an element.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
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
		$this->login( $username, $password );
	}

	/**
	 * Login as a user
	 *
	 * @param  string $username Username
	 * @param  string $password Password
	 */
	public function login( string $username = null, string $password = 'password' ) {
		if ( null === $username ) {
			$snapshot = EnvironmentFactory::get()->getSnapshot();

			$username = empty( $snapshot ) ? 'admin' : 'wpsnapshots';
		}

		$this->moveTo( 'wp-login.php' );

		$this->setElementAttribute( '#user_login', 'value', $username );

		usleep( 100 );

		$this->setElementAttribute( '#user_pass', 'value', $password );

		usleep( 100 );

		$this->click( '#wp-submit' );

		usleep( 200 );

		$this->waitUntilNavigation( [ 'domcontentloaded' ] );

		try {
			$login_error = $this->getElement( '#login_error' );

			$this->setElementAttribute( '#user_login', 'value', $username );

			usleep( 100 );

			$this->setElementAttribute( '#user_pass', 'value', $password );

			usleep( 100 );

			$this->click( '#wp-submit' );
		} catch ( ElementNotFound $exception ) {
			// Do nothing
		}

		$this->waitUntilElementVisible( '#wpadminbar' );
	}

	/**
	 * Logout from WordPress.
	 */
	public function logout() {
		$this->moveTo( 'wp-login.php?action=logout' );

		$this->waitUntilElementVisible( '#error-page' );

		$this->click( '#error-page a' );

		usleep( 200 );

		$this->waitUntilElementVisible( '#loginform' );
	}

	/**
	 * Check a checkbox or radio input.
	 *
	 * @param  array|string $elements Array of ElementHandle or selector string
	 */
	public function checkOptions( $elements ) {
		$elements = $this->getElements( $elements );

		foreach ( $elements as $element ) {
			$type = $this->getElementAttribute( $element, 'type' );

			if ( in_array( $type, array( 'checkbox', 'radio' ), true ) && empty( $element->getProperty( 'checked' )->jsonValue() ) ) {
				$element->click();
			}
		}
	}

	/**
	 * Uncheck a checkbox.
	 *
	 * @param  array $elements Array of ElementHandle or selector string
	 */
	public function uncheckOptions( $elements ) {
		$elements = $this->getElements( $elements );

		foreach ( $elements as $element ) {
			$type = $this->getElementAttribute( $element, 'type' );

			if ( 'checkbox' === $type && ! empty( $element->getProperty( 'checked' )->jsonValue() ) ) {
				$element->click();
			}
		}
	}

	/**
	 * Check if element is interactable
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 */
	public function canInteractWithField( $element ) {
		$element = $this->getElement( $element );

		$old_value = $this->getElementAttribute( $element, 'value' );

		$element->click();

		$this->getPage()->keyboard->type( 'wpa' );
		$this->getPage()->keyboard->press( 'Backspace' );

		TestCase::assertTrue( ( $old_value !== $this->getElementProperty( $element, 'value' ) ), 'Can not interact with field.' );

		$this->setElementProperty( $element, 'value', $old_value );
	}

	/**
	 * Check if element is not interactable
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 */
	public function cannotInteractWithField( $element ) {
		$element = $this->getElement( $element );

		$old_value = $this->getElementAttribute( $element, 'value' );

		$element->click();

		$this->getPage()->keyboard->type( 'wpa' );
		$this->getPage()->keyboard->press( 'Backspace' );

		TestCase::assertTrue( ( $old_value === $this->getElementProperty( $element, 'value' ) ), 'Can interact with field.' );

		$this->setElementProperty( $element, 'value', $old_value );
	}

	/**
	 * Set a value for a field.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @param  mixed                $value Value to put in field
	 */
	public function fillField( $element, $value ) {
		$this->setElementProperty( $element, 'value', $value );
	}

	/**
	 * Clear the value of a textarea or an input fields.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 */
	public function clearField( $element ) {
		$this->fillField( $element_path, '' );
	}

	/**
	 * Type in a field
	 *
	 * @param  string $element_path Path to element
	 * @param  mixed  $value Value to put in field
	 */
	public function typeInField( string $element_path, $value ) {
		$this->setElementProperty( $element_path, 'value', '' );

		$this->getPage()->type( $element_path, $value, [ 'delay' => 20 ] );

		// Hack for buggy type behavior
		$element_value = $this->getElementProperty( $element_path, 'value' );

		if ( $value !== $element_value ) {
			$this->getPage()->type( $element_path, $value, [ 'delay' => 20 ] );
		}
	}

	/**
	 * Attach a file to a field.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @param  string               $file_path Path to file
	 */
	public function attachFile( $element, $file_path ) {
		$element = $this->getElement( $element );

		$element->uploadFile( $file_path );
	}

	/**
	 * Check if the actor sees an element on the current page. Element must be visible to human eye.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 */
	public function seeElement( $element ) {
		TestCase::assertTrue( $this->elementIsVisible( $element ), $this->elementToString( $element ) . ' is not visible.' );
	}

	/**
	 * Check if the actor sees an element on the current page a specific number of times. Element
	 * must be visible to human eye.
	 *
	 * @param  ElementHandle|string $elements Either element object or selector string
	 * @param  int                  $times Number of times element must be visible
	 */
	public function seeNumberOfElements( $elements, int $times ) {
		$elements = $this->getElements( $elements );

		$visible = 0;

		foreach ( $elements as $element ) {
			if ( $this->elementIsVisible( $element ) ) {
				$visible++;
			}
		}

		TestCase::assertEquals( $times, $visible, $this->elementsToString( $elements ) . ' is not visible ' . $times . ' times.' );
	}

	/**
	 * Check if the actor doesnt see an element on the current page. Element must not be visible to human eye.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 */
	public function dontSeeElement( $element ) {
		TestCase::assertFalse( $this->elementIsVisible( $element ), $this->elementToString( $element ) . ' is visible.' );
	}

	/**
	 * Check if the actor sees a text on the current page. You can use a regular expression to check a text.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: "/test/i".
	 *
	 * @param  string               $text Text to check for
	 * @param  ElementHandle|string $element Either element object or selector string
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
	 * @param  string               $text to check for
	 * @param  ElementHandle|string $element Either element object or selector string
	 */
	public function dontSeeText( $text, $element = null ) {
		if ( empty( $element ) ) {
			$element = $this->getElement( 'body' );
		} else {
			try {
				$element = $this->getElement( $element );
			} catch ( ElementNotFound $exception ) {
				return;
			}
		}

		$content = trim( $this->getElementInnerText( $element ) );

		if ( empty( $content ) ) {
			return;
		}

		TestCase::assertFalse( Utils\find_match( $content, $text ), $text . ' found.' );
	}

	/**
	 * Check if the actor sees a text in the page source. You can use a regular expression to check a text.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @param string $text A text to look for or a regular expression.
	 */
	public function seeTextInSource( $text ) {
		TestCase::assertTrue( Utils\find_match( $this->getPageSource(), $text ), $text . ' not found in source.' );
	}

	/**
	 * Check if the actor can't see a text in the page source. You can use a regular expression to check a text.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @param string $text A text to look for or a regular expression.
	 */
	public function dontSeeTextInSource( $text ) {
		TestCase::assertFalse( Utils\find_match( $this->getPageSource(), $text ), $text . ' found in source.' );
	}

	/**
	 * Press a key on an element.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @param string               $key A key to press.
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
	 * @param  ElementHandle|string $element Either element object or selector string
	 */
	public function pressEnterKey( $element ) {
		$this->pressKey( $element, 'Enter' );
	}

	/**
	 * Return current active element.
	 *
	 * @return ElementHandle
	 */
	public function getActiveElement() {
		return $this->getPage()->evaluate( JsFunction::createWithBody( 'return document.activeElement;' ) );
	}

	/**
	 * Check if the actor sees a link on the current page with specific text and url. You can use
	 * a regular expression to check URL in the href attribute. Please, use forward slashes to define your
	 * regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @param string $text A text to find a link.
	 * @param string $url Optional. The url of the link.
	 */
	public function seeLink( $text, $url = '' ) {
		$selector = 'a';

		if ( ! empty( $url ) ) {
			$selector = 'a[href="' . $url . '"]';
		}

		$links = $this->getElements( $selector );

		$found_link = false;

		foreach ( $links as $link ) {
			$content = trim( $this->getElementInnerText( $link ) );

			$found_link = Utils\find_match( $content, $text );

			if ( $found_link ) {
				break;
			}
		}

		TestCase::assertTrue( $found_link, $text . ' not found in link.' );
	}

	/**
	 * Check if the actor doesn't see a link on the current page with specific text and url. You can use
	 * a regular expression to check URL in the href attribute. Please, use forward slashes to define your
	 * regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @param string $text A text to find a link.
	 * @param string $url Optional. The url of the link.
	 */
	public function dontSeeLink( $text, $url = '' ) {
		$selector = 'a';

		if ( ! empty( $url ) ) {
			$selector = 'a[href="' . $url . '"]';
		}

		$links = $this->getElements( $selector );

		$found_link = false;

		foreach ( $links as $link ) {
			$content = trim( $this->getElementInnerText( $link ) );

			$found_link = Utils\find_match( $content, $text );

			if ( $found_link ) {
				break;
			}
		}

		TestCase::assertFalse( $found_link, $text . ' found in link.' );
	}

	/**
	 * Check if the actor can see a text in the current URL. You can use a regular expression to check the current URL.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @param string $text A text to look for in the current URL.
	 */
	public function seeTextInUrl( $text ) {
		TestCase::assertTrue( Utils\find_match( $this->getCurrentUrl(), $text ), $text . ' not found.' );
	}

	/**
	 * Check if the actor cann't see a text in the current URL. You can use a regular expression to check the current URL.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @param string $text A text to look for in the current URL.
	 */
	public function dontSeeTextInUrl( $text ) {
		TestCase::assertFalse( Utils\find_match( $this->getCurrentUrl(), $text ), $text . ' found.' );
	}

	/**
	 * Return current URL.
	 *
	 * @return string The current URL.
	 */
	public function getCurrentUrl() {
		return $this->getPage()->url();
	}

	/**
	 * Check if the current user can see a checkbox is checked.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 */
	public function seeCheckboxIsChecked( $element ) {
		$element = $this->getElement( $element );

		TestCase::assertTrue( $element->getProperty( 'checked' )->jsonValue(), 'Element not checked.' );
	}

	/**
	 * Check if the current user cann't see a checkbox is checked.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 */
	public function dontSeeCheckboxIsChecked( $element ) {
		$element = $this->getElement( $element );

		TestCase::assertFalse( $element->getProperty( 'checked' )->jsonValue(), 'Element checked.' );
	}

	/**
	 * Check if the user can see text inside of an attribute
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @param string               $attribute Attribute name
	 * @param string               $value Attribute value
	 */
	public function seeValueInAttribute( $element, $attribute, $value ) {
		$element = $this->getElement( $element );

		$attribute_value = $this->getElementAttribute( $element, $attribute );

		TestCase::assertTrue( Utils\find_match( $attribute_value, $value ), $value . ' not found in attribute.' );
	}

	/**
	 * Check if the user can not see text inside of an attribute
	 *
	 * @param ElementHandle|string $element Either element object or selector string
	 * @param string               $attribute Attribute name
	 * @param string               $value Attribute value
	 */
	public function dontSeeValueInAttribute( $element, $attribute, $value ) {
		$element = $this->getElement( $element );

		$attribute_value = $this->getElementAttribute( $element, $attribute );

		TestCase::assertFalse( Utils\find_match( $attribute_value, $value ), $text . ' found in attribute.' );
	}

	/**
	 * Check if the user can see text inside of an property
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @param string               $property Property name
	 * @param string               $value Property value
	 */
	public function seeValueInProperty( $element, $property, $value ) {
		$element = $this->getElement( $element );

		$property_value = $this->getElementProperty( $element, $property );

		TestCase::assertTrue( Utils\find_match( $property_value, $value ), $value . ' not found in property.' );
	}

	/**
	 * Check if the user can see text inside of an property
	 *
	 * @param ElementHandle|string $element Either element object or selector string
	 * @param string               $property Property name
	 * @param string               $value Property value
	 */
	public function dontSeeValueInProperty( $element, $property, $value ) {
		$element = $this->getElement( $element );

		$property_value = $this->getElementProperty( $element, $property );

		TestCase::assertFalse( Utils\find_match( $property_value, $value ), $value . ' found in property.' );
	}

	/**
	 * Check if the current user can see a value in a field. You can use a regular expression to check the value.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @param  string               $value Field value
	 */
	public function seeFieldValue( $element, $value ) {
		$element = $this->getElement( $element );

		$prop_value = $this->getElementProperty( $element, 'value' );

		TestCase::assertTrue( Utils\find_match( $prop_value, $value ), $value . ' not found in field value.' );
	}

	/**
	 * Check if the current user can see a value in a field. You can use a regular expression to check the value.
	 * Please, use forward slashes to define your regular expression if you want to use it. For instance: <b>"/test/i"</b>.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @param  string               $value Field value
	 */
	public function dontSeeFieldValue( $element, $value ) {
		$element = $this->getElement( $element );

		$prop_value = $this->getElementProperty( $element, 'value' );

		TestCase::assertFalse( Utils\find_match( $prop_value, $value ), $value . ' found in field value.' );
	}

	/**
	 * Convert an element to a piece of a failure message.
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @return string A message.
	 */
	public function elementToString( $element ) {
		if ( is_string( $element ) ) {
			return $element;
		} elseif ( is_array( $element ) ) {
			return $this->elementsToString( $element );
		}

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

	/**
	 * Convert elements to a piece of a failure message.
	 *
	 * @param  array $elements Array of elements
	 * @return string A message.
	 */
	public function elementsToString( array $elements ) {
		$output = '';

		foreach ( $elements as $key => $element ) {
			if ( $key > 0 ) {
				$output .= ', ';
			}

			$output .= $this->elementToString( $element );
		}

		return $output;
	}

	/**
	 * Check if element is visible
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @return boolean
	 */
	public function elementIsVisible( $element ) {
		try {
			$element = $this->getElement( $element );
		} catch ( ElementNotFound $exception ) {
			return false;
		}

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

	/**
	 * Check if element is enabled
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @return boolean
	 */
	public function elementIsEnabled( $element ) {
		$element = $this->getElement( $element );

		return $this->getPage()->evaluate(
			JsFunction::createWithParameters( [ 'element' ] )
			->body(
				'return ! element.disabled;'
			),
			$element
		);
	}

	/**
	 * Get element tag name
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @return  string
	 */
	public function getElementTagName( $element ) {
		$element = $this->getElement( $element );

		return $this->getPage()->evaluate(
			JsFunction::createWithParameters( [ 'element' ] )
			->body(
				'return element.tagName;'
			),
			$element
		);
	}

	/**
	 * Get element attribute
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @param  string               $attribute_name Attribute name
	 * @return string
	 */
	public function getElementAttribute( $element, string $attribute_name ) {
		$element = $this->getElement( $element );

		return $this->getPage()->evaluate(
			JsFunction::createWithParameters( [ 'element' ] )
			->body(
				'
			    return element.getAttribute( "' . addcslashes( $attribute_name, '"' ) . '" );
				'
			),
			$element
		);
	}

	/**
	 * Get element property
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @param  string               $property_name Property name
	 * @return string
	 */
	public function getElementProperty( $element, string $property_name ) {
		$element = $this->getElement( $element );

		return $this->getPage()->evaluate(
			JsFunction::createWithParameters( [ 'element' ] )
			->body(
				'return element.' . $property_name . ';'
			),
			$element
		);
	}

	/**
	 * Get element inner text
	 *
	 * @param  ElementHandle|string $element Either element object or selector string
	 * @return string
	 */
	public function getElementInnerText( $element ) {
		$element = $this->getElement( $element );

		return $this->getPage()->evaluate(
			JsFunction::createWithParameters( [ 'element' ] )
			->body(
				'return element.innerText;'
			),
			$element
		);
	}

}
