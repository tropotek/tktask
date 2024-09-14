<?php
/**
 * application configuration parameters
 */
use Tk\Config;

return function (Config $config) {

    /**
     * Set environment type to prevent destructive functions on production sites
     * options are 'dev' | 'prod'
     */
    $config['env.type'] = 'prod';

    /**
     * Enable to view more verbose log messages
     */
    $config['debug'] = false;

    /**
     * side-nav (default) use config to load top nav template
     */
    $config->set('path.template.admin', '/html/minton/sn-admin.html');
    $config->set('path.template.user', $config->get('path.template.admin'));

    /*
     * mail template path
     */
    $config['system.mail.template'] = '/html/templates/mail.default.tpl';

    /**
     * Enable DB sessions
     */
    $config['session.db_enable'] = true;

    /**
     * Set the site timezone for PHP and MySQL
     */
    $config['php.date.timezone'] = 'Australia/Melbourne';

    /**
     * The default log level
     */
    $config['log.logLevel'] = \Psr\Log\LogLevel::ERROR;



};