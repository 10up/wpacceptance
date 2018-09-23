<?php
/**
 * Check if element is visible
 *
 * @package  wpassure
 */

namespace WPAssure\PHPUnit\Constraints;

use PHPUnit\Framework\ExpectationFailedException;

/**
 * Constraint class
 */
class ElementVisible extends \WPAssure\PHPUnit\Constraint {

	use Traits\SeeableAction,
		Traits\ElementToMessage;

	/**
	 * Element to look for
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
	 * @param \Facebook\WebDriver\Remote\RemoteWebElement|string $element An element to look for a text inside.
	 */
	public function __construct( $action, $element ) {
		parent::__construct( $this->verifyAction( $action ) );

		$this->element = $element;
	}

	/**
	 * Evaluate if the actor can or can't see the element
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
			$found = $element->isDisplayed();

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
		return $this->elementToMessage( $this->element );
	}

}
