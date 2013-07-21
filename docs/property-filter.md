ActiveRecord\PropertyFilter
=================================

**PropertyFilter** allows for filtering property values just before storing them and saving to database.

Note that this feature is not required for escaping data before storing it in the database (this is already taken
care of). You can use PropertyFilter to apply custom filtering logic to your model classes properties.

## Using with filter definitions

 1. Import `PropertyFilter` trait
 2. Configure `$_filters` static property
     * The property is an array of property names and respective filters.
     * Array key corresponds to property name.
     * Array value can be a filter or an array of filters.
     * A filter is a [PHP callable](http://www.php.net/manual/en/language.types.callable.php)
      (a string with function name or an array of class and method name).
     * Filter function is provided with value and is expected to return result of filtration.
 3. Each time filtered properties are updated they will be processed by filters.

````php
use Thinkscape\ActiveRecord;

class Country
{
    use ActiveRecord\Core;
    use ActiveRecord\PropertyFilter;

    protected static $_properties = [
        'name'       => true,
        'population' => true,
    ];

    protected static $_filters = [
        'name'       => 'static::filterCountryName',  // use filterCountryName() method
        'population' => [
            'intval',     // use intval() function
            'abs'         // and then abs() function
        ]
    ];

    protected static function filterCountryName($name) {
        $name = preg_replace('/[^a-zA-Z\ ]/', '', $name);
        $name = trim($name);
        return $name;
    }
}

$country = new Country();
$country->name = ' Le France 2';
$country->population = '65000000 people';

echo $country->name . ' has population of ' . $country->population;
// Le France has population of 65000000
````


## Using filteringProperty

It is also possible to override the `filterProperty()` method and write custom filtering logic in scope of
the filtered instance.

 1. Import `PropertyFilter` trait
 2. Define `filterProperty( $property, $value)` method
     * `$property` is the name of the property being modified.
     * `$value` is the new value of the property.
     * The method must return filtered value to store.
     * The method is called in dynamic context - you have access to all record data.
     * You can check current property value by using `$this->property` or `$_data['property']`.

````php
use Thinkscape\ActiveRecord;

class Country
{
    use ActiveRecord\Core;
    use ActiveRecord\PropertyFilter;

    protected static $_properties = [
        'name'       => true,
        'population' => true,
    ];

    protected function filterProperty($property, $value) {
        switch($property) {
            case 'name' :
                $value = preg_replace('/[^a-zA-Z\ ]/', '', $value);
                $value = trim($value);
                return $value;
            case 'population' :
                return abs( (int) $value);
            default:
                return $value;
        }
    }
}
````
