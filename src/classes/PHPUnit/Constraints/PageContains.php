<?php
/**
 * Test page contents constraint
 *
 * @package  wpassure
 */

namespace WPAssure\PHPUnit\Constraints;

use PHPUnit\Framework\ExpectationFailedException;

/**
 * Constraint class
 */
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
	private $text;

	/**
	 * Optional element to look for a text.
	 *
	 * @access private
	 * @var \Facebook\WebDriver\Remote\RemoteWebElement|string
	 */
	private $element;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string                                             $action The evaluation action. Valid options are "see" or "dontSee".
	 * @param string                                             $text A text to look for.
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element Optional. An element to look for a text inside.
	 */
	public function __construct( $action, $text, $element ) {
		parent::__construct( $this->verifyAction( $action ) );

		$this->text    = $text;
		$this->element = $element;
	}

	/**
	 * Evaluate if the actor can or can't see a text.
	 *
	 * @access protected
	 * @param \WPAssure\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ): bool {
		$actor = $this->getActor( $other );

		try {
			$element = $actor->getElement( ! empty( $this->element ) ? $this->element : 'body' );
		} catch ( ExpectationFailedException $e ) {
			return self::ACTION_DONTSEE === $this->action;
		}

		if ( $element ) {
			$content = trim( $element->getText() );
			if ( empty( $content ) ) {
				// if current action is "dontSee" then return "true" what means the constrain is met,
				// otherwise it means that action is "see" and the constrain isn't met, thus return "false"
				return self::ACTION_DONTSEE === $this->action;
			}

			$found = $this->findMatch( $content, $this->text );

			return ( $found && self::ACTION_SEE === $this->action ) || ( ! $found && self::ACTION_DONTSEE === $this->action );
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
		$message = sprintf( ' "%s" text', $this->text );
		if ( ! empty( $this->element ) ) {
			$message .= ' in the scope of ' . $this->elementToMessage( $this->element );
		}

		return $message;
	}

}
