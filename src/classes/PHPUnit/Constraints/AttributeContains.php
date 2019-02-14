<?php
/**
 * Check field values constraint
 *
 * @package  wpacceptance
 */

namespace WPAcceptance\PHPUnit\Constraints;

use WPAcceptance\PHPUnit\Traits\ElementUtilities;

/**
 * Constraint class
 */
class AttributeContains extends \WPAcceptance\PHPUnit\Constraint {

	use ElementUtilities;

	/**
	 * The element to look for.
	 *
	 * @access private
	 * @var string
	 */
	private $element;

	/**
	 * The attribute to check
	 *
	 * @access private
	 * @var string
	 */
	private $attribute;

	/**
	 * A value to check.
	 *
	 * @access private
	 * @var string
	 */
	private $value;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $action The evaluation action. Valid options are "see" or "dontSee".
	 * @param string $element A text to look for.
	 * @param strong $attribute Attribute to check
	 * @param string $value A value to check.
	 */
	public function __construct( $action, $element, $attribute, $value ) {
		parent::__construct( $action );

		$this->element   = $element;
		$this->attribute = $attribute;
		$this->value     = $value;
	}

	/**
	 * Evaluate if the actor can or can't see a value in the field.
	 *
	 * @access protected
	 * @param \WPAcceptance\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ): bool {
		$actor = $this->getActor( $other );

		$element = $actor->getElement( $this->element );

		$attribute_value = $element->getAttribute( $this->attribute );

		$found = ( false !== stripos( $attribute_value, $this->value ) );

		return ( $found && self::ACTION_SEE === $this->action ) || ( ! $found && self::ACTION_DONTSEE === $this->action );
	}

	/**
	 * Return description of the failure.
	 *
	 * @access public
	 * @return string The description text.
	 */
	public function toString(): string {
		return sprintf( ' %s contains "%s" value', $this->elementToMessage( $this->element ), $this->value );
	}

}
