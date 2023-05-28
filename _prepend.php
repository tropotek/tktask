<?php
/**
 * Bootstrap System.
 *
 * Load this file when running any script to
 * set up and bootstrap the system environment
 */

$composer = include __DIR__ . '/vendor/autoload.php';

// Init Tk System Objects
// Update these calls here if you want to override them...
//$config  = \Tk\Config::instance();
$factory = \App\Factory::instance();
$factory->set('classLoader', $composer);
//$system  = \App\System::instance();
//$registry  = \App\Registry::instance();

\Tk\Factory::instance()->getBootstrap()->init();
