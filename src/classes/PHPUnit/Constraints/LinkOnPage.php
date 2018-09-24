<?php
/**
 * Test links on page constraint
 *
 * @package  wpassure
 */

namespace WPAssure\PHPUnit\Constraints;

use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\WebDriverBy;

/**
 * Constraint class
 */
class LinkOnPage extends \WPAssure\PHPUnit\Constraint {

	use Traits\StringOrPattern;

	/**
	 * A text of a link to look for.
	 *
	 * @access private
	 * @var string
	 */
	private $text = '';

	/**
	 * A url of a link to look for.
	 *
	 * @access private
	 * @var string
	 */
	private $url = '';

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $action The evaluation action. Valid options are "see" or "dontSee".
	 * @param string $text A text of a link to look for.
	 * @param string $url A url of a link to look for.
	 */
	public function __construct( $action, $text, $url ) {
		parent::__construct( $action );

		$this->text = $text;
		$this->url  = $url;
	}

	/**
	 * Evaluate if the actor can or can't see a link with specific text and url.
	 *
	 * @access protected
	 * @param \WPAssure\PHPUnit\Actor $other The actor instance.
	 * @return boolean TRUE if the constrain is met, otherwise FALSE.
	 */
	protected function matches( $other ): bool {
		$actor = $this->getActor( $other );
		$by    = WebDriverBy::partialLinkText( $this->text );

		try {
			$elements = $actor->getElements( $by );
			if ( ! empty( $this->url ) ) {
				foreach ( $elements as $element ) {
					$href = $element->getAttribute( 'href' );
					if ( $this->findMatch( $href, $this->url ) ) {
						return self::ACTION_SEE === $this->action;
					}
				}

				return self::ACTION_DONTSEE === $this->action;
			} else {
				return self::ACTION_SEE === $this->action;
			}
		} catch ( NoSuchElementException $e ) {
			return self::ACTION_DONTSEE === $this->action;
		}

		return false;
	}

	/**
	 * Return description of the failure.
	 *
	 * @access public
	 * @return string The description text.
	 */
	public function toString(): string {
		$message = sprintf( ' a link with "%s" text', $this->text );
		if ( ! empty( $this->url ) ) {
			$message .= sprintf( ' that contains "%s" url in the href attribute', $this->url );
		}

		return $message;
	}

}
