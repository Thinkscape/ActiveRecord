 ActiveRecord quick start
=========================

Before you jump into quick start, make sure you are using PHP 5.4, you have installed the component into
your application and you have included [Composer autoloader](../README.md#installation-using-composer) or
the included [autoload_register.php](../README.md#manual-installation).

## Writing model classes

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
 2. `ActiveRecord\Persistence`, one of the following:
    * [ZendDb](../src/Thinkscape/ActiveRecord/Persistence/ZendDb.php) or
    * [DoctrineDBAL](../src/Thinkscape/ActiveRecord/Persistence/DoctrineDBAL.php) or
    * [Mongo](../src/Thinkscape/ActiveRecord/Persistence/Mongo.php)

Let's update the class to make it an ActiveRecord using Zend\Db:

````php

class Country
{
    use Thinkscape\ActiveRecord\Core;
    use Thinkscape\ActiveRecord\Persistence\ZendDb;

    // ...
}
````


## Connecting to the database

All [persistence methods](persistence.md) (such as ZendDb, DoctrineDBAL, Mongo) require a working database connection.
We have to create a new connection adapter and configure it with ActiveRecord.

There are 3 ways to configure database with ActiveRecord:

 1. We can set default db adapter for every ActiveRecord.
 2. We can set default db adapter for every instance of our class (i.e. class `Country`).
 3. We can set db adapter for a particular single instance.

Here is an example showing all 3 methods with Zend\Db persistence method:

````php
use Zend\Db\Adapter\Adapter;

// Create MySQL adapter using Zend\Db
$adapter = Adapter(array(
   'driver'   => 'Mysqli',
   'database' => 'my_application',
   'username' => 'developer',
   'password' => 'developer-password'
));

// Method 1. Set default adapter for all ActiveRecord instances
Thinkscape\ActiveRecord\Persistence\ZendDb::setDefaultDb($adapter);

// Method 2. Set default adapter for our Country class
Country::setDefaultDb($adapter);

// Method 3. Create an instance and assign an adapter to it
$finland = new Country();
$finland->setDb($adapter);
````

> More info on [persistence methods](persistence.md)