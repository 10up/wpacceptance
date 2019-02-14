<?php
/**
 * Check if field can be interacted with
 *
 * @package  wpacceptance
 */

namespace WPAcceptance\PHPUnit\Constraints;

use WPAcceptance\PHPUnit\Traits\ElementUtilities;

/**
 * Constraint class
 */
class FieldInteractable extends \WPAcceptance\PHPUnit\Constraint {

	use ElementUtilities;

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
		parent::__construct( $action );

		$this->element = $element;
	}

	/**
	 * Evaluate if the actor can or can't see the element
	 *
	 * @access protected
	 * @param \WPAcceptance\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ): bool {
		$actor = $this->getActor( $other );

		$element = $actor->getElement( $this->element );

		try {
			$element->clear();
		} catch ( InvalidElementStateException $e ) {
			return self::ACTION_CANTINTERACT === $this->action;
		}

		$interactable = ( $element->isEnabled() && $element->isDisplayed() );

		return ( $interactable && self::ACTION_INTERACT === $this->action ) || ( ! $interactable && self::ACTION_CANTINTERACT === $this->action );
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
