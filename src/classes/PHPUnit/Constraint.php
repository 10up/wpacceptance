<?php

namespace WPAssure\PHPUnit;

abstract class Constraint extends \PHPUnit\Framework\Constraint\Constraint {

	const ACTION_SEE     = 'see';
	const ACTION_DONTSEE = 'dontSee';

	/**
	 * Return an instance of the actor class.
	 *
	 * @access protected
	 * @throws \WPAssure\Exception when the constrain is used with not an instance of the Actor class.
	 * @param \WPAssure\PHPUnit\Actor $other Incoming argument that used for this constrain.
	 * @return \WPAssure\PHPUnit\Actor An instance of the Actor class.
	 */
	protected function _getActor( $other ) {
		if ( ! ( $other instanceof \WPAssure\PHPUnit\Actor ) ) {
			throw new \WPAssure\Exception( 'The constrain must be used only with an instance of the Actor class.' );
		}

		return $other;
	}

    /**
     * Return the description of the failure.
     *
	 * @access protected
	 * @param mixed $other An instance of an actor.
	 * @return string A description of the failure.
	 */
	protected function failureDescription( $other ) {
		$actor = $this->_getActor( $other );

		$message = $actor->getActorName();
		$message .= $this->_action === self::ACTION_SEE ? ' sees ' : " doesn't see";
		$message .= $this->toString();

		return $message;
	}

}
