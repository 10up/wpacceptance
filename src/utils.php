<?php
/**
 * Utility functions
 *
 * @package  wpacceptance
 */

namespace WPAcceptance\Utils;

/**
 * Check if content contains a substring or matches a pattern.
 *
 * @param string $content The content to search in.
 * @param string $string_or_pattern The string to search or pattern to match.
 * @return boolean TRUE if the content contains a needle, otherwise FALSE.
 */
function find_match( $content, $string_or_pattern ) {
	return preg_match( '#^/.*/$#', $string_or_pattern )
		? preg_match( $string_or_pattern, $content ) > 0
		: mb_stripos( $content, $string_or_pattern ) !== false;
}

/**
 * Find root of WP install (where wp-config.php resides)
 *
 * @param  string $path Directory to start searching from. Defaults to cwd
 * @return string|bool
 */
function get_wordpress_path( $path = '' ) {
	if ( ! empty( $path ) ) {
		$path = trailingslash( getcwd() );
	}

	for ( $i = 0; $i < 15; $i++ ) {
		if ( file_exists( $path . '/wp-config.php' ) ) {
			return $path;
		}

		$path .= '../';
	}

	return false;
}

/**
 * Resolve absolute path that contains ../ or ./
 *
 * @param string $path Path string
 * @return string
 */
function resolve_absolute_path( $path ) {
	// Remove spaces
	$path = str_replace( ' ', '', $path );

	// Remove trailing .
	$path = preg_replace( '#(.*)/.$#', '$1', $path );

	// Remove /./
	$path = str_replace( '/./', '/', $path );

	// Remove //
	$path = preg_replace( '#[/]+#', '/', $path );

	$path_parts = explode( '/', $path );

	$final_path_parts = [];

	foreach ( $path_parts as $path_part ) {
		if ( '..' === $path_part ) {
			array_pop( $final_path_parts );
		} elseif ( ! empty( $path_part ) ) {
			$final_path_parts[] = $path_part;
		}
	}

	if ( empty( $final_path_parts ) ) {
		return '/';
	}

	return '/' . implode( '/', $final_path_parts ) . '/';
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
 * @param  string $cwd Override current working directory. Must be absolute.
 * @return string
 */
function normalize_path( $path, $cwd = null ) {
	$path = trim( $path );

	if ( empty( $cwd ) ) {
		$cwd = getcwd();
	}

	$cwd = trim( trailingslash( $cwd ) );

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
		$path = rtrim( $cwd, '/' ) . '/' . substr( $path, 2 );
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
