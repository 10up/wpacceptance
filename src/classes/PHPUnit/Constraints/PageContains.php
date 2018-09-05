<?php

namespace WPAssure\PHPUnit\Constraints;

use Facebook\WebDriver\Remote\RemoteWebElement;

class PageContains extends \WPAssure\PHPUnit\Constraint {

	use WPAssure\PHPUnit\Constraints\Traits\StringOrPattern;

	/**
	 * The text to look for.
	 *
	 * @access private
	 * @var string
	 */
	private $_text;

	/**
	 * Optional element to look for a text.
	 *
	 * @access private
	 * @var \Facebook\WebDriver\Remote\RemoteWebElement|string
	 */
	private $_element;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $action The evaluation action. Valid options are "see" or "dontSee".
	 * @param string $text A text to look for.
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element Optional. An element to look for a text inside.
	 */
	public function __construct( $action, $text, $element ) {
		parent::__construct( self::_verifySeeableAction( $action ) );

		$this->_text = $text;
		$this->_element = $element;
	}

	/**
	 * Evaluate if the actor can or can't see a text.
	 *
	 * @access protected
	 * @param \WPAssure\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ) {
		$actor = $this->_getActor( $other );
		$element = $actor->getElement( ! empty( $this->_element ) ? $this->_element : 'body' );
		if ( $element ) {
			$content = trim( $element->getText() );
			if ( empty( $content ) ) {
				// if current action is "dontSee" then return "true" what means the constrain is met,
				// otherwise it means that action is "see" and the constrain isn't met, thus return "false"
				return $this->_action === self::ACTION_DONTSEE;
			}

			$found = $this->_findMatch( $content, $this->_text );

			return ( $found && $this->_action === self::ACTION_SEE ) || ( ! $found && $this->_action === self::ACTION_DONTSEE );
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
		$message = sprintf( ' "%s" text', $this->_text );

		if ( ! empty( $this->_element ) ) {
			if ( is_string( $this->_element ) ) {
				$message .= sprintf( ' in the scope of "%s" selector', $this->_element );
			} elseif ( $this->_element instanceof RemoteWebElement ) {
				$message .= ' in the scope of ' . $this->_element->getTagName();

				$id = trim( $this->_element->getID() );
				if ( ! empty( $id ) ) {
					$message .= '#' . $id;
				}

				$class = trim( $this->_element->getAttribute( 'class' ) );
				if ( ! empty( $class ) ) {
					$classes = array_filter( array_map( 'trim', split( ' ', $class ) ) );
					if ( ! empty( $classes ) ) {
						$message .= '.' . implode( '.', $classes );
					}
				}

				$message .= ' tag';
			}
		}

		return $message;
	}

}
