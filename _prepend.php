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

\Tk\Factory::instance()->getBootstrap()->init();
