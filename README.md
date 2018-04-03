# DB

## Description
Light Wrapper around Docrines DBAL to handle management of configuration settings for various database types

## Installing
### Install application via Composer

require "cymapgt/db": "1.*"

## Usage
The DB package is built for building database connection settings for various database types
supported by Doctrine DBAL

### Overview
* DB is a singleton, which is common pattern used for database connection classes
* DB is designed to pick DB connection settings from environment variables, but can also
take a parameter of database connection settings
* All the Public Methods of the DB package are static
* NB: Storing database parameters in environment variable needs careful considerations
Security considerations are needed to ensure that they are only accessible to the web server
user. If this method is not viable in your scenario, you may use the alternative method that
accepts the DB connect settings as an array.

### Getters and Setters

####Setting The Database Type

    DB::setDbType('mysql');

Replace mysql with either of the following: oracle, mssql, sqlite, sybase, drizzle, postgres

#### Getting The Database Type of a Connection

    DB::getDbType();

#### Get Database Parameters

    DB::getDatabaseParameters();

Return an array of the database parameters stored in the web server / operating system
environment. The array is an associative array whose indices vary depending on the database
type. Database configuration array keys match the Doctrine DBAL database connection settings
listed on the documentation for DBAL (http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html).

An example for MySQL is the array would contain user, password, host, port, dbname, driver, unix_socket and charset indices.

### Quality Check on the Configuration Settings

#### Validating Configuration Settings

    DB::validateDbParameters($dbParams);

Accepts an array of the DB parameters. It then does two checks. It ensures that the driver which DBAL will use
for the database is actually loaded. It then checks that any of the default settings any of the various databases
DBAL can load are present in the array. If any of the two checks fails a cymapgt\Exception\DBException will be thrown.

    DB::sanitizeDbParameters($dbParams);

Accepts an array of the DB parameters. Some of the DB connection settings are optional e.g. the charset in mysql.
This method discards empty configurations prior to the DB connection being instantiated.

### Making DB connection and returning a DBAL instance

#### Connecting from Environment Settings

    //dbObj is an instance of DBAL loading your favourite DB . You can make your queries
    $dbObj = DB::connectDb();

#### Connecting with an Array of config settings

    DB::setDbType('mysql');

    //define settings in array
    $mysqlSettings = array (
     'user' => 'cr7',
     'password' => 'valdebas',
     'host' => 'localhost',
     'port' => '3306',
     'dbname' => 'undecima'
    );

    //return dbal instance with the mysql dbl loaded
    $dbObj = DB::connectDbNew($mysqlSettings);

#### Closing Database Connections

    //Part of resource management and ensuring sensitive information is discarded is destroying resources after use
    DB::closeDbConnection();


### Testing
DB Tests are provided with the package. SQLITE db is used in the testsuite.


### Contribute
* Email @rhossis or contact via Skype
* Fork the repository on GitHub to start making your changes to the master branch (or branch off of it).
* You will be added as author for contributions ... duh :)

### License
BSD 3 CLAUSE
