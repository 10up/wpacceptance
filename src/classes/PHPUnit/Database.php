<?php
/**
 * Database (MySQL) helper functionality to integrate with test class
 *
 * @package  wpacceptance
 */

namespace WPAcceptance\PHPUnit;

use WPAcceptance\EnvironmentFactory;
use WPAcceptance\Log;

/**
 * Database trait
 */
trait Database {

	/**
	 * Get the last INSERT, UPDATE, or DELETE that happened in the MySQL server
	 *
	 * @return array|null
	 */
	protected function getLastModifyingQuery() {
		$mysql = EnvironmentFactory::get()->getMySQLClient();

		$result = $mysql->query( "SELECT * FROM mysql.general_log WHERE command_type = 'Query' AND user_host LIKE '%wpa-wordpress%' AND argument REGEXP '^(UPDATE|INSERT|DELETE).*' ORDER BY event_time DESC LIMIT 1" );

		if ( ! $result ) {
			Log::instance()->write( 'Query error: ' . $this->mysqli_instance->error, 2 );
		}

		return $result->fetch_assoc();
	}

	/**
	 * Use new database if the current one is dirty
	 *
	 * @param  boolean $force Force new DB to be used
	 */
	protected function ensureCleanDatabase( $force = false ) {
		$config = EnvironmentFactory::get()->getSuiteConfig();

		if ( ! empty( $config['disable_clean_db'] ) ) {
			return;
		}

		$new_last_modifying_query = $this->getLastModifyingQuery();

		if ( $force ) {
			Log::instance()->write( 'Forcing clean database.', 1 );

			EnvironmentFactory::get()->makeCleanDB();
		} elseif ( ! empty( $new_last_modifying_query ) && $new_last_modifying_query['event_time'] !== $this->last_modifying_query['event_time'] ) {
			Log::instance()->write( 'Database has been modified. Setting up clean database.' );
			Log::instance()->write( 'Last query at ' . $new_last_modifying_query['event_time'] . ': ' . $new_last_modifying_query['argument'], 2 );

			EnvironmentFactory::get()->makeCleanDB();
		}
	}

	/**
	 * Execute MySQL query and return results.
	 *
	 * @static
	 * @access protected
	 * @param string $query A query to execute.
	 * @return \mysqli_result Results of execution.
	 */
	protected static function query( $query ) {
		$mysql = EnvironmentFactory::get()->getMySQLClient();

		// @todo: log query
		return $mysql->query( $query );
	}

	/**
	 * Return table name with proper prefix.
	 *
	 * @static
	 * @access protected
	 * @param string $table A table name without a prefix.
	 * @return string A table name with a prefix.
	 */
	protected static function getTableName( $table ) {
		return EnvironmentFactory::get()->getMySQLClient()->getTablePrefix() . $table;
	}

	/**
	 * Return table name with proper prefix.
	 *
	 * @static
	 * @access protected
	 * @return string
	 */
	protected static function getCurrentDatabaseName() {
		return EnvironmentFactory::get()->getMySQLCredentials()['DB_NAME'];
	}

	/**
	 * Parse clauses and return WHERE statements for a query.
	 *
	 * Clauses should look something like this:
	 * [
	 *   [
	 *     'key'   => 'column_name',
	 *     'value' => 'col value',
	 *   ],
	 *   [
	 *     'key'   => 'column_name2',
	 *     'value' => 'col value2',
	 *   ],
	 *   [
	 *     'key'        => 'column_name3',
	 *     'value'      => 'col value3',
	 *     'compare' => 'like'
	 *   ],
	 *   [
	 *     [
	 *       'key'   => 'column_name4',
	 *       'value' => 'col value4',
	 *     ],
	 *     [
	 *       'key'        => 'column_name5',
	 *       'value'      => 'col value5',
	 *     ],
	 *     'relation' => 'or',
	 *   ]
	 *   'relation' => 'and',
	 * ]
	 *
	 * @static
	 * @access protected
	 * @param array $clauses Array of clauses.
	 * @return string
	 */
	protected static function parseWhereClauses( array $clauses ) {
		$mysql    = EnvironmentFactory::get()->getMySQLClient();
		$relation = ! empty( $clauses['relation'] ) ? strtolower( $clauses['relation'] ) : 'and';

		$prepared_clause = '';

		foreach ( $clauses as $condition ) {
			if ( is_array( $condition ) ) {
				if ( ! empty( $prepared_clause ) ) {
					$prepared_clause .= " $relation ";
				}

				if ( ! empty( $condition['key'] ) ) {
					if ( isset( $condition['value'] ) ) {
						$compare = ( ! empty( $condition['compare'] ) ) ? strtolower( $condition['compare'] ) : '=';

						$condition_string = sprintf( '`%s` ' . $compare . ' ', $mysql->escape( $condition['key'] ) );

						if ( 'like' === $compare ) {
							$condition_string .= '"%' . $mysql->escape( $condition['value'] ) . '%"';
						} elseif ( 'in' === $compare ) {
							$values            = array_map( array( $mysql, 'escape' ), (array) $condition['value'] );
							$condition_string .= '("' . implode( '", "', $values ) . '")';
						} else {
							$condition_string .= '"' . $mysql->escape( $condition['value'] ) . '"';
						}

						$prepared_clause .= $condition_string;
					}
				} else {
					reset( $condition );

					if ( is_array( $condition[ key( $condition ) ] ) ) {
						$prepared_clause .= ' ( ' . static::parseWhereClauses( $condition[ key( $condition ) ] ) . ' ) ';
					}
				}
			}
		}

		if ( empty( $prepared_clause ) ) {
			return '';
		}

		return '(' . $prepared_clause . ')';
	}

	/**
	 * Return ID of the latest post in the posts table.
	 *
	 * Valid arguments:
	 *   post_type   - (array|string) a post type or array of types.
	 *   post_status - (array|string) a post status or array of statuses.
	 *
	 * @access public
	 * @param array $args Array of arguments.
	 * @return int The latest post ID if found, otherwise FALSE.
	 */
	public static function getLastPostId( array $args = array() ) {
		$table = static::getTableName( 'posts' );

		if ( empty( $args ) ) {
			$args = [
				[
					'key'   => 'post_type',
					'value' => 'post',
				],
				[
					'key'   => 'post_status',
					'value' => 'publish',
				],
			];
		}

		$where = static::parseWhereClauses( $args );

		if ( ! empty( $where ) ) {
			$where = ' WHERE ' . $where;
		}

		$results = self::query( "SELECT ID FROM {$table}{$where} ORDER BY `ID` DESC LIMIT 1" )->fetch_assoc();

		if ( ! empty( $results ) ) {
			return (int) $results['ID'];
		}

		return false;
	}

	/**
	 * Assert post field contains a string
	 *
	 * @param  int    $post_id Post id
	 * @param  string $field   Post field name
	 * @param  mixed  $value   Value to compare against field
	 */
	public static function assertPostFieldContains( $post_id, $field, $value ) {
		$table = static::getTableName( 'posts' );

		$where = static::parseWhereClauses(
			[
				[
					'key'   => 'ID',
					'value' => $post_id,
				],
				[
					'key'   => $field,
					'value' => $value,
				],
			]
		);

		if ( ! empty( $where ) ) {
			$where = ' WHERE ' . $where;
		}

		$results = self::query( "SELECT * FROM {$table}{$where} ORDER BY `ID` DESC LIMIT 1" )->fetch_assoc();

		if ( empty( $results ) ) {
			static::fail( 'Post not found.' );
		} else {
			static::assertTrue( (bool) preg_match( '#' . $value . '#i', $results[ $field ] ) );
		}
	}

	/**
	 * Assert if new posts exist after a post with provided id. Use "self::getLastPostId(...)" to get the current latest ID.
	 *
	 * @static
	 * @access public
	 * @param int    $since_post_id A post id to compare new posts with.
	 * @param array  $args Array of arguments that has been used to receive the latest post id.
	 * @param string $message Optinal. A message to use on failure.
	 */
	public static function assertNewPostsExist( $since_post_id, array $args = array(), $message = '' ) {
		$new_last_id = self::getLastPostId( $args );

		if ( empty( $message ) ) {
			$message = 'The latest ID must be bigger than provided post ID.';
		}

		static::assertGreaterThan( (int) $since_post_id, (int) $new_last_id, $message );
	}

	/**
	 * Assert that a post exists in the database.
	 *
	 * Valid arguments:
	 *   post_title  - (array|string) a post title or array of titles.
	 *   post_type   - (array|string) a post type or array of types.
	 *   post_status - (array|string) a post status or array of statuses.
	 *
	 * @param array  $args Array of arguments.
	 * @param string $message Optinal. A message to use on failure.
	 */
	public static function assertPostExists( array $args, $message = '' ) {
		$post_id = static::getLastPostId( $args );

		if ( empty( $message ) ) {
			$message = 'A post must exist in the database.';
		}

		static::assertGreaterThan( 0, (int) $post_id, $message );
	}

	/**
	 * Get row(s) with matching column value(s)
	 *
	 * @param  array  $conditions Conditions to look up. Conditions should look like
	 *                            [
	 *                            'column_name'  => 'value',
	 *                            'column_name2' => 'value2',
	 *                            ]
	 * @param  string $table_name Name of table
	 * @return mixed
	 */
	public static function selectRowsWhere( array $conditions, string $table_name ) {
		$table = static::getTableName( $table_name );

		$query = "SELECT * FROM {$table} WHERE 1=1 ";

		foreach ( $conditions as $condition => $value ) {
			$query .= ' AND `' . addcslashes( $condition, '`' ) . '` = "' . addcslashes( $value, '"' ) . '"';
		}

		$results = self::query( $query );

		$rows = [];

		while ( $row = $results->fetch_assoc() ) { // phpcs:ignore
			$rows[] = $row;
		}

		if ( empty( $rows ) ) {
			return null;
		}

		return 1 === count( $rows ) ? $rows[0] : $rows;
	}

	/**
	 * Get row(s) with matching column value(s)
	 *
	 * @param  array  $updates_by_column Updates to make by column
	 *                            [
	 *                            'column_name'  => 'new value',
	 *                            'column_name2' => 'new value2',
	 *                            ]
	 * @param  array  $where_conditions Conditions to look up. Conditions should look like
	 *                            [
	 *                            'column_name'  => 'value',
	 *                            'column_name2' => 'value2',
	 *                            ]
	 * @param  string $table_name Name of table
	 * @return boolean|integer
	 */
	public static function updateRowsWhere( array $updates_by_column, array $where_conditions, string $table_name ) {
		$table = static::getTableName( $table_name );

		$conditions = '';

		foreach ( $where_conditions as $condition => $value ) {
			$conditions .= ' AND `' . addcslashes( $condition, '`' ) . '` = "' . addcslashes( $value, '"' ) . '"';
		}

		$updates = '';

		foreach ( $updates_by_column as $column => $value ) {
			if ( ! empty( $updates ) ) {
				$updates .= ', ';
			}

			if ( is_array( $value ) || is_object( $value ) ) {
				$value = serialize( $value );
			}

			$value = mysqli_real_escape_string( EnvironmentFactory::get()->getMySQLClient()->getMySQLInstance(), $value );

			$updates .= "`$column`='$value' ";
		}

		$query = "UPDATE {$table} SET $updates WHERE 1=1 $conditions";

		return self::query( $query );
	}

	/**
	 * Create a WP post
	 *
	 * @param  array $args Post table column args
	 * @return boolean
	 */
	public static function createPost( array $args = [] ) {
		$mysql = EnvironmentFactory::get()->getMySQLClient();

		$table = static::getTableName( 'posts' );

		$defaults = [
			'post_author'           => 1,
			'post_date'             => date( 'Y-m-d H:i:s' ),
			'post_date_gmt'         => date( 'Y-m-d H:i:s' ),
			'post_modified'         => date( 'Y-m-d H:i:s' ),
			'post_modified_gmt'     => date( 'Y-m-d H:i:s' ),
			'post_content'          => 'Test post content',
			'post_content_filtered' => 'Test post content',
			'post_title'            => 'Test Post',
			'post_name'             => 'test-post',
			'post_excerpt'          => 'Test post excerpt',
			'post_status'           => 'publish',
			'post_type'             => 'post',
			'to_ping'               => '',
			'ping'                  => '',
		];

		if ( ! empty( $args['post_content'] ) && empty( $args['post_content_filtered'] ) ) {
			$args['post_content_filtered'] = $args['post_content'];
		}

		$values = '';

		foreach ( $defaults as $key => $value ) {
			if ( isset( $args[ $key ] ) ) {
				$value = $args[ $key ];
			}

			if ( ! empty( $values ) ) {
				$values .= ', ';
			}

			$values .= '"' . $mysql->escape( $value ) . '"';
		}

		$query = "INSERT INTO {$table} (post_author, post_date, post_date_gmt, post_modified, post_modified_gmt, post_content, post_content_filtered, post_title, post_name, post_excerpt, post_status, post_type, to_ping, pinged) VALUES ({$values})";

		return self::query( $query );
	}

}
