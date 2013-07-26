<?php
namespace Thinkscape\ActiveRecord\Persistence;

use Thinkscape\ActiveRecord\Core;
use Thinkscape\ActiveRecord\Exception;

/**
 * Simple, volatile memory storage, useful for testing.
 */
trait Memory
{
    protected static $_persistenceMemoryStorage = [];

    public function save()
    {
        // Get trait level storage
        $storage = & Memory::_getInternalStorage();
        $class = get_called_class();

        // Make sure there's class-specific container
        if (!isset($storage[$class])) {
            $storage[$class] = [];
        }

        if (!$this->id) {
            // perform an INSERT operation

            // Get the data
            $updateData = $this->collectUpdateData();

            // Remember ID
            $this->id = (int) (microtime(true) * 1000);

            // Store data
            $storage[$class][$this->id] = $updateData;

            // Update object state
            $this->_dirtyData = [];
            $this->isLoaded = true;

            // Store in registry
            Core::storeInstanceInRegistry(get_called_class(), $this->id, $this);

        } else {
            // perform an UPDATE operation
            if (!$this->isLoaded) {
                throw new Exception\RuntimeException('Attempt to save() a record that has not been loaded');
            }

            // Get update data
            $updateData = $this->collectUpdateData();

            // Store in memory
            foreach ($updateData as $key => $val) {
                $storage[$class][$this->id][$key] = $val;
            }

            // Update object state
            $this->_dirtyData = [];
            $this->isLoaded = true;
        }
    }

    public function load()
    {
        // Check if already loaded
        if ($this->isLoaded) {
            return true;
        }

        // Check for ID
        if (!$this->id) {
            throw new Exception\RuntimeException('Attempt to load() a record without an ID');
        }

        // Get trait level storage
        $storage = & Memory::_getInternalStorage();
        $class = get_called_class();

        // Try to find in memory storage
        if (!isset($storage[$class]) || !isset($storage[$class][$this->id])) {
            throw new Exception\RecordNotFoundException(get_called_class(), $this->id);
        }

        // Load data
        $this->_data = $storage[$class][$this->id];
        $this->_dirtyData = [];
        $this->isLoaded = true;
    }

    public function reload()
    {
        // Check if not loaded
        if (!$this->isLoaded) {
            return $this->load();
        }

        // Check for ID
        if (!$this->id) {
            throw new Exception\RuntimeException('Attempt to reload() a record without an ID');
        }

        // Reset instance status and load the record
        $this->_data = [];
        $this->_dirtyData = [];
        $this->isLoaded = false;

        return $this->load();
    }

    public function delete()
    {
        // Check for ID
        if (!$this->id) {
            throw new Exception\RuntimeException('Attempt to reload() a record without an ID');
        }

        // Get trait level storage
        $storage = & Memory::_getInternalStorage();
        $class = get_called_class();

        // Try to find in memory storage
        if (!isset($storage[$class]) || !isset($storage[$class][$this->id])) {
            throw new Exception\RecordNotFoundException(get_called_class(), $this->id);
        }

        // Remove from storage
        unset($storage[$class][$this->id]);

        // Reset instance state
        $this->isLoaded = false;
    }

    public static function initARMemory()
    {
        static::$_AREM[get_called_class()]['Core.findById'][] = 'static::findByIdInDb';
    }

    protected static function findByIdInDb($id)
    {
        $id = (int) $id;
        $class = get_called_class();

        // Get trait level storage
        $storage = & Memory::_getInternalStorage();

        // Try to find in memory storage
        if (!isset($storage[$class]) || !isset($storage[$class][$id])) {
            return null;
        }

        // Create new instance
        $instance = static::factory($id);

        return $instance;
    }

    public static function & _getInternalStorage()
    {
        return self::$_persistenceMemoryStorage;
    }
}
