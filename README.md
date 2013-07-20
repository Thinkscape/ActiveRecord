ActiveRecord  [![Build Status](https://api.travis-ci.org/Thinkscape/ActiveRecord.png?branch=master)](http://travis-ci.org/Thinkscape/ActiveRecord) [![Coverage Status](https://coveralls.io/repos/Thinkscape/ActiveRecord/badge.png)](https://coveralls.io/r/Thinkscape/ActiveRecord)
=============

Modern ActiveRecord implementation for PHP 5.4+

[ActiveRecord is an architectural pattern](https://en.wikipedia.org/wiki/Active_record_pattern) for adding
database awareness and [CRUD](https://en.wikipedia.org/wiki/CRUD) functionality to domain objects. It is 
simple to use, easy to maintain and performant when used together with well-designed userland code.

The ActiveRecord pattern has been discussed, debated, critiqued and praised for decades. You can learn more
about when to use it, when not to use it and what are some of the caveats in the following document: 
[docs/discussion.md](docs/discussion.md).

## Installation
### Requirements

  * PHP 5.4.3 or newer
  * Database Abstraction Layer, one of the following:
    * [Zend Framework 2.2 Zend\Db](https://github.com/zendframework/zf2) or
    * [Doctrine DBAL 2.3+](https://github.com/doctrine/dbal)

### Installation using Composer

 1. Inside your app directory run `composer require thinkscape/activerecord:dev-master`
 2. Make sure you are using composer autoloader: `include "vendor/autoload.php";`
 3. Follow [quick start](#quick-start) instructions.

### Manual installation
 
 1. Obtain the source code with by either:
   * cloning git [project from github](https://github.com/Thinkscape/ActiveRecord.git), or
   * downloading and extracting [source package](https://github.com/Thinkscape/ActiveRecord/archive/master.zip).
 2. Set up class autoloading by either:
   * using the provided autoloader: `require "init_autoload.php";`, or
   * adding `src` directory as namespace `Thinkscape\ActiveRecord` to your existent autoloader.
 3. Follow [quick start](#quick-start) instructions.

Before you jump into quick start, make sure you are using PHP 5.4, you have installed the component into
your application and you have included [Composer autoloader](../README.md#installation-using-composer) or
the included [autoload_register.php](../README.md#manual-installation).

## Documentation

 * [Quick Start](#quick-start)
 * [Configuration](docs/config.md)
 * [CRUD - Create, Read, Update, Delete](docs/CRUD.md)
 * [Queries and traversal](docs/queries.md)
 * [Persistence methods and DB configuration](docs/persistence.md)
 * [Features and add-ons](docs/features.md)

## Quick Start

### 1) Make your classes ActiveRecords

ActiveRecord is used to add database functionality to your existing model classes. For example, let's consider
the following class:

````php
class Country
{
    protected $name;

    public function getName(){
        return $this->name;
    }

    public function setName($name){
        $this->name = $name;
    }
}
````

In order to make the class an ActiveRecord, at minimum, you have to add the following traits:

 1. [ActiveRecord\Core](../src/Thinkscape/ActiveRecord/Core.php)
 2. [ActiveRecord\Persistence](../src/Thinkscape/ActiveRecord/Persistence), one of the following:
    [ZendDb](../src/Thinkscape/ActiveRecord/Persistence/ZendDb.php),
    [DoctrineDBAL](../src/Thinkscape/ActiveRecord/Persistence/DoctrineDBAL.php),
    [Mongo](../src/Thinkscape/ActiveRecord/Persistence/Mongo.php),
    [Memory](../src/Thinkscape/ActiveRecord/Persistence/Memory.php).

Let's update the class to make it an ActiveRecord using Zend\Db:

````php
use Thinkscape\ActiveRecord;

class Country
{
    use ActiveRecord\Core;
    use ActiveRecord\Persistence\ZendDb;

    protected static $_properties = ['name'];

    // ...
}
````

> More info on [configuring ActiveRecords](docs/config.md)


### 2) Connect to the database

All [persistence methods](persistence.md) (such as ZendDb, DoctrineDBAL, ...) require a working database connection.
We have to create a new connection adapter and configure it with ActiveRecord:


````php
use Zend\Db\Adapter\Adapter;

// Create Zend\Db MySQLi adapter
$adapter = new Adapter(array(
   'driver'   => 'Mysqli',
   'database' => 'my_application',
   'username' => 'developer',
   'password' => 'developer-password'
));

// Method 1. Set default adapter for all ActiveRecord instances
Thinkscape\ActiveRecord\Persistence\ZendDb::setDefaultDb($adapter);

// Method 2. Set default adapter for Country class
Country::setDefaultDb($adapter);

// Method 3. Create an instance and assign an adapter to it
$finland = new Country();
$finland->setDb($adapter);
````

> [More info on persistence methods and configuring database](docs/persistence.md)

### 3) Insert, update and delete records

````php
// Create new record
$finland = new Country();
$finland->setName('Finland');
$finland->save();      // INSERT INTO country (name) VALUES ("Finland")

// Update
$finland->setName('Maamme');
$finland->save();      // UPDATE country SET name = "Maamme"

// Delete
$finland->delete();    // DELETE FROM country WHERE id = 1
````

> [More info on CRUD operations](docs/CRUD.md)

### 4) Retrieve records from database

````php
$first = Country::findFirst();
// SELECT * FROM country ORDER BY id ASC LIMIT 1

$countryById = Country::findById(220);
// SELECT * FROM country WHERE id = 220

$countryByName = Country::findOneBy('name', 'Finland');
// SELECT * FROM country WHERE name = "Finland" LIMIT 1

$countryByName = Country::findOne([
    'name' => 'Finland'
]);
// SELECT * FROM country WHERE name = "Finland" LIMIT 1

$allEuropeanCountries = Country::findAll([
    'continent' => 'Europe'
]);
// SELECT * FROM country WHERE continent = "Finland"


$allBigCountries = Country::findAll([
    ['population', 'gt', 30000000]
]);
// SELECT * FROM country WHERE population >= 30000000

````
> [More info on queries and finding records](docs/queries.md)

### 5) Add more features to your class

 * ActiveRecord\AttributeMethods
 * ActiveRecord\Aliasing
 * ActiveRecord\Aggregations
 * ActiveRecord\Associations
 * ActiveRecord\Conversion
 * ActiveRecord\CounterCache
 * ActiveRecord\Callbacks
 * ActiveRecord\Inheritance
 * ActiveRecord\Integration
 * ActiveRecord\Locking\Optimistic
 * ActiveRecord\Locking\Pessimistic
 * ActiveRecord\ModelSchema
 * ActiveRecord\NestedAttributes
 * ActiveRecord\Reflection
 * ActiveRecord\Readonly
 * ActiveRecord\ReadonlyAttributes
 * ActiveRecord\Scoping
 * ActiveRecord\Serialization
 * ActiveRecord\Sanitization
 * ActiveRecord\Timestamp
 * ActiveRecord\Transactions
 * ActiveRecord\Validations

