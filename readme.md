# tk8base

__Project:__ tk8base    
__Web:__ <https://github.com/tropotek/tk8base>  
__Authors:__ Tropotek <http://www.tropotek.com/>

A base site using the Tk framework, use this as a starting point for your own site.

## Contents

- [Installation](#installation)
- [Introduction](#introduction)

## Installation

1. First set up a database for the site and keep the login details handy.
2. Make sure you have the latest version of composer [https://getcomposer.org/download/] installed.
3. Use the following commands:
    ```bash
    $ git clone https://github.com/tropotek/tk8base.git
    $ cd tk8base
    $ composer install
    ````
4. You will be asked a number of questions to set up the environment settings.
5. Edit the `/src/App/config/config.php` file to your required settings.
6. You may have to change the permissions of the `/data/` folder so PHP can read and write to it.
7. To enable debug mode and logging edit the `/src/config/config.php` file to suit your server.
8. Browse to the location that was shown at the end of install to see if it all worked.
9. To log in with the default admin account, you will need to create a password.
   To create the admin account password execute the `password [pwd]` command using the site's CLI tool:
    ```bash
    $ ./bin/cmd pwd admin
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
$ git checkout master
$ git pull
$ composer update
```

__Warning:__ Upgrading could potentially break the site change the database. Back up any DB and
site files before running these commands.


## Introduction

### Main TK8 base objects

The tk8 lib has 4 main objects that can be used globally to maintain the
system state and create instances of system objects.

Modify the `/_prepend.php` file to override these objects as required.


__Factory__

The Factory object is where you will get system objects.
It helps manage our singleton objects and is used in favor of Dependency Injection (DI) pattern for simplicity.
The DI pattern can get very complicated, very quickly, and is not of any real advantage on our sites.
See the available functions in `\Tk\Factory`, `\Bs\Factory` and `\App\Factory` and override them
in the `\App\Factory` object to suite your site's environment.


Use the Factory to get instances of the following common MVC objects:
- Tk\Config
- Tk\Registry
- Tk\System
- Symfony\Component\HttpFoundation\Request
- Symfony\Component\HttpFoundation\Session\Session
- Tk\Db\Pdo
- Tk\Cache\Cache
- Symfony\Component\EventDispatcher\EventDispatcher
- Tk\Mail\Gateway
- Monolog\Logger
- Tk\Mvc\FrontController
- Tk\Console\Command


__Config__

The config object contains all the site configuration parameters. It contains the data from
the file `/src/config/config.php`. tk libs also have config files that are parsed before the
main site config file at `/src/config/config.php`. This file contains all the DB config params and
any other params you do not want in system code, hashes, passwords and API keys. It is not overwitten
when you run any updates. The file `/src/config/config.php.in` should be used when installing a new site.

You can override this in your own sites to add new options and override existing methods.

The config object will contain site settings like:
- basePath and baseUrl
- script start time
- dataPath and dataUrl
- debug mode
- etc...


__System__

The System object will contain all system information methods
and any methods to set the system state, setTimeZone(), SetLogPath(), etc.
You can also use this object to create paths and urls it uses the base path/url to create a full path.
It also has gives ou access to the version and composer info information.


__Registry__

The Registry holds persistent system configuration data.
After changing any Registry values remember to call save() to store the updated registry.
NOTE: Objects should not be saved in the Registry storage, only primitive types.

You can add to this as your site develops. A good idea is to create an admin site settings edit page
that can modify required data in this object.

By default, the registry stores the state of the site params like:
- Site Name
- Site Short Name
- Site Email (Used as the `from` in emails)
- Maintenance Mode state


__Task Scheduling__

There are 2 type of task scheduling that are available to the Tk lib by default:
- Set up a cron job in your host OS using the `./bin/cmd cron ...` command. You will
  have to update the `/App/Console/Cron` console command and add your scripts there.
  (You can also create a new command its just there for standardisation).
- Another place to look for automated tasks is the `/config/sql/events.sql` file
  where DB events are stored, add you own events here as needed.



