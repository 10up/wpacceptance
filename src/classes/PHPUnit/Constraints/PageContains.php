<?php

namespace WPAssure\PHPUnit\Constraints;

class PageContains extends \WPAssure\PHPUnit\Constraint {

	use Traits\StringOrPattern,
		Traits\SeeableAction,
		Traits\ElementToMessage;

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
	 * @param string                                             $action The evaluation action. Valid options are "see" or "dontSee".
	 * @param string                                             $text A text to look for.
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element Optional. An element to look for a text inside.
	 */
	public function __construct( $action, $text, $element ) {
		parent::__construct( $this->_verifyAction( $action ) );

		$this->_text    = $text;
		$this->_element = $element;
	}

	/**
	 * Evaluate if the actor can or can't see a text.
	 *
	 * @access protected
	 * @param \WPAssure\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ): bool {
		$actor   = $this->_getActor( $other );
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
	public function toString(): string {
		$message = sprintf( ' "%s" text', $this->_text );
		if ( ! empty( $this->_element ) ) {
			$message .= ' in the scope of ' . $this->_elementToMessage( $this->_element );
		}

		return $message;
	}

}
