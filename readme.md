# tk8base

__Project:__ tk8base    
__Web:__ <http://www.tropotek.com/>  
__Authors:__ Tropotek <http://www.tropotek.com/>

A base site using the Tk framework, use this as a starting point for your own site.

## Contents

- [Installation](#installation)
- [Introduction](#introduction)

## Installation

1. First setup a database for the site and keep the login details handy.
2. Make sure you have the latest version of composer [https://getcomposer.org/download/] installed.
3. Use the following commands:
~~~bash
# git clone https://github.com/tropotek/tk8base.git
# cd tk8base
# composer install
~~~
4. Edit the `/src/App/config/config.php` file to your required settings.
5. You may have to change the permissions of the `/data/` folder so apache can read and write to it.
6. To enable debug mode and logging edit the `/src/config/config.php` file to suit your server.
   tail the log for more info on any errors.


## Upgrading

~~~bash
# git reset --hard
# git checkout master
# git pull
# composer update
~~~

__Warning:__ This could potentially break the site. Be sure to back up any DB and
site files before running these commands


## Introduction

### Editing The HTML

In the folder `/html` you will find all the content templates for the site.



