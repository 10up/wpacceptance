<?php

namespace WPAssure\PHPUnit\Constraints;

class CheckboxChecked extends \WPAssure\PHPUnit\Constraint {

	use WPAssure\PHPUnit\Constraints\Traits\SeeableAction,
	    WPAssure\PHPUnit\Constraints\Traits\ElementToMessage;

	/**
	 * The checkbox element to look for.
	 *
	 * @access private
	 * @var string
	 */
	private $_element;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $action The evaluation action. Valid options are "see" or "dontSee".
	 * @param string $element A text to look for.
	 */
	public function __construct( $action, $element ) {
		parent::__construct( $this->_verifyAction( $action ) );

		$this->_element = $element;
	}

	/**
	 * Evaluate if the actor can or can't see a checkbox is checked.
	 *
	 * @access protected
	 * @param \WPAssure\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ) {
		$actor = $this->_getActor( $other );
		$element = $actor->getElement( $this->_element );
		$checked = $element->getAttribute( 'checked' ) === 'checked';

		return ( $checked && $this->_action === self::ACTION_SEE ) || ( ! $checked && $this->_action === self::ACTION_DONTSEE );
	}

	/**
	 * Return description of the failure.
	 *
	 * @access public
	 * @return string The description text.
	 */
	public function toString() {
		return sprintf( ' %s is checked', $this->_elementToMessage( $this->_element ) );
	}

}
