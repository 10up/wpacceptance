<?php
/**
 * Bootstrap WPAssure
 *
 * @package  wpassure
 */

namespace WPAssure;

use \Symfony\Component\Console\Application;

$app = new Application( 'WPAssure', '0.9.1' );

define( 'WPASSURE_DIR', dirname( __DIR__ ) );

/**
 * Attempt to set this as WPAssure can consume a lot of memory.
 */
ini_set( 'memory_limit', '-1' );

if ( GitLab::get()->isGitLab() ) {
	putenv( 'WPSNAPSHOTS_DIR=' . GitLab::get()->getSnapshotsDirectory() );
}

/**
 * Register commands
 */
$app->add( new Command\Init() );
$app->add( new Command\Run() );
$app->add( new Command\Destroy() );

$app->run();
