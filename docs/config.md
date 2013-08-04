Configuration and basic usage
==============================

ActiveRecord is used to add database functionality to your existing model classes.

## Minimal configuration

1. Add [ActiveRecord\Core](../src/Thinkscape/ActiveRecord/Core.php) trait to your class.
2. Add one of the following [ActiveRecord\Persistence](../src/Thinkscape/ActiveRecord/Persistence) traits:
   [ZendDb](../src/Thinkscape/ActiveRecord/Persistence/ZendDb.php),
   [DoctrineDBAL](../src/Thinkscape/ActiveRecord/Persistence/DoctrineDBAL.php),
   [Mongo](../src/Thinkscape/ActiveRecord/Persistence/Mongo.php),
   [Memory](../src/Thinkscape/ActiveRecord/Persistence/Memory.php).
3. Define `$_properties` that will be be accessed in the database.
4. Set `$_dbTable` for storing your data.


## Using getters and setters

ActiveRecord can work together with custom set/get methods. For example:

````php
use Thinkscape\ActiveRecord;

class Person
{
    use ActiveRecord\Core;
    use ActiveRecord\Persistence\ZendDb;

    protected static $_dbTable    = 'people';
    protected static $_properties = ['name', 'age'];

    protected $name;
    protected $age;

    public function getName() {
        return $this->name;
    }

    public function getAge() {
        return $this->age;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setAge($age) {
        $age = (int)$age;

        if($age < 0 || $age > 130) {
            throw new \InvalidArgumentException('Invalid age provided');
        }

        $this->age = $age;
    }
}
````

Properties can still be accessed directly but ActiveRecord will use accessors instead of internally storing the value.

````php
$joey = new Person();
$joey->name = "Joey";  // same as $joey->setName('Joey');
$joey->age = 35;       // same as $joey->setAge(35);
$joey->save();

$bob = Person::findOne([ 'name' => 'Bob' ]);
$bobsAge = $bob->age;  // same as $bob->getAge();
echo "Bob is $bobsAge years old";
````

**Note:** when saving to database, data will be collected from your getters. If you'd like to overwrite this behavior,
you can overwrite protected function [collectUpdateData()](../src/Thinkscape/ActiveRecord/Core.php#L300)