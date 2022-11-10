<?php
/**
 * Remember to refresh the cache after editing
 *
 * Reload the page with <Ctrl>+<Shift>+R
 */
use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;

// @see https://symfony.com/doc/current/routing.html
return function (CollectionConfigurator $routes) {


    $routes->add('index', '/')
        ->controller([\App\Controller\Home::class, 'doDefault']);
    $routes->add('home', '/home')
        ->controller([\App\Controller\Home::class, 'doDefault']);

    $routes->add('login', '/login')
        ->controller([\App\Controller\Login::class, 'doDefault']);
    $routes->add('logout', '/logout')
        ->controller([\App\Controller\Login::class, 'doLogout']);

    $routes->add('user-dashboard', '/dashboard')
        ->controller([\App\Controller\Dashboard::class, 'doDefault']);


    // API Endpoints
    $routes->add('api-htmx-alert', '/api/htmx/alert')
        ->controller([\App\Api\App::class, 'doAlert'])
        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_GET]);

    $routes->add('api-htmx-toast', '/api/htmx/toast')
        ->controller([\App\Api\App::class, 'doToast'])
        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_GET]);





    // Test routes

    $routes->add('install', '/install')
        ->controller([\App\Controller\Install::class, 'doDefault']);

    $routes->add('ui-form', '/ui/form')
        ->controller([\App\Controller\Ui\FormEg::class, 'doDefault']);

    $routes->add('user-manager', '/userManager')
        ->controller([\App\Controller\User\Manager::class, 'doDefault']);
    $routes->add('user-edit', '/userEdit/{id}')
        ->controller([\App\Controller\User\Edit::class, 'doDefault'])
        ->defaults(['id' => 0]);
    $routes->add('user-edit-new', '/nUserEdit/{id}')
        ->controller([\App\Controller\User\EditNew::class, 'doDefault'])
        ->defaults(['id' => 0]);

    $routes->add('user-form', '/form/user/{id}')
        ->controller([\App\Form\User::class, 'doDefault'])
        ->defaults(['id' => 0])
        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_POST]);


    $routes->add('phpinfo', '/info')
        ->controller([\App\Controller\Info::class, 'doDefault']);
    $routes->add('test-dom', '/domTest')
        ->controller([\App\Controller\DomTest::class, 'doDefault']);
    $routes->add('test-htmx', '/htmx')
        ->controller([\App\Controller\Htmx::class, 'doDefault']);



    $routes->add('api-htmx-test', '/api/htmx/test')
        ->controller([\App\Api\Htmx::class, 'doTest'])
    ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_POST]);
    $routes->add('api-htmx-users', '/api/htmx/users')
        ->controller([\App\Api\Htmx::class, 'doFindUsers'])
        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_GET]);
    $routes->add('api-htmx-button', '/api/htmx/button')
        ->controller([\App\Api\Htmx::class, 'doButton'])
        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_GET]);
    $routes->add('api-htmx-tabs', '/api/htmx/tabs')
        ->controller([\App\Api\Htmx::class, 'doGetTabs'])
        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_GET]);

    $routes->add('api-htmx-upload', '/api/htmx/upload')
        ->controller([\App\Api\Htmx::class, 'doUpload'])
        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_POST]);

};