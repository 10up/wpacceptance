<?php
/**
 * Manage host file
 *
 * @package  wpacceptance
 */

namespace WPAcceptance;

/**
 * Host file class
 */
class HostFile {
	/**
	 * Host file entries
	 *
	 * @var array
	 */
	private $entries = [];

	/**
	 * Host file path
	 *
	 * @var string
	 */
	private $file_path;

	/**
	 * Setup host file class
	 *
	 * @param  string $file_path Path to hosts file
	 * @throws Exception\ReadHostFile Cannot read host file.
	 */
	public function __construct( string $file_path = '/etc/hosts' ) {
		$this->file_path = $file_path;

		$host_file_contents = file_get_contents( $this->file_path );

		if ( false === $host_file_contents ) {
			throw new Exception\ReadHostFile();
		}

		if ( ! empty( $host_file_contents ) ) {
			$entries = explode( "\n", $host_file_contents );

			foreach ( $entries as $entry ) {
				$entry = trim( $entry );

				if ( empty( $entry ) ) {
					continue;
				}

				$ip = preg_replace( '#^(.*?) .*$#', '$1', $entry );

				$hosts_raw = preg_replace( '#[\s]+#', ' ', preg_replace( '#^.*? (.*)$#', '$1', $entry ) );

				$hosts = explode( ' ', $hosts_raw );

				if ( empty( $hosts ) ) {
					continue;
				}

				if ( empty( $this->entries[ $ip ] ) ) {
					$this->entries[ $ip ] = [];
				}

				$this->entries[ $ip ] = array_merge( $this->entries[ $ip ], $hosts );
			}
		}
	}

	/**
	 * Add host file entry
	 *
	 * @param string       $ip   IP address
	 * @param string|array $hosts Host name(s)
	 */
	public function add( string $ip, $hosts ) {
		$hosts          = (array) $hosts;
		$prepared_hosts = [];

		foreach ( $hosts as $host ) {
			if ( $ip !== $this->getIpByHost( $host ) ) {
				$prepared_hosts[] = $host;
			}
		}

		if ( empty( $prepared_hosts ) ) {
			return;
		}

		if ( empty( $this->entries[ $ip ] ) ) {
			$this->entries[ $ip ] = [];
		}

		$this->entries[ $ip ][] = $prepared_hosts;

		exec( 'echo "' . $ip . ' ' . implode( ' ', $prepared_hosts ) . '" | sudo tee -a ' . $this->file_path );
	}

	/**
	 * Get hosts associated with an IP address
	 *
	 * @param  string $ip IP address
	 * @return string
	 */
	public function getHostsByIp( string $ip ) {
		if ( empty( $this->entries[ $ip ] ) ) {
			return null;
		}

		return $this->entries[ $ip ];
	}

	/**
	 * Get IP associated with a host
	 *
	 * @param  string $host Host name
	 * @return string
	 */
	public function getIpByHost( string $host ) {
		$return_ip = null;

		foreach ( $this->entries as $ip => $hosts ) {
			if ( in_array( $host, $hosts, true ) ) {
				$return_ip = $ip;
				break;
			}
		}

		return $return_ip;
	}
}
