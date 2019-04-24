<?php
/**
 * Functionality used if WP Acceptance is used inside Gitlab. We do all this because GitLab runs docker in docker.
 *
 * @package wpacceptance
 */

namespace WPAcceptance;

/**
 * Gitlab singleton
 */
class GitLab {

	/**
	 * Are we inside gitlab or not
	 *
	 * @var boolean
	 */
	private $is_gitlab = false;

	/**
	 * Name of GitLab build volume
	 *
	 * @var string
	 */
	private $volume_name;

	/**
	 * Gitlab container ID
	 *
	 * @var string
	 */
	private $container_id;

	/**
	 * Path to wpsnapshots directory
	 *
	 * @var string
	 */
	private $snapshots_directory;

	/**
	 * Path to project directory
	 *
	 * @var string
	 */
	private $project_directory;

	/**
	 * Path to wpacceptance directory
	 *
	 * @var string
	 */
	private $wpacceptance_directory;

	/**
	 * Are we inside gitlab or not
	 *
	 * @return boolean
	 */
	public function isGitLab() {
		return $this->is_gitlab;
	}

	/**
	 * Get name of GitLab build build
	 *
	 * @return string
	 */
	public function getVolumeName() {
		return $this->volume_name;
	}

	/**
	 * Get wp snapshots directory
	 *
	 * @return string
	 */
	public function getSnapshotsDirectory() {
		return $this->snapshots_directory;
	}

	/**
	 * Get project directory
	 *
	 * @return string
	 */
	public function getProjectDirectory() {
		return $this->project_directory;
	}

	/**
	 * Get wp acceptance directory
	 *
	 * @return string
	 */
	public function getWPAcceptanceDirectory() {
		return $this->project_directory;
	}

	/**
	 * Setup singleton class
	 */
	private function __construct() {
		$this->is_gitlab    = ! empty( getenv( 'CI_CONFIG_PATH' ) );
		$this->container_id = exec( 'docker ps -q -f "label=com.gitlab.gitlab-runner.job.id=$CI_JOB_ID" -f "label=com.gitlab.gitlab-runner.type=build"' );

		if ( ! empty( $this->container_id ) ) {
			$this->volume_name = exec( 'docker inspect --format "{{ range .Mounts }}{{ if eq .Destination \"/builds/$CI_PROJECT_NAMESPACE\"}}{{ .Name }}{{ end }}{{ end }}" ' . $this->container_id );
		}

		$this->project_directory   = getenv( 'CI_PROJECT_NAME' );
		$this->snapshots_directory = '/builds/' . getenv( 'CI_PROJECT_NAMESPACE' ) . '/.wpsnapshots/';
	}

	/**
	 * Get gitlab pipeline ID
	 *
	 * @return  string
	 */
	public function getPipelineId() {
		return getenv( 'CI_PIPELINE_ID' );
	}

	/**
	 * Get singleton GitLab instance
	 *
	 * @return self
	 */
	public static function get() {
		static $instance;

		if ( empty( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}
}
