<?php

namespace WPAssure\PHPUnit\Constraints;

class UrlContains extends \WPAssure\PHPUnit\Constraint {

	use WPAssure\PHPUnit\Constraints\Traits\SeeableAction,
	    WPAssure\PHPUnit\Constraints\Traits\StringOrPattern;

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
		parent::__construct( $this->_verifyAction( $action ) );

		$this->_text = $text;
	}

	/**
	 * Evaluate if the actor can or can't see a text in the current URL.
	 *
	 * @access protected
	 * @param \WPAssure\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ) {
		$actor = $this->_getActor( $other );
		$webdriver = $actor->getWebDriver();

		$url = trim( $webdriver->getCurrentURL() );
		$found = $this->_findMatch( $url, $this->_text );

		return ( $found && $this->_action === self::ACTION_SEE ) || ( ! $found && $this->_action === self::ACTION_DONTSEE );
	}

	/**
	 * Return description of the failure.
	 *
	 * @access public
	 * @return string The description text.
	 */
	public function toString() {
		return sprintf( ' "%s" text in the current URL', $this->_text );
	}

}
