<?php
/**
 * Remember to refresh the cache after editing
 *
 * Reload the page with <Ctrl>+<Shift>+R
 */

use Symfony\Component\Routing\Loader\Configurator\CollectionConfigurator;
/*
 * @see https://symfony.com/doc/current/routing.html
 *
 * Selecting a template:
 *   You can select the page's template by adding `->defaults(['template' => '{public|admin|user|login|maintenance|error}'])`.
 *
 *   Other options may be available if you have created new template paths in the `20-config.php` file.
 *   Create a new path with `$config->set('path.template.custom', '/html/newTemplate/index.html');`
 *   then add `->defaults(['template' => 'custom'])` to the route. (case-sensitive)
 *
 */
return function (CollectionConfigurator $routes) {

    $routes->add('home-base', '/')
        ->controller([\App\Controller\Home::class, 'doDefault']);

    $routes->add('home', '/home')
        ->controller([\App\Controller\Home::class, 'doDefault']);

    $routes->add('contact', '/contact')
        ->controller([\App\Controller\Contact::class, 'doDefault']);

    $routes->add('user-dashboard', '/dashboard')
        ->controller([\App\Controller\Dashboard::class, 'doDefault']);

    $routes->add('settings-edit', '/settings')
        ->controller([\App\Controller\Admin\Settings::class, 'doDefault']);

    // API Endpoints




    // Example routes, remove for production

    // php page route example
    // $routes->add('widget-test', '/widgetTest')
    //     ->defaults(['path' => '/page/widgetManager.php'])
    //     ->controller([\Bs\Mvc\PhpController::class, 'doDefault']);

    $routes->add('example-manager', '/exampleManager')
        ->controller([\App\Controller\Example\Manager::class, 'doDefault']);

    $routes->add('example-edit', '/exampleEdit')
        ->controller([\App\Controller\Example\Edit::class, 'doDefault']);

    $routes->add('phpinfo', '/info')
        ->controller([\App\Controller\Admin\Info::class, 'doDefault']);

    // Htmx Examples
    $routes->add('ui-form', '/ui/form')
        ->controller([\App\Controller\Examples\FormEg::class, 'doDefault']);
    $routes->add('test', '/test')
        ->controller([\App\Controller\Examples\Test::class, 'doDefault']);
    $routes->add('test-dom', '/domTest')
        ->controller([\App\Controller\Examples\DomTest::class, 'doDefault']);
    $routes->add('test-htmx', '/htmx')
        ->controller([\App\Controller\Examples\Htmx::class, 'doDefault']);

    // Note no page template param for API calls
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

    $routes->add('api-htmx-toast', '/api/htmx/toast')
        ->controller([\App\Api\HtmxExamples::class, 'doToast'])
        ->methods([\Symfony\Component\HttpFoundation\Request::METHOD_GET]);

};