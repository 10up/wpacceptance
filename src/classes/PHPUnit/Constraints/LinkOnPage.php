<?php

namespace WPAssure\PHPUnit\Constraints;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;

class LinkOnPage extends \WPAssure\PHPUnit\Constraint {

	use WPAssure\PHPUnit\Constraints\Traits\StringOrPattern;

	/**
	 * A text of a link to look for.
	 *
	 * @access private
	 * @var string
	 */
	private $_text = '';

	/**
	 * A url of a link to look for.
	 *
	 * @access private
	 * @var string
	 */
	private $_url = '';

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $action The evaluation action. Valid options are "see" or "dontSee".
	 * @param string $text A text of a link to look for.
	 * @param string $url A url of a link to look for.
	 */
	public function __construct( $action, $text, $url ) {
		parent::__construct( self::_verifySeeableAction( $action ) );

		$this->_text = $text;
		$this->_url  = $url;
	}

	/**
	 * Evaluate if the actor can or can't see a link with specific text and url.
	 *
	 * @access protected
	 * @param \WPAssure\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ) {
		$actor = $this->_getActor( $other );
		$by    = WebDriverBy::partialLinkText( $this->_text );

		try {
			$elements = $actor->getElements( $by );
			if ( ! empty( $this->_url ) ) {
				foreach ( $elements as $element ) {
					$href = $element->getAttribute( 'href' );
					if ( $this->_findMatch( $href, $this->_url ) ) {
						return $this->_action === self::ACTION_SEE;
					}
				}

				return $this->_action === self::ACTION_DONTSEE;
			} else {
				return $this->_action === self::ACTION_SEE;
			}
		} catch ( NoSuchElementException $e ) {
			return $this->_action === self::ACTION_DONTSEE;
		}

		return false;
	}

	/**
	 * Return description of the failure.
	 *
	 * @access public
	 * @return string The description text.
	 */
	public function toString() {
		$message = sprintf( ' a link with "%s" text' );
		if ( ! empty( $this->_url ) ) {
			$message .= sprintf( ' that contains "%s" url in the href attribute', $this->_url );
		}

		return $message;
	}

}
