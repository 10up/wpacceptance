<?php

namespace WPAssure\PHPUnit\Constraints;

class Cookie extends \WPAssure\PHPUnit\Constraint {

	use WPAssure\PHPUnit\Constraints\Traits\SeeableAction;

	/**
	 * The cookie name.
	 *
	 * @access private
	 * @var string
	 */
	private $_name = '';

	/**
	 * The cookie value.
	 *
	 * @access private
	 * @var mixed
	 */
	private $_value = null;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $action The evaluation action. Valid options are "see" or "dontSee".
	 * @param string $name A cookie name.
	 * @param mixed  $value Optional. Cookie vale.
	 */
	public function __construct( $action, $name, $value ) {
		parent::__construct( $this->_verifyAction( $action ) );

		$this->_name  = $name;
		$this->_value = $value;
	}

	/**
	 * Evaluate if the actor can or can't see a cookie.
	 *
	 * @access protected
	 * @param \WPAssure\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ) {
		$actor     = $this->_getActor( $other );
		$webdriver = $actor->getWebDriver();

		$cookies = $webdriver->manage()->getCookies();
		foreach ( $cookies as $cookie ) {
			if ( $cookie['name'] === $this->_name ) {
				if ( empty( $this->_value ) ) {
					// if current action is "see" then return "true" what means the constrain is met,
					// otherwise it means that action is "dontSee" and the constrain isn't met, thus return "false"
					return $this->_action === self::ACTION_SEE;
				}

				// if current action is "see" and cookie's value equals to what we are looking for,
				// then return "true" what means the constrain is met, otherwise it means that action is
				// "dontSee" or value doesn't match, thus return "false"
				return $cookie['value'] == $this->_value && $this->_action === self::ACTION_SEE;
			}
		}

		return false;
	}

	/**
	 * Return description of the failure.
	 *
	 * @access public
	 * @return string The description text.
	 */
	public function toString() {
		$message = sprintf( ' "%s" cookie', $this->_name );
		if ( ! empty( $this->_value ) ) {
			$message .= sprintf( ' with "%s" value', $this->_value );
		}

		return $message;
	}

}
