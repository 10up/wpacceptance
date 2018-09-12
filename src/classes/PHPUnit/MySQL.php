<?php
/**
 * MySQL trait to add to test cases
 *
 * @package  wpassure
 */

namespace WPAssure\PHPUnit;

use \mysqli;
use WPAssure\EnvironmentFactory;

/**
 * MySQL trait
 */
trait MySQL {

	/**
	 * MySQL instance
	 *
	 * @access protected
	 * @var mysqli|boolean
	 */
	protected $_mysqli_instance = null;

	/**
	 * Return/create MySQL instance
	 *
	 * @return mysqli|boolean
	 */
	public function getMySQLInstance() {
		if ( null !== $this->_mysqli_instance ) {
			$mysql_creds = EnvironmentFactory::get()->getMySQLCredentials();

			$this->_mysqli_instance = new mysqli( $mysql_creds['DB_HOST'], $mysql_creds['DB_USER'], $mysql_creds['DB_PASSWORD'], $mysql_creds['DB_NAME'] );

			if ( $this->_mysqli_instance->connect_error ) {
				$this->_mysqli_instance = false;
			}
		}

		return $this->_mysqli_instance;
	}

}
