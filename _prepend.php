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

$config  = \Tk\Config::instance();
$factory = \App\Factory::instance();
$factory->set('composerClassLoader', $composer);
$system  = \Tk\System::instance();

// Define App Constants/Settings
include_once(__DIR__ . '/src/config/config.php');

\Tk\Factory::instance()->getBootstrap()->init();
