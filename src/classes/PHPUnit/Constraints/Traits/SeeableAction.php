<?php
/**
 * Evaluate action
 *
 * @package  wpassure
 */

namespace WPAssure\PHPUnit\Constraints\Traits;

use WPAssure\PHPUnit\Constraint;

/**
 * Trait to be mixed with constraint
 */
trait SeeableAction {

	/**
	 * Verify and return evaluation action that can be either "see" or "dontSee" only.
	 * If the action is not valid, return a default action.
	 *
	 * @access public
	 * @param string $action The original evaluation action.
	 * @param string $default A default action if incoming action is invalid.
	 * @return string Verified evaluation action or default action if it's invalid.
	 */
	protected function verifyAction( $action, $default = Constraint::ACTION_SEE ) {
		return $action === Constraint::ACTION_SEE || $action === Constraint::ACTION_DONTSEE
			? $action
			: $default;
	}

}
