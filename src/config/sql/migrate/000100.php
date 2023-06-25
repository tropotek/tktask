<?php

$registry = \App\Factory::instance()->getRegistry();
$registry->set('site.name', 'Tk Base Site - Tropotek');
$registry->set('site.name.short', 'TkBase');
$registry->set('site.email', 'site@email.com');
$registry->set('site.email.sig', '');
$registry->set('system.maintenance.enabled', '');
$registry->set('system.maintenance.message', '');
$registry->set('system.global.css', '');
$registry->set('system.global.js', '');
$registry->set('system.meta.description', 'A base development project site for the TK libs');
$registry->set('system.meta.keywords', '');
$registry->set('site.account.registration', 'site.account.registration');

// TODO: Set your projects registry defaults here
// ...

$registry->save();















