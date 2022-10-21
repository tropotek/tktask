<?php
/**
 * Setup system configuration parameters
 *
 * @author Tropotek <http://www.tropotek.com/>
 */

return function (\Tk\Config $config)
{
    // Default System DB
    $config->set('db.default.type', 'mysql');
    $config->set('db.default.host', 'localhost');
    $config->set('db.default.port', '3306');
    $config->set('db.default.name', 'dev_tk8base');
    $config->set('db.default.user', 'dev');
    $config->set('db.default.pass', 'dev007');

    /*
     * DB secret API key
     * Use this  key for the mirror command in a dev environment.
     * Keep this key secret. Access to the sites DB can be gained with it.
     */
    $config->set('db.mirror.secret', 'acee2caddc146cfed0a0da12e133c726');

    /*
     * Enable DB sessions
     */
    $config->set('session.db_enable', true);

    /*
     * Set the site timezone for PHP and MySQL
     */
    $config->set('php.date.timezone', 'Australia/Brisbane');

    /*
     * Setup the dev environment
     */
    $config->set('debug', true);
    if ($config->isDebug()) {
        error_reporting(-1);
        $config->set('php.display_errors', 'On');
        $config->set('php.error_log', '/home/godar/log/error.log');
        $config->set('log.logLevel', \Psr\Log\LogLevel::DEBUG);
        // Mirror command Uri
        $config->set('db.mirror.url', 'https://godar.ttek.org/Projects/tk8base/util/mirror');
    }

};

