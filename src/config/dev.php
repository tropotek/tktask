<?php
/**
 * Finalise system migration.
 * Called after the system DB has be migrated or mirrored.
 *
 */

// Production code




// dev env code
if (!\Tk\Config::isDev()) return;

foreach (\Bs\Auth::findAll() as $auth) {
    if (!$auth->active) continue;
    $auth->password = \Bs\Auth::hashPassword('password');
    $auth->save();
}