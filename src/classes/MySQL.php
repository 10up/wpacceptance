<?php
/**
 * MySQL client to use in tests
 *
 * @package  wpacceptance
 */

namespace WPAcceptance;

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
	protected $mysqli_instance = null;

	/**
	 * MySQL table prefix
	 *
	 * @access protected
	 * @var string
	 */
	protected $table_prefix;

	/**
	 * External MySQL port
	 *
	 * @var int
	 */
	protected $port;

	/**
	 * IP address for mysql
	 *
	 * @var string
	 */
	protected $ip;

	/**
	 * Setup MySQL
	 *
	 * @param  array  $mysql_creds MySQL creds
	 * @param  string $ip IP address
	 * @param  int    $port External MySQL port
	 * @param  string $table_prefix Table prefix to use in queries
	 */
	public function __construct( $mysql_creds, $ip, $port, $table_prefix ) {
		$this->mysqli_instance = new mysqli( $ip . ':' . $port, $mysql_creds['DB_USER'], $mysql_creds['DB_PASSWORD'], $mysql_creds['DB_NAME'] );

		if ( $this->mysqli_instance->connect_error ) {
			$this->mysqli_instance = false;
		}

		$this->table_prefix = $table_prefix;

		$this->port = $port;
		$this->ip   = $ip;
	}

	/**
	 * Proxy query to MySQL
	 *
	 * @param  string $query Query string
	 * @return \mysqli_result
	 */
	public function query( $query ) {
		Log::instance()->write( 'Running MySQL query: ' . $query, 2 );

		$result = $this->mysqli_instance->query( $query );

		if ( ! $result ) {
			Log::instance()->write( 'Query error: ' . $this->mysqli_instance->error, 2 );
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
					$data[ $k ] = $this->mysqli_instance->escape_string( $v );
				}
			}
		} else {
			$data = $this->mysqli_instance->escape_string( $data );
		}

		return $data;
	}

	/**
	 * Return/create MySQL instance
	 *
	 * @return mysqli|boolean
	 */
	public function getMySQLInstance() {
		return $this->mysqli_instance;
	}

	/**
	 * Get MySQL table prefix
	 *
	 * @return string
	 */
	public function getTablePrefix() {
		return $this->table_prefix;
	}
}
