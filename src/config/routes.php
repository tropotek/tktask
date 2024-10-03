<?php
/**
 * Site Routes
 */

use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;

/**
 * Remember to refresh the cache after editing,
 * Reload the page with <Ctrl>+<Shift>+R
 *
 * @see https://symfony.com/doc/current/routing.html
 */
return function (CollectionConfigurator $routes) {

    // Public
    $routes->add('home-base', '/')
        ->controller([\App\Controller\Home::class, 'doDefault']);
    $routes->add('home', '/home')
        ->controller([\App\Controller\Home::class, 'doDefault']);
    $routes->add('contact', '/contact')
        ->controller([\App\Controller\Contact::class, 'doDefault']);

    // User Public
    $routes->add('login', '/login')
        ->controller([\App\Controller\User\Login::class, 'doLogin']);
    $routes->add('logout', '/logout')
        ->controller([\App\Controller\User\Login::class, 'doLogout']);
    $routes->add('login-ssi', '/_ssi')
        ->controller([\App\Controller\User\Ssi::class, 'doDefault']);
    $routes->add('recover', '/recover')
        ->controller([\App\Controller\User\Recover::class, 'doDefault']);
    $routes->add('recover-pass', '/recoverUpdate')
        ->controller([\App\Controller\User\Recover::class, 'doRecover']);
    $routes->add('register-activate', '/registerActivate')
        ->controller([\App\Controller\User\Register::class, 'doActivate']);
    $routes->add('register', '/register')
        ->controller([\App\Controller\User\Register::class, 'doDefault']);

    // User Member
    $routes->add('user-dashboard', '/dashboard')
        ->controller([\App\Controller\Dashboard::class, 'doDefault']);
    $routes->add('user-profile', '/profile')
        ->controller([\App\Controller\User\Profile::class, 'doDefault']);

    // User Admin
    $routes->add('settings-edit', '/settings')
        ->controller([\App\Controller\Admin\Settings::class, 'doDefault']);
    $routes->add('user-type-manager', '/user/{type}Manager')
        ->controller([\App\Controller\User\Manager::class, 'doByType'])
        ->defaults(['type' => \App\Db\User::TYPE_MEMBER]);
    $routes->add('user-type-edit', '/user/{type}Edit')
        ->controller([\App\Controller\User\Edit::class, 'doDefault'])
        ->defaults(['type' => \App\Db\User::TYPE_MEMBER]);

    // Filesystem
    $routes->add('file-manager', '/fileManager')
        ->controller([\App\Controller\File\Manager::class, 'doDefault']);


    // Component test page
    $routes->add('test-component', '/componentTest')
        ->controller([\App\Controller\Test\Component::class, 'doDefault']);
    $routes->add('component-test', '/component/test')
        ->controller([\App\Component\Test::class, 'doDefault']);


    // API
    $routes->add('api-notify', '/api/notify/getNotifications')
        ->controller([\App\Api\Notify::class, 'doGetNotifications']);
};