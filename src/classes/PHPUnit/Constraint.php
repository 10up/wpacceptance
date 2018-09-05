<?php

namespace WPAssure\PHPUnit;

abstract class Constraint extends \PHPUnit\Framework\Constraint\Constraint {

	const ACTION_SEE     = 'see';
	const ACTION_DONTSEE = 'dontSee';

	/**
	 * The evaluation action.
	 *
	 * @access protected
	 * @var string
	 */
	protected $_action = '';

	/**
	 * Verify and return evaluation action that can be either "see" or "dontSee" only.
	 * If the action is not valid, return "see" action as default.
	 *
	 * @static
	 * @access public
	 * @param string $action The original evaluation action.
	 * @return string Verified evaluation action or "see" action if it is invalid.
	 */
	protected static function _verifySeeableAction( $action ) {
		return $action === self::ACTION_SEE || $action === self::ACTION_DONTSEE
			? $action
			: self::ACTION_SEE;
	}

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $action The evaluation action.
	 */
	public function __construct( $action ) {
		parent::__construct();
		$this->_action = $action;
	}

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
	 * Return the description of the current evaluation action.
	 *
	 * @access protected
	 * @return string A description of the current action.
	 */
	protected function _getActionDescription() {
		switch ( $this->_action ) {
			case self::ACTION_SEE:
				return ' sees';
			case self::ACTION_DONTSEE:
				return " doesn't see";
		}

		return '';
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
		return $actor->getActorName() . $this->_getActionDescription() . $this->toString();
	}

}
