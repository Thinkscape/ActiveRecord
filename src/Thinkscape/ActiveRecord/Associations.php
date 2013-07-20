<?php
namespace Thinkscape\ActiveRecord;

trait Associations
{

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

        // use a getter
        if (method_exists($this,'get'.$prop)) {
            return call_user_func(array($this, 'get' . $prop));
        }

        // return parent object association
        if (isset(static::$_belongsTo[$prop])) {
            return $this->_findParent($prop);
        }

        // return associated object
        elseif (isset(static::$_hasOne[$prop])) {
            return $this->_findAssociate($prop);
        }

        // return children
        elseif (isset(static::$_hasMany[$prop])) {
            return $this->_findChildren($prop);
        }

        // return a property
        if ($this->_id) {
            // we have object id, load data from db
            if(!$this->_loaded)
                $this->_loadFromDb();

            if (array_key_exists($prop,$this->_data)) {
                return $this->_data[$prop];
            }
        } else {
            // return in-memory property value
            if (array_key_exists($prop,$this->_data)) {
                return $this->_data[$prop];
            }
        }

        throw new Exception\UndefinedPropertyException(get_class($this),$prop);
    }

    public function __set($prop,$val)
    {
        // return object id (performance)
        if ($prop == 'id' || $prop == static::$_pk) {
            return $this->_id = $val;
        }

        // call custom method
        elseif (method_exists($this,'set'.$prop)) {
            return call_user_func(array($this, 'set' . $prop));
        }

        if ($this->_id) {
            if (!$this->_loaded) {
                // we have object id, load data from db
                $this->_loadFromDb();
            }

            if (array_key_exists($prop,$this->_data)) {
                return $this->_data[$prop] = $val;
            } else {
                throw new Exception\UndefinedPropertyException(get_class($this),$prop);
            }

        } else {
            // this is a completely new object. Make sure we know column names
            if (static::$_columns) {
                // check if column (property) exists before setting it
                if (array_key_exists($prop,static::$_columns)) {
                    return $this->_data[$prop] = $val;
                } else {
                    throw new Exception\UndefinedPropertyException(get_class($this),$prop);
                }
            } else {
                // we should determine columns
                static::_loadColumns();
                if (array_key_exists($prop,self::$_columnsCache[get_class($this)])) {
                    return $this->_data[$prop] = $val;
                } else {
                    throw new Exception\UndefinedPropertyException(get_class($this),$prop);
                }
            }
        }
    }

    public function __call($func,$args)
    {
        if (substr($func,0,3) == 'get') {
            return $this->__get(
                strtolower(substr($func,3,1)).
                    substr($func,4)
            );
        } else {
            throw Exception\BadMethodCallException('There is no method '.$func.' in class "'.get_called_class().'"');
        }
    }

    public static function __callStatic($func,$args)
    {
        // ::findByName('name')
        if (substr($func,0,6) == 'findBy') {
            $what = substr($func,6);

            return static::findAll(array($what => $args));

            // ::findOneByName('name')
        } elseif (substr($func,0,9) == 'findOneBy') {
            $what = substr($func,9);

            return static::findOne(array($what => $args));

            // unknown function
        } else {
            throw Exception\BadMethodCallException('There is static method '.$func.' in class "'.get_called_class().'"');
        }
    }

}
