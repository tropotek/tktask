<?php
/**
 * Set up the dev environment.
 *
 * It will also run after a mirror command is called
 *   and the system is in debug mode.
 *
 * It can be executed from the cli command
 *   `./bin/cmd debug`
 */

$config = \Tk\Config::instance();

if (!$config->isDebug()) {
    error_log(__FILE__ . ': Do not execute this file in a production environment!');
    return;
}
//vd('running dev script');

/** @var \App\Db\User $user */
foreach (\App\Db\UserMap::create()->findAll() as $user) {
    $user->setPassword(\App\Db\User::hashPassword('password', PASSWORD_DEFAULT));
    $user->save();
}

/*
-- --------------------------------------
-- Change all passwords to 'password' for debug mode
-- --------------------------------------

-- Salted
-- UPDATE `user` SET `password` = MD5(CONCAT('password', `hash`));

-- Unsalted
-- UPDATE `user` SET `password` = MD5('password');
*/
