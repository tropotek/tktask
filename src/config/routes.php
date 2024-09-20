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

    // Base site routes
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


    // For development only Remove for your sites
    if (\Tk\Config::instance()->isDev()) {
        $routes->add('example-manager', '/exampleManager')
            ->controller([\App\Controller\Example\Manager::class, 'doDefault']);
        $routes->add('example-edit', '/exampleEdit')
            ->controller([\App\Controller\Example\Edit::class, 'doDefault']);

        // Htmx Examples
        $routes->add('ui-form', '/ui/form')
            ->controller([\App\Controller\Examples\FormEg::class, 'doDefault']);
        $routes->add('test', '/test')
            ->controller([\App\Controller\Examples\Test::class, 'doDefault']);
        $routes->add('test-dom', '/domTest')
            ->controller([\App\Controller\Examples\DomTest::class, 'doDefault']);
        $routes->add('test-htmx', '/htmx')
            ->controller([\App\Controller\Examples\Htmx::class, 'doDefault']);

        // API Endpoints
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
    }

};