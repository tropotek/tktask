<?php
/*
 * @author Tropotek <http://www.tropotek.com/>
 */

try {
    require_once __DIR__ . '/_prepend.php';
    defined('TKAPP') || die();

    $factory  = \App\Factory::instance();
    $response = $factory->getFrontController()->handle($factory->getRequest());
    $response->send();
    $factory->getFrontController()->terminate($factory->getRequest(), $response);
} catch (\Exception $e) {
    error_log($e->__toString());
}
