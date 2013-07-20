ActiveRecord Zend\Db Persistence
=================================

ActiveRecord can use `Zend\Db` DBAL to store data in various SQL databases. More information on how to use
Zend\Db can be found in the [official ZF2 documentation](http://framework.zend.com/manual/2.2/en/modules/zend.db.adapter.html).

There are 3 ways to configure Zend\Db with ActiveRecord:

 1. We can set default Zend\Db adapter for every ActiveRecord.
 2. We can set default Zend\Db adapter for every instance of our class (i.e. class `Country`).
 3. We can set Zend\Db adapter for a single instance.


### Default DB adapter for every ActiveRecord

In order to set a default, global db adapter use `Persistence\ZendDb::setDefaultDb()` method:

````php
use Zend\Db\Adapter\Adapter;
use Thinkscape\ActiveRecord;

// Create Zend\Db MySQLi adapter
$adapter = new Adapter(array(
   'driver'   => 'Mysqli',
   'database' => 'my_application',
   'username' => 'developer',
   'password' => 'developer-password'
));

// Set default adapter for all ActiveRecord instances
ActiveRecord\Persistence\ZendDb::setDefaultDb($adapter);
````

> [More info on creating Zend\Db adapters and supported databases]
  (http://framework.zend.com/manual/2.0/en/modules/zend.db.adapter.html)

### Class default DB adapter

To set a default adapter for a custom AR class (and its subclasses), use static `::setDefaultDb()` method on the class.

````php
use Zend\Db\Adapter\Adapter;
use Thinkscape\ActiveRecord;

class Entity extends ActiveRecord\AbstractActiveRecord { }

// Create Zend\Db adapter
$adapter1 = new Adapter(array( /* ... */ ))

// Set default adapter for instances of Entity and its subclasses
Entity::setDefaultDb($adapter);
````

### Instance DB adapter

In some cases it is useful to set DB adapter on individual instances, for example:

````php
use Zend\Db\Adapter\Adapter;
use Thinkscape\ActiveRecord;

class Entity extends ActiveRecord\AbstractActiveRecord { }

// Create Zend\Db adapters
$adapter1 = new Adapter(array( /* ... */ ))
$adapter2 = new Adapter(array( /* ... */ ))

// Set default adapter for instances of Entity and its subclasses
Entity::setDefaultDb($adapter1);

$entity1 = new Entity(); // this instance will use default $adapter1

// Create different MySQL adapter


// Create new instance and change its adapter
$entity2 = new Entity();
$entity2->setDb($adapter2); // this instance will now use $adapter 2
````


### Subclass default DB adapter

It is possible to define different adapter for subclasses of the main class, for example:

````php
use Zend\Db\Adapter\Adapter;
use Thinkscape\ActiveRecord;

class Entity extends ActiveRecord\AbstractActiveRecord { }
class Foo    extends Entity { }
class Bar    extends Entity { }

// Create MySQL adapter using Zend\Db
$adapter1 = Adapter(array( /* ... */ ))

// Set default adapter for instances of Entity and its subclasses
Entity::setDefaultDb($adapter1);

$entity = new Entity(); // this class will use $adapter1
$foo    = new Foo();    // this subclass will also use $adapter1

// Create different MySQL adapter
$adapter2 = Adapter(array( /* ... */ ))

// Set different adapter for SubEntityB
Bar::setDefaultDb($adapter2);

$foo = new Bar();    // this subclass will also use $adapter2
````

