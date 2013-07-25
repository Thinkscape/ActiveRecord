<?php
namespace Thinkscape\ActiveRecord\Persistence;

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

        if (!$this->id) {
            // perform an INSERT operation

            // Get the data
            $updateData = $this->collectUpdateData();

            // Remember ID
            $this->id = (int) (microtime(true) * 1000);

            // Store data
            $storage[$this->id] = $updateData;

            // Update object state
            $this->_dirtyData = [];
            $this->isLoaded = true;
        } else {
            // perform an UPDATE operation
            if (!$this->isLoaded) {
                throw new Exception\RuntimeException('Attempt to save() a record that has not been loaded');
            }

            // Get update data
            $updateData = $this->collectUpdateData();

            // Store in memory
            foreach ($updateData as $key => $val) {
                $storage[$this->id][$key] = $val;
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

        // Try to find in memory storage
        if (!isset($storage[$this->id])) {
            throw new Exception\RecordNotFoundException(get_called_class(), $this->id);
        }

        // Load data
        $this->_data = $storage[$this->id];
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

        // Try to find in memory storage
        if (!isset($storage[$this->id])) {
            throw new Exception\RecordNotFoundException(get_called_class(), $this->id);
        }

        // Remove from storage
        unset($storage[$this->id]);

        // Reset instance state
        $this->isLoaded = false;
    }

    public function getFieldsFromDatabase()
    {
        throw new Exception\ConfigException('Unable to retrieve fields from Memory persistence storage.');
    }

    public static function initARMemory()
    {
        static::$_AREM[get_called_class()]['Core.findById'][] = 'static::findByIdInDb';
    }

    protected static function findByIdInDb($id)
    {
        // Get trait level storage
        $storage = & Memory::_getInternalStorage();

        // Try to find in memory storage
        if (!isset($storage[$id])) {
            return null;
        }

        // Create new instance
        $instance = new static(['id' => $id]);

        return $instance;
    }

    public static function & _getInternalStorage()
    {
        return self::$_persistenceMemoryStorage;
    }
}
