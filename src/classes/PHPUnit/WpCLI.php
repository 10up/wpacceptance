<?php
/**
 * WP CLI helper to integratate with test class.
 *
 * @package  wpacceptance
 */

namespace WPAcceptance\PHPUnit;

use WPAcceptance\EnvironmentFactory;

/**
 * WP CLI utility runner to integrate with test class.
 */
trait WpCLI {

	/**
	 * Run command.
	 *
	 * @param  string $command A WP ClI command.
	 * @return array
	 */
	public function runCommand( $command ) {
		return EnvironmentFactory::get()->wpCliRunner( $this->sanitizeCommand( $command ) );
	}

	/**
	 * Sanitize command.
	 *
	 * @param  string $command The command to sanitize.
	 * @return string
	 */
	protected function sanitizeCommand( $command ) {
		if ( 0 === strpos( $command, 'wp' ) ) {
			$command = substr( $command, 2 );
		}

		return trim( $command );
	}
}
