<?php
/**
 * Check field values constraint
 *
 * @package  wpacceptance
 */

namespace WPAcceptance\PHPUnit\Constraints;

use WPAcceptance\PHPUnit\Traits\ElementUtilities;
use WPAcceptance\Utils;

/**
 * Constraint class
 */
class FieldValueContains extends \WPAcceptance\PHPUnit\Constraint {

	use ElementUtilities;

	/**
	 * The element to look for.
	 *
	 * @access private
	 * @var string
	 */
	private $element;

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
	 * @param string $value A value to check.
	 */
	public function __construct( $action, $element, $value ) {
		parent::__construct( $action );

		$this->element = $element;
		$this->value   = $value;
	}

	/**
	 * Evaluate if the actor can or can't see a value in the field.
	 *
	 * @access protected
	 * @param \WPAcceptance\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ): bool {
		$actor   = $this->getActor( $other );
		$element = $actor->getElement( $this->element );

		$tag     = strtolower( $element->getTagName() );
		$content = '';
		switch ( $tag ) {
			case 'input':
				$content = $element->getAttribute( 'value' );
				break;
			case 'textarea':
				$content = $element->getText();
				break;
			case 'select':
				$select  = new WebDriverSelect( $element );
				$content = $select->getAllSelectedOptions();
				break;
		}

		$found = false;
		if ( is_array( $content ) ) {
			foreach ( $content as $option ) {
				$found |= Utils\find_match( $option, $this->value );
			}
		} else {
			$found = Utils\find_match( $content, $this->value );
		}

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
