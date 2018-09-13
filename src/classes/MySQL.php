<?php
/**
 * MySQL client to use in tests
 *
 * @package  wpassure
 */

namespace WPAssure;

use \mysqli;

/**
 * MySQL client
 */
class MySQL {
	/**
	 * MySQL instance
	 *
	 * @access protected
	 * @var mysqli|boolean
	 */
	protected $_mysqli_instance = null;

	/**
	 * MySQL table prefix
	 *
	 * @access protected
	 * @var string
	 */
	protected $_table_prefix;

	/**
	 * External MySQL port
	 *
	 * @var int
	 */
	protected $_port;

	/**
	 * Setup MySQL
	 *
	 * @param  array  $mysql_creds MySQL creds
	 * @param  int    $port External MySQL port
	 * @param  string $table_prefix Table prefix to use in queries
	 */
	public function __construct( $mysql_creds, $port, $table_prefix ) {
		$this->_mysqli_instance = new mysqli( '127.0.0.1:' . $port, $mysql_creds['DB_USER'], $mysql_creds['DB_PASSWORD'], $mysql_creds['DB_NAME'] );

		if ( $this->_mysqli_instance->connect_error ) {
			$this->_mysqli_instance = false;
		}

		$this->_table_prefix = $table_prefix;

		$this->_port = $port;
	}

	/**
	 * Proxy query to MySQL
	 *
	 * @param  string $query Query string
	 * @return \mysqli_result
	 */
	public function query( $query ) {
		Log::instance()->write( 'Running MySQL query: ' . $query, 2 );

		$result = $this->_mysqli_instance->query( $query );

		if ( ! $result ) {
			Log::instance()->write( 'Query error: ' . $this->_mysqli_instance->error, 2 );
		}

		return $result;
	}

	/**
	 * Escape SQL for use in query.
	 *
	 * @param  string|array $data Data to escape
	 * @return string|array
	 */
	public function escape( $data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $k => $v ) {
				if ( is_array( $v ) ) {
					$data[ $k ] = $this->escape( $v );
				} else {
					$data[ $k ] = $this->_mysqli_instance->escape_string( $v );
				}
			}
		} else {
			$data = $this->_mysqli_instance->escape_string( $data );
		}

		return $data;
	}

	/**
	 * Return/create MySQL instance
	 *
	 * @return mysqli|boolean
	 */
	public function getMySQLInstance() {
		return $this->_mysqli_instance;
	}

	/**
	 * Get MySQL table prefix
	 *
	 * @return string
	 */
	public function getTablePrefix() {
		return $this->_table_prefix;
	}
}
