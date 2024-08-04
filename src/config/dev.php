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
//vd('running dev script');

/** @var \Bs\Db\User $user */
foreach (\Bs\Db\UserMap::create()->findAll() as $user) {
    $user->setPassword(\Bs\Db\User::hashPassword('password'));
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
