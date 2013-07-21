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
     * A map of
     * @var bool
     */
    protected static $_isARinit = [];

    /**
     * ActiveRecord event manager static storage
     *
     * @var array
     */
    protected static $_AREM = [];

    /**
     * Creates new ActiveRecord instance.
     *
     * @param  array|Traversable                  $data
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($data = [])
    {
        // Init AR features for this class. This is currently the only way to implement static,
        // class-level initialization tasks for classes using traits, because traits cannot
        // be extended, cannot access each-other methods and are mostly invisible to class methods
        // on runtime.
        if (!isset(static::$_isARinit[get_called_class()])) {
            static::initActiveRecord();
        }

        // validate incoming data
        if (!is_array($data) && !$data instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Cannot construct new %s using %s - expected array or \Traversable',
                get_called_class(),
                gettype($data)
            ));
        }

        // assign values
        foreach ($data as $key => $val) {
            $this->__set($key, $val);
        }
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
     * Collect data for UPDATE or INSERT operation.
     *
     * @return array
     * @throws Exception\ConfigException
     */
    public function collectUpdateData()
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
     * Walk through all traits for current class and run init methods for features
     *
     * Warning: if this method is overridden in model class, you have to manually copy and paste the
     *          code from here, because it is currently impossible to change scope of static method calls;
     *          calling Core::initActiveRecord() will change "static::" scope to the trait itself.
     */
    protected static function initActiveRecord()
    {
        $className = get_called_class();
        $traits = class_uses($className,false);

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

        static::$_isARinit[get_called_class()] = true;
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
     * Store property value
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
