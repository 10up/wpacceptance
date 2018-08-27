<?php
/**
 * Utility functions
 *
 * @package  wpassure
 */

namespace WPAssure\Utils;

/**
 * Find root of WP install (where wp-config.php resides)
 *
 * @return string|bool
 */
function get_wordpress_path() {
	$current_dir = getcwd() . '/';

	for ( $i = 0; $i < 15; $i++ ) {
		if ( file_exists( $current_dir . '/wp-config.php' ) ) {
			return $current_dir;
		}

		$current_dir .= '../';
	}

	return false;
}

/**
 * Add trailing slash to path
 *
 * @param  string $path Path
 * @return string
 */
function trailingslash( $path ) {
	return rtrim( $path, '/' ) . '/';
}

/**
 * Find an open port given a host
 *
 * @param  string $host  Host URL
 * @param  int    $start Start port number
 * @param  int    $end   End port number
 * @return boolean
 */
function find_open_port( $host, $start, $end ) {
	$port = $start;

	while ( $port >= $start && $port <= $end ) {
		if ( is_open_port( $host, $port ) ) {
			return $port;
		}

		$port++;
	}

	return false;
}

/**
 * Check if port is open given a host
 *
 * @param  string $host Host URL
 * @param  int    $port Port to check
 * @return boolean       [description]
 */
function is_open_port( $host, $port ) {
	$errno  = null;
	$errstr = null;

	$connection = @fsockopen( $host, $port, $errno, $errstr );

	if ( is_resource( $connection ) ) {
		fclose( $connection );
		return false;
	} else {
		return true;
	}
}
