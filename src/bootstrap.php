<?php
/**
 * Bootstrap AssureWP
 *
 * @package  assurewp
 */

namespace AssureWP;

use \Symfony\Component\Console\Application;

$app = new Application( 'AssureWP', '0.9' );

define( 'ASSUREWP_DIR', __DIR__ );

/**
 * Attempt to set this as AssureWP can consume a lot of memory.
 */
ini_set( 'memory_limit', '-1' );

/**
 * Register commands
 */
$app->add( new Command\Run() );

$app->run();
