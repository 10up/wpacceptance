<?php

namespace WPAssure\PHPUnit\WebDriver;

use WPAssure\Log;

trait Popup {

	/**
	 * Accepts the current native popup window created by window.alert, window.confirm,
	 * window.prompt fucntions.
	 * 
	 * @access public
	 */
	public function acceptPopup() {
		$this->getWebDriver()->switchTo()->alert()->accept();
		Log::instance()->write( 'Accepted the current popup.', 1 );
	}

	/**
	 * Dismisses the current native popup window created by window.alert, window.confirm,
	 * window.prompt fucntions.
	 * 
	 * @access public
	 */
	public function cancelPopup() {
		$this->getWebDriver()->switchTo()->alert()->dismiss();
		Log::instance()->write( 'Dismissed the current popup.', 1 );
	}

}
