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
    * [Configuration](#configuration)
    * [Input Handling](#input-handler)
* [Features](#features)

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


### Configuration

A config object class is provided for easy and extendible configuration. The config object will read one or more JSON formatted files and sections of the config can be given to classes to provide either business rule constants or library configurations. It is designed so that you can have a default config file with all the default configurations for your application then you can have a production config file that will overwrite only the variables within that config file such as database access etc.


### Input Handler

An extremely simple input handler is provided. The input handler doesn't do anything to prevent SQL injection or XSS as these are handled by the Database library and the Views respectively.

## Features
For more details about the features of Cohesion view the [Current Features](https://github.com/adric-s/cohesion-framework/wiki/Current-Features) section in the wiki.
