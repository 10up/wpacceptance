<?php
/**
 * Bootstrap WP Acceptance
 *
 * @package  wpacceptance
 */

namespace WPAcceptance;

use \Symfony\Component\Console\Application;

$app = new Application( 'WPAcceptance', '0.16.7' );

define( 'WPACCEPTANCE_DIR', dirname( __DIR__ ) );

/**
 * Attempt to set this as WP Acceptance can consume a lot of memory.
 */
ini_set( 'memory_limit', '-1' );

if ( GitLab::get()->isGitLab() ) {
	$snapshots_dir = str_replace( ' ', '\ ', GitLab::get()->getSnapshotsDirectory() );

	putenv( 'WPSNAPSHOTS_DIR=' . $snapshots_dir );
}

EnvironmentFactory::init();

/**
 * Register commands
 */
$app->add( new Command\Init() );
$app->add( new Command\Run() );
$app->add( new Command\Destroy() );

$app->run();
