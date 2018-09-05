<?php

namespace WPAssure\PHPUnit\Constraints;

class PageSourceContains extends \WPAssure\PHPUnit\Constraint {

	/**
	 * The text to look for.
	 *
	 * @access private
	 * @var string
	 */
	private $_text;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $action The evaluation action. Valid options are "see" or "dontSee".
	 * @param string $text A text to look for.
	 */
	public function __construct( $action, $text ) {
		$current_action = $action === self::ACTION_SEE || $action === self::ACTION_DONTSEE
			? $action
			: self::ACTION_SEE;

		parent::__construct( $current_action );

		$this->_text = $text;
	}

	/**
	 * Evaluate if the actor can or can't see a text in the page source.
	 *
	 * @access protected
	 * @param \WPAssure\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ) {
		$actor = $this->_getActor( $other );

		$text = trim( $actor->getPageSource() );
		if ( empty( $text ) ) {
			// if current action is "dontSee" then return "true" what means the constrain is met,
			// otherwise it means that action is "see" and the constrain isn't met, thus return "false"
			return $this->_action === self::ACTION_DONTSEE;
		}

		$found = preg_match( '#^/[^/]+/(\w?)$#', $this->_text )
			? preg_match( $this->_text, $text ) > 0
			: mb_stripos( $text, $this->_text ) !== false;

		return ( $found && $this->_action === self::ACTION_SEE ) || ( ! $found && $this->_action === self::ACTION_DONTSEE );
	}

	/**
	 * Return description of the failure.
	 *
	 * @access public
	 * @return string The description text.
	 */
	public function toString() {
		return sprintf( ' "%s" text in the page source', $this->_text );
	}

}
