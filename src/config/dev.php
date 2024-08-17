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

$config = \Tk\Config::instance();

if (!$config->isDebug()) {
    error_log(__FILE__ . ': Do not execute this file in a production environment!');
    return;
}

/** @var \Bs\Db\User $user */
foreach (\Bs\Db\User::findAll() as $user) {
    $user->password = \Bs\Db\User::hashPassword('password');
    $user->save();
}