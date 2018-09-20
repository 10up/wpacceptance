<?php
/**
 * Check if string is inside something
 *
 * @package wpassure
 */

namespace WPAssure\PHPUnit\Constraints\Traits;

/**
 * Trait to be mixed with constraint
 */
trait StringOrPattern {

	/**
	 * Check if content contains a substring or matches a pattern.
	 *
	 * @access protected
	 * @param string $content The content to search in.
	 * @param string $string_or_pattern The string to search or pattern to match.
	 * @return boolean TRUE if the content contains a needle, otherwise FALSE.
	 */
	protected function findMatch( $content, $string_or_pattern ) {
		return preg_match( '#^/[^/]+/(\w?)$#', $string_or_pattern )
			? preg_match( $string_or_pattern, $content ) > 0
			: mb_stripos( $content, $string_or_pattern ) !== false;
	}

}
