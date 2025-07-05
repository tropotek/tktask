<?php
/**
 * Bootstrap System.
 *
 * Load this file when running any script to
 * set up and bootstrap the system environment
 */
$composer = include __DIR__ . '/vendor/autoload.php';

define('TKAPP', true);

// Init Tk System Objects
$config  = \Tk\Config::instance();
$factory = \App\Factory::instance();

$factory->set('composerLoader', $composer);
\Bs\Factory::instance()->getBootstrap()->init();
