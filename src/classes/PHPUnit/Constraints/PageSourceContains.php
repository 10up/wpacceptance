<?php

namespace WPAssure\PHPUnit\Constraints;

class PageSourceContains extends \WPAssure\PHPUnit\Constraint {

	use WPAssure\PHPUnit\Constraints\Traits\StringOrPattern;

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
		parent::__construct( self::_verifySeeableAction( $action ) );
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

		$content = trim( $actor->getPageSource() );
		if ( empty( $content ) ) {
			// if current action is "dontSee" then return "true" what means the constrain is met,
			// otherwise it means that action is "see" and the constrain isn't met, thus return "false"
			return $this->_action === self::ACTION_DONTSEE;
		}

		$found = $this->_findMatch( $content, $this->_text );

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
