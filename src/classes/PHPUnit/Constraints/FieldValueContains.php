<?php

namespace WPAssure\PHPUnit\Constraints;

use Facebook\WebDriver\WebDriverSelect;

class FieldValueContains extends \WPAssure\PHPUnit\Constraint {

	use WPAssure\PHPUnit\Constraints\Traits\SeeableAction,
	    WPAssure\PHPUnit\Constraints\Traits\StringOrPattern,
	    WPAssure\PHPUnit\Constraints\Traits\ElementToMessage;

	/**
	 * The element to look for.
	 *
	 * @access private
	 * @var string
	 */
	private $_element;

	/**
	 * A value to check.
	 *
	 * @access private
	 * @var string
	 */
	private $_value;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $action The evaluation action. Valid options are "see" or "dontSee".
	 * @param string $element A text to look for.
	 * @param string $value A value to check.
	 */
	public function __construct( $action, $element, $value ) {
		parent::__construct( $this->_verifyAction( $action ) );

		$this->_element = $element;
		$this->_value = $value;
	}

	/**
	 * Evaluate if the actor can or can't see a value in the field.
	 *
	 * @access protected
	 * @param \WPAssure\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ) {
		$actor = $this->_getActor( $other );
		$element = $actor->getElement( $this->_element );

		$tag = strtolower( $element->getTagName() );
		$content = '';
		switch ( $tag ) {
			case 'input':
				$content = $element->getAttribute( 'value' );
				break;
			case 'textarea':
				$content = $element->getText();
				break;
			case 'select':
				$select = new WebDriverSelect( $element );
				$content = $select->getAllSelectedOptions();
				break;
		}

		$found = false;
		if ( is_array( $content ) ) {
			foreach ( $content as $option ) {
				$found |= $this->_findMatch( $option, $this->_value );
			}
		} else {
			$found = $this->_findMatch( $content, $this->_value );
		}

		return ( $found && $this->_action === self::ACTION_SEE ) || ( ! $found && $this->_action === self::ACTION_DONTSEE );
	}

	/**
	 * Return description of the failure.
	 *
	 * @access public
	 * @return string The description text.
	 */
	public function toString() {
		return sprintf( ' %s contains "%s" value', $this->_elementToMessage( $this->_element ), $this->_value );
	}

}
