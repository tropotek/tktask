<?php
/**
 * Bootstrap System.
 *
 * Load this file when running any script to
 * set up and bootstrap the system environment
 *
 * @author Tropotek <http://www.tropotek.com/>
 */

$composer = include __DIR__ . '/vendor/autoload.php';

// Init Tk System Objects
// Update these calls here if you want to override them...
//$config  = \App\Config::instance();
$factory = \App\Factory::instance();
$factory->set('classLoader', $composer);
//$system  = \App\System::instance();
//$registry  = \App\Registry::instance();


// Define App Constants/Settings
include_once(__DIR__ . '/src/config/config.php');
// TODO: We could use a priority number in libs to
//       run an auto config App config always runs last.
//          eg: `{priority}-config.php`
//      ie: tk-base/config/40-config.php, tk-framework/config/50-config.php, tk-base/config/80-config.php, ui-lib/config/100-config.php
//      Also we could do the same with the routes or a package/bundle system


\Tk\Factory::instance()->getBootstrap()->init();
