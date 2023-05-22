<?php
/**
 * Remember to refresh the cache after editing
 *
 * Reload the page with <Ctrl>+<Shift>+R
 */

use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;

// @see https://symfony.com/doc/current/routing.html
return function (CollectionConfigurator $routes) {

    $routes->add('home-base', '/')
        ->controller([\App\Controller\Home::class, 'doDefault']);
    $routes->add('home', '/home')
        ->controller([\App\Controller\Home::class, 'doDefault']);
    $routes->add('user-dashboard', '/dashboard')
        ->controller([\App\Controller\Dashboard::class, 'doDefault']);

    // Auth pages Login, Logout, Register, Recover
    $routes->add('login', '/login')
        ->controller([\App\Controller\Login::class, 'doLogin']);
    $routes->add('logout', '/logout')
        ->controller([\App\Controller\Login::class, 'doLogout']);
    $routes->add('recover', '/recover')
        ->controller([\App\Controller\Recover::class, 'doDefault']);
    $routes->add('recover-pass', '/recoverUpdate')
        ->controller([\App\Controller\Recover::class, 'doRecover']);
    $routes->add('register', '/register')
        ->controller([\App\Controller\Register::class, 'doDefault']);
    $routes->add('register-activate', '/registerActivate')
        ->controller([\App\Controller\Register::class, 'doActivate']);


    // API Endpoints
    $routes->add('api-htmx-alert', '/api/htmx/alert')
        ->controller([\App\Api\App::class, 'doAlert'])
        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_GET]);
    $routes->add('api-htmx-toast', '/api/htmx/toast')
        ->controller([\App\Api\App::class, 'doToast'])
        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_GET]);

    $routes->add('settings-edit', '/settings')
        ->controller([\App\Controller\Admin\Settings::class, 'doDefault']);


    // Test routes (Remove for production sites and delete /src/Controller/Examples folder)
    $routes->add('staff-manager', '/staffManager')
        ->controller([\App\Controller\User\Manager::class, 'doDefault']);
    $routes->add('user-manager', '/userManager')
        ->controller([\App\Controller\User\Manager::class, 'doDefault']);
    $routes->add('user-edit', '/userEdit/{id}')
        ->controller([\App\Controller\User\Edit::class, 'doDefault'])
        ->defaults(['id' => 0]);
    // HTMX edit
//    $routes->add('user-edit-x', '/userEditX/{id}')
//        ->controller([\App\Controller\User\EditX::class, 'doDefault'])
//        ->defaults(['id' => 0]);
//    $routes->add('api-form-user', '/form/user/{id}')
//        ->controller([\App\Form\User::class, 'doDefault'])
//        ->defaults(['id' => 0])
//        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_POST]);


    // Htmx Examples (TODO: remove for productions sites)
    $routes->add('ui-form', '/ui/form')
        ->controller([\App\Controller\Examples\FormEg::class, 'doDefault']);
    $routes->add('phpinfo', '/info')
        ->controller([\App\Controller\Examples\Info::class, 'doDefault']);
    $routes->add('test-dom', '/domTest')
        ->controller([\App\Controller\Examples\DomTest::class, 'doDefault']);
    $routes->add('test-htmx', '/htmx')
        ->controller([\App\Controller\Examples\Htmx::class, 'doDefault']);

    $routes->add('api-htmx-test', '/api/htmx/test')
        ->controller([\App\Api\HtmxExamples::class, 'doTest'])
    ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_POST]);
    $routes->add('api-htmx-users', '/api/htmx/users')
        ->controller([\App\Api\HtmxExamples::class, 'doFindUsers'])
        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_GET]);
    $routes->add('api-htmx-button', '/api/htmx/button')
        ->controller([\App\Api\HtmxExamples::class, 'doButton'])
        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_GET]);
    $routes->add('api-htmx-tabs', '/api/htmx/tabs')
        ->controller([\App\Api\HtmxExamples::class, 'doGetTabs'])
        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_GET]);

    $routes->add('api-htmx-upload', '/api/htmx/upload')
        ->controller([\App\Api\HtmxExamples::class, 'doUpload'])
        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_POST]);

};