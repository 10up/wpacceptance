<?php
/**
 * Bootstrap WPAssure
 *
 * @package  wpassure
 */

namespace WPAssure;

use \Symfony\Component\Console\Application;

$app = new Application( 'WPAssure', '0.9' );

define( 'ASSUREWP_DIR', __DIR__ );

/**
 * Attempt to set this as WPAssure can consume a lot of memory.
 */
ini_set( 'memory_limit', '-1' );

/**
 * Register commands
 */
$app->add( new Command\Run() );

$app->run();
