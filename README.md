Cohesion PHP Framework
======================

The primary objective for creating this framework is to provide a simple framework to help developers create good clean PHP code. It forces developers to write code that follows many common software development principles such as the ones defined in [SOLID](http://en.wikipedia.org/wiki/SOLID_(object-oriented_design)) and [GRASP](http://en.wikipedia.org/wiki/GRASP_(object-oriented_design)).

Cohesion is more than just a Framework, it's a development guideline that tries to enforce best practices to improve mantainability.


## Contents

* [Origins](#origins)
* [Code Structure](#code-structure)
    * [Controllers](#controllers)
    * [Business Logic](#services---business-logic)
    * [Object Data](#data-transfer-objects-dtos---object-data)
    * [Data Access Logic](#data-access-objects-daos---data-access-logic)
    * [Views](#views)
* [Utility classes](#utility-classes)
    * [Database Interaction](#database-interaction)
    * [Configuration Library](#configuration-library)
    * [Input Handling](#input-handler)
* [Features](#features)
* [Installing Cohesion](#installing-cohesion)
    * [Requirements](#requirements)
    * [Installing Composer](#installing-composer)
    * [Installing Nginx](#installing-nginx)
    * [Installing Cohesion with Composer](#installing-cohesion-with-composer)
    * [Setting up Nginx server](#setting-up-nginx-server)
* [Configuration](#configuration)
    * [File Structure](#file-structure)
    * [Environment specific configuration](#environment-specific-configuration)
    * [Configuration Options](#configuration-options)
* [Development](#development)


## Origins

Cohesion started from me deciding to start a new project in PHP. I didn't have much experience with many PHP frameworks so rather than looking through all the existing frameworks and picking the one that seemed to make the most sense to me, I decided to list all the 'features' I wanted in a framework before trying to find one suitable.

Coming from a long history of programming in a myriad of languages, I realised that one of my main requirements was to have a strong code structure to provide a strong backbone of the application so that it's very easy to maintain in the future. I have managed large teams of software engineers maintaining large codebases and I know how painful it can be to add new features or change existing ones when every engineer decides to implement his own structure.

In going through all the existing frameworks I found that none of them really gave me what I wanted. Probably the closest ones would be [Symfony](http://symfony.com/) and [Laravel](http://laravel.com/) but still they didn't really give me everything I needed. So I set out to develop a simple framework that will give me everything I need and will help make sure that I will be able to continue to easily add more features in the future even when my project has grown to hundreds of classes and will be able to easily go in and optimize the data access etc without having to worry about effecting any business logic etc.

One of the issues I have with many of the other frameworks is that they provide a good foundation for developing "anything" but then let the developers do whatever they want on top of it. They pride themselves as being extremely customizable and letting users choose how they want to use it. While that's fantastic for many people, I want something that will provide more than a foundation, I don't want other people to "use it how ever they want". In going with this theme I've tightened "how" Cohesion can be used down a bit and don't have as many "options".

I'm sure Cohesion won't be for everyone, as most people are happy with something they can quickly hack a website on top of and continue just quickly hacking stuff on top.

Cohesion is more for people who envision their product continuing to grow and place a high value on maintainability and low [technical debt](http://en.wikipedia.org/wiki/Technical_debt).


## Code Structure

Cohesion lays out the ground work for an extended MVC set up. I say extended because it goes one step further at separation of responsibilities by splitting the 'Model' into the **Business Logic**, **Object Data** and **Data Access Logic**. So I'm going to call it BODVC, sure, why not.


### Controllers

The Controllers are the external facing code that access the input variables and returns the output of the relevant view. The Controller handles the authentication, accesses the relevant Handler(s) then constructs the relevant view. It doesn't contain any business logic including authorisation.


### Services - Business Logic

Services contain all the business logic and only the business logic for a specific object. I've called the business logic section 'Services' because I want people to think of them as an independant Service for one portion of the application. The service will contain all the authorisations and validations before carrying out an operation. Services are the entry point for the DAOs, where only the UserService accesses the UserDAO. The UserService can be thought of as the internal API for everything to do with Users.


### Data Transfer Objects (DTOs) - Object Data

DTOs are simple instance objects that contain the data about a specific object and don't contain any business logic or data access logic. DTOs have accessors and mutators (getter and setters) and *can* contain minimal validation in the mutators.


### Data Access Objects (DAOs) - Data Access Logic

DAOs contain all the logic to store and retrieve objects and data from external data sources such as RDBMSs, non relational data stores, cache, etc. The DAO does not contain any business logic and will simply perform the operations given to it, therefore it's extremely important that all accesses to DAOs come from the correlating Service so that can perform the necessary checks before performing the storage or retrieval operations. The Service doesn't care how the DAO stores or retrieves the data only that it does. If later down the line a system is needed to be converted from using MySQL to MongoDB the only code that should ever need to be touched would be to within the DAOs.


### Views

Views take in the required parameters and return a rendering of what the user should see. Views don't perform any logic and don't do any calls to any function to get additional data. All data that the view will need should be provided to the view from the Controller.


## Utility classes

Cohesion comes with several extremely lightweight utility classes such as input handling, database interaction, configuration, and many more. More will be added over time and I'm always happy to receive pull requests additional utilities.


### Database Interaction

A MySQL database library is provided to provide safe interaction with MySQL databases. The database class includes support for master/slave queries, transactions, named variables, as well as many other features. For more information on the database library read the documentation at the start of the [MySQL.php](core/dataaccess/database/MySQL.php) file.


### Configuration Library

A config object class is provided for easy and extendible configuration. The config object will read one or more JSON formatted files and sections of the config can be given to classes to provide either business rule constants or library configurations. It is designed so that you can have a default config file with all the default configurations for your application then you can have a production config file that will overwrite only the variables within that config file such as database access etc.


### Input Handler

An extremely simple input handler is provided. The input handler doesn't do anything to prevent SQL injection or XSS as these are handled by the Database library and the Views respectively.


## Features

For more details about the features of Cohesion view the [Current Features](https://github.com/adric-s/cohesion-framework/wiki/Current-Features) section in the wiki.


## Installing Cohesion

This documentation currently focuses on installing Cohesion on a Debian/Ubuntu system. 

### Requirements

Once PHP is installed and set up adding additional libraries will be handled by Composer but there're still a few things you need to install yourself before then.

* PHP version 5.4 or above
* PHP-FPM
* MySQLnd extension
* APCu extension


#### Installing PHP and required extensions

```bash
$ sudo apt-get install php5 mysql-client php5-mysqlnd php5-fpm php5-curl php-pear php5-dev
```

Install APCu for fast access caching.
```bash
$ sudo pecl install apcu-beta
```
You'll most likely have to add the extension to your php.ini.
```bash
$ sudo echo "extension=apcu.so" > /etc/php5/mods-available/apcu.ini
$ sudo ln -s /etc/php5/mods-available/apcu.ini /etc/php5/conf.d/20-apcu.ini
$ sudo ln -s /etc/php5/mods-available/apcu.ini /etc/php5/fpm/conf.d/20-apcu.ini
```
The paths may be different depending on your distribution

#### Make PHP-FPM use a Unix socket

By default PHP-FPM is listening on port 9000 on 127.0.0.1. It is also possible to make PHP-FPM use a Unix socket which avoids the TCP overhead. To do this, open `/etc/php5/fpm/pool.d/www.conf`

```bash
$ sudo vi /etc/php5/fpm/pool.d/www.conf
```
find the existing `listen` line and comment it out and add the new one as shown here:

```conf
[...]
;listen = 127.0.0.1:9000
listen = /var/run/php5-fpm.sock
[...]
```

### Installing Composer

[Composer](https://getcomposer.org/) is the package manager used by modern PHP applications and is the only recommended way to install Cohesion. To install composer run these commands:

```bash
$ curl -sS https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer
```


### Installing Nginx

It's recommended to use [Nginx](http://en.wikipedia.org/wiki/Nginx) with Cohesion. Nginx is a very lightweight web server and is much more efficient than Apache and very easy to configure.

```bash
$ sudo apt-get install nginx
```


### Installing Cohesion with Composer

Once composer is installed run the following command to create a new project in a `myproject` directory using Cohesion:

```bash
$ composer create-project cohesion/cohesion-framework -sdev myproject
```

Composer will then download all required dependencies and create the project directory structure for you.

After composer finishes the installation process the installer will ask you `Do you want to remove the existing VCS (.git, .svn..) history? [Y,n]?` just hit `<Enter>` to safely remove the Cohesion git history. This will prevent you from polluting your projects version history with Cohesion commits. It will also make it easier to set up your own version control for your project.


### Setting up Nginx server

Default Nginx configurations are provided within the `/config/nginx/` directory of your project. So that we can keep our server configurations in version control we'll just link to the configuration file you want to use for the current environment within our project.

```bash
$ sudo ln -s /full/path/to/myproject/conf/nginx/local.conf /etc/nginx/sites-available/myproject-local
$ sudo ln -s /etc/nginx/sites-available/myproject-local /etc/nginx/sites-enabled/myproject
$ sudo rm /etc/nginx/sites-enabled/default
```
**Note:** Your nginx directory might be somewhere else depending on your distribution.

Open up the configuration and set the `root` path to the `www` directory within your project.
```bash
$ vi config/nginx/local.conf
```

You can create additional Nginx configuration files for your different environments just remember to change the `server_name`, `root` path and `APPLICATION_ENV`.


Restart nginx
```bash
sudo service nginx restart
```

Now you should be able to see a default Cohesion welcome page when you go to [http://localhost](http://localhost).

## Configuration

Configuration files are JSON formatted and can include comments. The configuration files are set up in a cascading fashion where loading subsequent configurations will overwrite just the values that are specified in that config and the unspecified configurations are left unchanged.

The default Cohesion configuration is located at `myproject/config/cohesion-default-conf.json`. It is not recommended to make any changes to this file directly as it may make it harder to resolve any conflicts etc if we update it. Instead you should put all your default configurations in the `myproject/config/default-conf.json` file. Remember you don't need to implement all the settings, only add the ones that you want to do differently from the cohesion defaults.


### File Structure

The configuration is very well structured and documented so make sure you take the time to read the comments in the cohesion default configuration file about what each setting does.

When constructing various objects and libraries they will be given a sub section of the configuration so it's important to get the structure right.

All objects and libraries will have access to the `global` section.

Each Service that you create will get a copy of the `application` section. The view and templating library will get the `view` section. The database class will use a copy of the `data_access.database`. And so on and so forth.


### Configuration Options

For a full list of the configuration options see the [Configuration wiki page](https://github.com/cohesion/cohesion-framework/wiki/Configuration#configuration-options)


### Environment Specific Configuration

For environment specific configurations such as different database settings, etc for your dev, staging and production environments. These are just examples you can have separate configuration for what ever different environments you might have. Simply create additional configuration files in the form `{environment}-conf.json`. For example, `local-conf.json` or `production-conf.json`.

In the Nginx config file we created earlier there is a commented out line for the `fastcgi_param`. Uncomment that line and change `local` to the environment config you want to use.

Again these are cascaded so you only need to include the settings that are different for that environment and it will get the rest of the settings from your `default-conf.json` and the `cohesion-default-conf.json`.


## Development

Alright! Now you're ready to start coding your application. 

We'll be adding some information on how to start developing your application as well as some example applications soon.



