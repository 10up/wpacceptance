<?php

namespace WPAssure\PHPUnit\WebDriver;

use WPAssure\Log;

trait Screenshot {

	/**
	 * Takes a screenshot of the viewport.
	 *
	 * @access public
	 * @param string $name A filename without extension.
	 */
	public function takeScreenshot( $name = null ) {
		if ( empty( $name ) ) {
			$name = uniqid( date( 'Y-m-d_H-i-s_' ) );
		}

		$filename = $name . '.jpg';
		$this->getWebDriver()->takeScreenshot( $filename );
		Log::instance()->write( 'Screenshot saved to ' . $filename, 1 );
	}

}
