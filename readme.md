# TkTask

__Author:__ Tropotek <http://www.tropotek.com/>

## Contents

- [Installation](#installation)
- [Introduction](#introduction)

## About
TkTask is a billing and task management system for developers. 

Features:
- Manage clients and suppliers
- Manage large Project tasks
- Issue invoice emails
- Add recurring billing to client accounts (Hosting, domain name, etc)
- Track expenses
- Generate profit and loss report per financial year
- Monitor client web sites, with email notification when offline

## Installation

1. First set up a database for the site and keep the login details handy.
2. Make sure you have the latest version of composer [https://getcomposer.org/download/] installed.
3. Use the following commands:
    ```bash
    $ git clone https://github.com/tropotek/tktask.git
    $ cd tktask
    $ composer install
    ````
4. You will be asked a number of questions to set up the environment settings and DB.
5. View/Edit the `/config.php` file after the command has finished and change to your required settings if needed.
6. To enable debug mode and logging edit the `/config.php` file:
```php
    //  ...
    // Enable Debug in a dev environment
    $config['env.type'] = 'dev';
    $config['debug'] = true;

    // Setup dev environment
    if ($config->isDev()) {
        // Send all emails to the debug address
        $config['system.debug.email'] = 'dev@email.com';
        // allow any password without strict validation
        $config['auth.password.strict'] = false;
    }
```
7. Browse to the location you installed the site.
8. Set a password for the `admin` user with `./bin/cmd pwd admin`
9. Install the cron script to ensure all features of the site work:
```
# Run the cron script every 2 hours
0 */2 * * *  /{pathToSire}/bin/cmd cron
```
10. Now you should be able to use your new site.


## Upgrading

Upgrade the site to the latest release version using the CLI command;
```bash
$ cd {siteroot}
$ ./bin/cmd ug
```




