<?php
namespace Thinkscape\ActiveRecord;

use Traversable;

/**
 * Core ActiveRecord functionality.
 *
 * ActiveRecord configuration consists of the following static properties:
 *
 * @staticvar array  $_properties                A map of available (and accessible) entity properties.
 * @staticvar bool   $_retrievePropertiesFromDb  Flag to determine if properties can be derived from DB column names.
 *                                               (defaults tro true)
 */
trait Core
{
    /**
     * Instance ID
     *
     * @var integer
     */
    protected $id;

    /**
     * ActiveRecord internal data storage
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Internal map of modified (dirty) properties
     *
     * @var array
     */
    protected $_dirtyData = [];

    /**
     * Is loaded from DB flag.
     *
     * @var bool
     */
    protected $isLoaded = false;

    /**
     * A map of all classes that have performed Active Record initialization.
     *
     * See notes under __construct().
     *
     * @var bool
     */
    protected static $_isARinit = [];

    /**
     * Static ActiveRecord instance registry, used to provide same object instances for records with the same ID.
     *
     * @var bool
     */
    protected static $_arRegistry = [];

    /**
     * ActiveRecord internal Event Manager's listener stack
     *
     * @var array
     */
    protected static $_AREM = [];

    /**
     * Creates new ActiveRecord instance.
     *
     * Warning! In case you are creating your own, custom constructor, make sure to include code from this
     * implementation. It contains initialization tasks, required for Active Record to work properly.
     *
     * @param  array|Traversable                  $data
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($data = [])
    {
        // Init AR features for this class. This is currently the only way to implement static,
        // class-level initialization tasks for classes using traits, because traits cannot
        // be extended, cannot access each-other methods and are mostly invisible to class methods
        // on runtime. It is not possible to implicitly perform initialization at class declaration
        // time, nor at the moment a class uses a particular trait. We are using isset() together
        // with get_called_class() which is the fastest way of determining if the initialization
        // should be performed or not.
        if (!isset(static::$_isARinit[get_called_class()])) {
            static::initActiveRecord();
        }

        // validate incoming data
        if ($data !== null && !is_array($data) && !$data instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Cannot construct new %s using %s - expected array or \Traversable',
                get_called_class(),
                gettype($data)
            ));
        }

        // assign values
        if ($data !== null) {
            foreach ($data as $key => $val) {
                $this->__set($key, $val);
            }
        }
    }

    /**
     * Retrieve instance for given unique id, or create a completely new instance.
     *
     * @param string|int|null $data ID of the object to retrieve, or an array of properties to create a completely
     *                               new record. Use null to create a new record with default values.
     * @return static
     * @throws Exception\InvalidArgumentException
     */
    public static function factory($data = null)
    {
        if ($data !== null && !is_numeric($data) && !is_array($data) && !$data instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Cannot get new instance of %s using %s - expected a number, array or \Traversable',
                get_called_class(),
                gettype($data)
            ));
        }

        // Retrieve the instance by ID
        $class = get_called_class();
        if (is_numeric($data)) {

            // Try to find the object reference in the registry
            $instance = Core::getInstanceFromRegistry($class, (int) $data);

            // If it wasn't found, create a new instance for this ID and add it to registry.
            if (!$instance) {
                $instance = new static([ 'id' => (int) $data ]);
                Core::storeInstanceInRegistry($class, (int) $data, $instance);
            }

            return $instance;
        }

        // Create completely new instance but don't store it in registry yet (as we don't know the ID)
        $instance = new static($data);

        return $instance;
    }

    /**
     * Retrieve instance from internal registry, or null if does not exist.
     *
     * @param  string  $class
     * @param  integer $id
     * @return static
     */
    public static function getInstanceFromRegistry($class, $id)
    {
        if(
            !isset(self::$_arRegistry[$class]) ||
            !isset(self::$_arRegistry[$class][$id])
        ) {
            return null;
        }

        return self::$_arRegistry[$class][$id];
    }

    /**
     * Store a reference to a record in the internal registry.
     * This is used to provide same object instances for records with the same ID.
     *
     * @param  string  $class
     * @param  integer $id
     * @param  object  $instance
     * @return void
     */
    public static function storeInstanceInRegistry($class, $id, $instance)
    {
        if (!isset(self::$_arRegistry[$class])) {
            self::$_arRegistry[$class] = [];
        }
        self::$_arRegistry[$class][$id] = $instance;
    }

    /**
     * Get current ID or null in case the record has not been yet stored in the DB.
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set instance ID.
     *
     * This method can be called only once, and is usually called when creating the instance.
     *
     * @param $id
     * @throws Exception\RuntimeException
     * @return void
     */
    public function setId($id)
    {
        if ($this->id !== null) {
            throw new Exception\RuntimeException('Unable to change id of an ActiveRecord instance');
        }
        $this->id = $id;
    }

    /**
     * Find a record with the given id
     *
     * @param $id
     * @return mixed|null
     */
    public static function findById($id)
    {
        // Call all registered findById listeners, providing them with the id we
        // are searching for. This architecture allows for multi-tier
        return static::_arTriggerUntilValue('Core.findById', array($id));
    }

    /**
     * Collect data for UPDATE or INSERT operation.
     *
     * @return array
     * @throws Exception\ConfigException
     */
    protected function collectUpdateData()
    {
        $updateData = [];

        // Make sure we know all property names
        if (!static::$_properties) {
            if (!isset(static::$_retrievePropertiesFromDb) || static::$_retrievePropertiesFromDb) {
                static::getPropertiesFromDatabase();
            } else {
                throw new Exception\ConfigException(sprintf(
                    'Missing property configuration for class %s',
                    get_called_class()
                ));
            }
        }
        // Run through all properties and retrieve data that has changed
        foreach (static::$_properties as $property => $attributes) {
            // Normalize properties configured without attributes
            if (is_integer($property)) {
                if (!is_string($attributes)) {
                    throw new Exception\ConfigException(sprintf(
                        'Cannot understand property %s definition of %s',
                        (int) $property,
                        get_called_class()
                    ));
                }

                $property = $attributes;
                $attributes = array();
            }

            if (isset($this->_dirtyData[$property])) {
                $updateData[$property] = $this->__get($property);
            }
        }

        return $updateData;
    }

    /**
     * Walk through all traits for current class and run init methods for all features.
     * This method is called only once for each class consuming ActiveRecord\Core.
     *
     * Warning: if this method is overridden in model class, you have to manually copy and paste the
     *          code from here, because it is currently impossible to change scope of static method calls;
     *          calling Core::initActiveRecord() will change "static::" scope to the trait itself.
     */
    protected static function initActiveRecord()
    {
        $className = get_called_class();
        $traits = class_uses($className,false);

        // Call initialization methods that are provided by individual traits
        foreach ($traits as $traitName) {
            $traitName = substr($traitName, strrpos($traitName, '\\') + 1);
            $initMethod = 'initAR' . $traitName;
            if (method_exists($className, $initMethod)) {
                /**
                 * We are using PHP 5.4+ variable static method invocation
                 * which is 2x to 6x faster than call_user_func()
                 * @link http://3v4l.org/SlOpr
                 */
                static::{$initMethod}();
            }
        }

        // We are using get_called_class() here to handle subclasses that might share the static
        // variable storage with parent class but additional ActiveRecord features added on.
        static::$_isARinit[$className] = true;
    }

    /**
     * Trigger all listeners attached to current class for $eventName.
     *
     * @param  string $eventName Name of the event to trigger
     * @param  array  $arguments
     * @return void
     */
    protected static function _arTriggerAll($eventName, array $arguments = [])
    {
        $className = get_called_class();
        if (empty(static::$_AREM[$className]) || empty(static::$_AREM[$className][$eventName]) ) {
            return;
        }

        foreach (static::$_AREM[$className][$eventName] as $callable) {
            call_user_func_array($callable, $arguments);
        }
    }

    /**
     * Trigger listeners until one returns a non-null value
     *
     * @param  string     $eventName Name of the event to trigger
     * @param  array      $arguments
     * @return mixed|null
     */
    protected static function _arTriggerUntilValue($eventName, array $arguments = [])
    {
        $className = get_called_class();
        if (empty(static::$_AREM[$className]) || empty(static::$_AREM[$className][$eventName]) ) {
            return null;
        }

        foreach (static::$_AREM[$className][$eventName] as $callable) {
            $value = call_user_func_array($callable, $arguments);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Retrieve property value
     *
     * @param $prop
     * @return mixed
     * @throws Exception\UndefinedPropertyException
     */
    public function __get($prop)
    {
        // return object id
        if ($prop == 'id') {
            return $this->id;
        }

        // if we are working with an identified entity, make sure it's loaded
        if ($this->id && !$this->isLoaded) {
            $this->load();
        }

        // use a getter
        if (method_exists($this, 'get' . $prop)) {
            return call_user_func(array($this, 'get' . $prop));
        }

        // return in-memory property value
        if (array_key_exists($prop, $this->_data)) {
            return $this->_data[$prop];
        }

        // return null for unset, known properties
        if (array_key_exists($prop, static::$_properties)) {
            return null;
        }

        throw new Exception\UndefinedPropertyException(get_class($this), $prop);
    }

    /**
     * Change property value.
     *
     * @param $prop
     * @param $val
     * @throws Exception\ConfigException
     * @throws Exception\UndefinedPropertyException
     * @return void
     */
    public function __set($prop, $val)
    {
        if (method_exists($this, 'set' . $prop)) {
            call_user_func(array($this, 'set' . $prop), $val);

            return;
        }

        if ($this->id) {
            // We are working with an identified entity - make sure it's loaded from the database
            if (!$this->isLoaded) {
                $this->load();
            }

            // Check if a key exists in the storage and update it
            if (array_key_exists($prop, $this->_data)) {

                // Run the value through features' filters
                static::_arTriggerAll('Core.beforeSet', array(&$this, &$val, $prop));

                // Store the value
                $this->_data[$prop] = $val;
                $this->_dirtyData[$prop] = true;
            } else {
                throw new Exception\UndefinedPropertyException(get_class($this), $prop);
            }

        } else {
            // We are working with a completely new object. Make sure we know all property names
            if (!static::$_properties) {
                if (!isset(static::$_retrievePropertiesFromDb) || static::$_retrievePropertiesFromDb) {
                    static::getPropertiesFromDatabase();
                } else {
                    throw new Exception\ConfigException(sprintf(
                        'Missing property configuration for class %s',
                        get_called_class()
                    ));
                }
            }

            // Check if column (property) exists before trying to set it
            if (array_key_exists($prop, static::$_properties)) {

                // Run the value through features' filters
                static::_arTriggerAll('Core.beforeSet', array(&$this, &$val, $prop));

                // Store the value
                $this->_data[$prop] = $val;
                $this->_dirtyData[$prop] = true;
            } else {
                throw new Exception\UndefinedPropertyException(get_class($this), $prop);
            }
        }
    }

    /*public static function __callStatic($func, $args)
    {
        // ::findByName('name')
        if (substr($func, 0, 6) == 'findBy') {
            $what = substr($func, 6);

            return static::findAll(array($what => $args));

            // ::findOneByName('name')
        } elseif (substr($func, 0, 9) == 'findOneBy') {
            $what = substr($func, 9);

            return static::findOne(array($what => $args));

            // unknown function
        } else {
            throw Exception\BadMethodCallException('There is static method ' . $func . ' in class "' . get_called_class() . '"');
        }
    }*/

}
