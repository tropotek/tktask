# tktask

__Author:__ Tropotek <http://www.tropotek.com/>

## Contents

- [Installation](#installation)
- [Introduction](#introduction)

## Installation

1. First set up a database for the site and keep the login details handy.
2. Make sure you have the latest version of composer [https://getcomposer.org/download/] installed.
3. Use the following commands:
    ```bash
    $ git clone https://github.com/tropotek/tktask.git
    $ cd tktask
    $ composer install
    ````
4. You will be asked a number of questions to set up the environment settings.
5. Edit the `/config.php` file to your required settings if needed.
6. You may have to change the permissions of the `/data/` folder so your web server can read and write to it.
7. To enable debug mode and logging edit the `/config.php` file:
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
8. Browse to the location you installed the site.
9. (TODO: creating a new system user for first use?)


## Upgrading

Upgrade the site by the CLI command;
```bash
$ cd {siteroot}
$ ./bin/cmd ug
```

## Introduction




