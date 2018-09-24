<?php
/**
 * Test if checkbox is checked constraint
 *
 * @package  wpassure
 */

namespace WPAssure\PHPUnit\Constraints;

/**
 * Constraint class
 */
class CheckboxChecked extends \WPAssure\PHPUnit\Constraint {

	use Traits\ElementToMessage;

	/**
	 * The checkbox element to look for.
	 *
	 * @access private
	 * @var string
	 */
	private $element;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $action The evaluation action. Valid options are "see" or "dontSee".
	 * @param string $element A text to look for.
	 */
	public function __construct( $action, $element ) {
		parent::__construct( $action );

		$this->element = $element;
	}

	/**
	 * Evaluate if the actor can or can't see a checkbox is checked.
	 *
	 * @access protected
	 * @param \WPAssure\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ): bool {
		$actor   = $this->getActor( $other );
		$element = $actor->getElement( $this->element );
		$checked = $element->getAttribute( 'checked' ) === 'checked';

		return ( $checked && self::ACTION_SEE === $this->action ) || ( ! $checked && self::ACTION_DONTSEE === $this->action );
	}

	/**
	 * Return description of the failure.
	 *
	 * @access public
	 * @return string The description text.
	 */
	public function toString(): string {
		return sprintf( ' %s is checked', $this->elementToMessage( $this->element ) );
	}

}
