<?php
/**
 * Set up the dev environment.
 *
 * It will also run after a mirror command is called
 *   and the system is in debug mode.
 *
 * It can be executed from the cli command
 *   `./bin/cmd debug`
 *
 */

if (!\Tk\Config::isDebug() || \Tk\Config::isProd()) {
    error_log(__FILE__ . ': Do not execute this file in a production environment!');
    return;
}

foreach (\Bs\Auth::findAll() as $auth) {
    $auth->password = \Bs\Auth::hashPassword('password');
    $auth->save();
}