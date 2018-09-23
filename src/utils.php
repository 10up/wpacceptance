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
 * Validator for slugs
 *
 * @param  string $answer Answer to validate
 * @throws \RuntimeException Exception to throw if answer isn't valid.
 * @return string
 */
function slug_validator( $answer ) {
	if ( ! preg_match( '#^[a-z0-9\-_]+$#i', $answer ) ) {
		throw new \RuntimeException(
			'A valid non-empty slug is required (letters, numbers, -, and _).'
		);
	}
	return strtolower( $answer );
}

/**
 * Normalizes paths. Note that we DO always add a trailing slash here
 *
 * /
 * ./
 * ~/
 * ./test/
 * ~/test
 * test
 *
 * @param  string $path Path to normalize
 * @return string
 */
function normalize_path( $path ) {
	$path = trim( $path );

	if ( '/' === $path ) {
		return $path;
	}

	/**
	 * Prepend ./ to non absolute paths
	 */
	if ( preg_match( '#[^\./\\\~]#i', substr( $path, 0, 1 ) ) ) {
		$path = './' . $path;
	}

	/**
	 * Make non-absolute path absolute
	 */
	if ( './' === substr( $path, 0, 2 ) ) {
		$path = rtrim( getcwd(), '/' ) . '/' . substr( $path, 2 );
	}

	/**
	 * Replace ~ with home directory
	 */
	if ( '~' === substr( $path, 0, 1 ) ) {
		$path = ltrim( $path, '~' );

		$home = rtrim( $_SERVER['HOME'], '/' );

		$path = $home . $path;
	}

	return trailingslash( $path );
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
