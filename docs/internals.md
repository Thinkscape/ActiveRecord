Internal Structure
=================================

## Internal Event Manager

ActiveRecord add-on features use an internal, lightweight event system for performing various tasks. Event listeners
are registered inside `static::$_AREM` in the following structure:

````php
static::$_AREM = [
    'Full\Class\Name' => [
        'Event.name1' => [
            $listenerCallable1,
            $listenerCallable2,
            // ...
        ],
        'Event.name2' => [
            $listenerCallable3,
            $listenerCallable4,
            // ...
        ],
        // ...
    ],
    'Another\Class\Name' => [
        // ...
    ],
];

````

Features are able to register their listeners in the Event Manager using feature initializers, for example:

````php
trait CustomFeature
{
    public static function initARCustomFeature()
    {
        static::$_AREM[get_called_class()]['Core.beforeSet'][] = 'static::beforeSetListener';
    }

    public static function beforeSetListener(&$instance, &$value, $propertyName)
    {
        // do something
    }
}
````

Events are triggered internally by the following functions defined by `ActiveRecord\Core`:

> `static::_arTriggerAll( string $eventName, array $arguments )`

Trigger all listeners for **$eventName** supplying them with **$arguments**. Return values are ignored and
every listener will be invoked.

> `(mixed) static::_arTriggerUntilValue( string $eventName, array $arguments )`

Trigger listeners until one of them returns a not-null value. The value is immediately returned and no further
listeners are invoked.


### Event names

 * `Core.beforeSet` - called just before storing property value in internal storage
     * `&$instance`     - a reference to current object
     * `&$value`        - the value that is about to be stored
     * `&$propertyName` - name of the property being modified
 * `Core.beforeDelete` - called just before an instance is deleted
     * `&$instance`  - a reference to the instance being deleted
 * `Core.delete` - called after instance has been deleted from the database
     * `&$instance`  - a reference to the instance being deleted


## Registry

ActiveRecord `factory()` and [find* methods](queries.md) guarantee PHP instance persistence - this means that fetching
the same object (same class and ID) multiple times will always return the same object instance for the lifetime of the
application. This is archived using an internal instance Registry inside `ActiveRecord\Core`.

The following public registry methods are available, however they should only be used by Features:

````php
    /**
     * Retrieve instance from internal registry, or null if does not exist.
     *
     * @param  string  $class
     * @param  integer $id
     * @return static
     */
    public static function getInstanceFromRegistry($class, $id);

    /**
     * Store a reference to a record in the internal registry.
     * This is used to provide same object instances for records with the same ID.
     *
     * @param  string  $class
     * @param  integer $id
     * @param  object  $instance
     * @return void
     */
    public static function storeInstanceInRegistry($class, $id, $instance);

    /**
     * Clear the internal instance registry
     *
     * @return void
     */
    public static function clearInstanceRegistry();
````


## Lifecycle management

## Serialization

## Subclassing