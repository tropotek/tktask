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

- Create a database for the site and keep the login details handy.
- Make sure you have the latest version of composer [https://getcomposer.org/download/] installed.
- Use the following commands:
    ```bash
    $ git clone https://github.com/tropotek/tktask.git
    $ cd tktask
    $ composer install
    ````
- You will be asked a number of questions to set up the environment settings and DB.
- Create a new admin user to log into the site:
```bash
$ ./bin/cmd adm {username}
```
- View/Edit the `/config.php` file after the command has finished and change to your required settings if needed.
- To enable debug mode and logging edit the `/config.php` file:
```php
    // Enable Debug in a dev environment
    $config['env.type'] = 'dev';
    // Setup dev environment
    if ($config->isDev()) {
        // Send all emails to the debug address
        $config['system.debug.email'] = 'dev@example.com';
        // allow any password without strict validation
        $config['auth.password.strict'] = false;
    }
```
- Browse to the site URL and login with the admin user credentials.

### Cron Script

- Install the cron script to ensure all features of the site work using `crontab -e`:
```cron
# Run the cron script every 2 hours
0 */2 * * *  /{pathToSite}/bin/cmd cron
```



## Upgrading

Upgrade the site by the CLI command;
```bash
$ cd {siteroot}
$ ./bin/cmd ug
```

Manual upgrade process if the above fails:
```bash
$ git reset --hard
$ git checkout 8.0.0    // Use the latest tag version here
$ composer update
```

__Warning:__ Upgrading could potentially break the site. Be sure to back up all DB's and
site `/data` files before running these commands.



