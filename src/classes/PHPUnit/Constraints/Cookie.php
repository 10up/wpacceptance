<?php
/**
 * Test cookies constraint
 *
 * @package  wpassure
 */

namespace WPAssure\PHPUnit\Constraints;

/**
 * Constraint class
 */
class Cookie extends \WPAssure\PHPUnit\Constraint {

	use WPAssure\PHPUnit\Constraints\Traits\SeeableAction;

	/**
	 * The cookie name.
	 *
	 * @access private
	 * @var string
	 */
	private $name = '';

	/**
	 * The cookie value.
	 *
	 * @access private
	 * @var mixed
	 */
	private $value = null;

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $action The evaluation action. Valid options are "see" or "dontSee".
	 * @param string $name A cookie name.
	 * @param mixed  $value Optional. Cookie vale.
	 */
	public function __construct( $action, $name, $value ) {
		parent::__construct( $this->verifyAction( $action ) );

		$this->name  = $name;
		$this->value = $value;
	}

	/**
	 * Evaluate if the actor can or can't see a cookie.
	 *
	 * @access protected
	 * @param \WPAssure\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ) {
		$actor     = $this->getActor( $other );
		$webdriver = $actor->getWebDriver();

		$cookies = $webdriver->manage()->getCookies();
		foreach ( $cookies as $cookie ) {
			if ( $cookie['name'] === $this->name ) {
				if ( empty( $this->value ) ) {
					// if current action is "see" then return "true" what means the constrain is met,
					// otherwise it means that action is "dontSee" and the constrain isn't met, thus return "false"
					return self::ACTION_SEE === $this->action;
				}

				// if current action is "see" and cookie's value equals to what we are looking for,
				// then return "true" what means the constrain is met, otherwise it means that action is
				// "dontSee" or value doesn't match, thus return "false"
				return $cookie['value'] === $this->value && self::ACTION_SEE === $this->action;
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
		$message = sprintf( ' "%s" cookie', $this->name );
		if ( ! empty( $this->value ) ) {
			$message .= sprintf( ' with "%s" value', $this->value );
		}

		return $message;
	}

}
