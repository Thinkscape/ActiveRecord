<?php
namespace Thinkscape\ActiveRecord\Persistence;

use Thinkscape\ActiveRecord\Core;
use Thinkscape\ActiveRecord\Exception;
use Zend\Db\Adapter\Adapter;
use Thinkscape\ActiveRecord\Exception\ConfigException;
use Zend\Db\Sql\Sql;

/**
 * Zend\Db based persistence functionality.
 *
 * @staticvar string $_dbTable                   Name of db table (collection) for storing record data.
 */
trait ZendDb
{
    /**
     * Instance's db adapter.
     *
     * @var Adapter|null
     */
    protected $_db;

    /**
     * Class Sql instance.
     *
     * @var Adapter|null
     */
    protected static $_sql;

    /**
     * Global, default db adapter (used/stored at trait level)
     *
     * @var Adapter|null
     */
    protected static $_globalDefaultDb;

    /**
     * Local superclass db adapter (used at user superclass level)
     *
     * @var Adapter|null
     */
    protected static $_classDefaultDb;

    /**
     * Name of the superclass that stores the db adapter
     *
     * @var Adapter|null
     */
    protected static $_classDefaultDbOwnerClass;

    /**
     * Subclass-specific db adapter (used/stored at trait level)
     *
     * @var Adapter|null
     */
    protected static $_subclassDefaultDb;

    public static function initARZendDb()
    {
        static::$_AREM[get_called_class()]['Core.findById'][] = 'static::findByIdInDb';
    }

    /**
     * @param $id
     * @return null|static
     */
    protected static function findByIdInDb($id)
    {
        $id = (int) $id;
        $db = static::getDefaultDb();
        $table = static::getStaticDbTable();
        $sql = new Sql($db);

        // Try to find the row in db
        $select = $sql->select($table)->columns(['id'])->limit(1)->where([
            'id' => $id
        ]);
        $results = $db->query($select->getSqlString($db->getPlatform()))->execute();

        // Check if a record has been retrieved
        if (!$results->count()) {
            return null;
        }

        // Create new instance
        $instance = static::factory($id);

        return $instance;
    }

    /**
     * Store instance data in the database
     *
     * @throws \Thinkscape\ActiveRecord\Exception\DatabaseException
     * @throws \Thinkscape\ActiveRecord\Exception\RuntimeException
     * @return void
     */
    public function save()
    {
        $db = $this->getDb();
        $sql = new Sql($db);

        if (!$this->id) {
            // perform an INSERT operation

            // Get the data
            $updateData = $this->collectUpdateData();

            // Prepare and execute INSERT
            $insert = $sql->insert($this->getDbTable())->values($updateData);
            $db->query(
                $insert->getSqlString($db->getPlatform()),
                $db::QUERY_MODE_EXECUTE
            );

            // Remember ID
            if (!$this->id = $db->getDriver()->getLastGeneratedValue()) {
                throw new Exception\DatabaseException(sprintf(
                    'Unable to retrieve INSERT id when trying to persist %s',
                    get_class($this)
                ));
            }

            // Store in registry
            Core::storeInstanceInRegistry(get_called_class(), $this->id, $this);

        } else {
            // perform an UPDATE operation
            if (!$this->isLoaded) {
                throw new Exception\RuntimeException('Attempt to save() a record that has not been loaded');
            }

            // Get the data
            $updateData = $this->collectUpdateData();

            if (!count($updateData)) {
                return; // there is nothing to update
            }

            // Prepare and execute UPDATE
            $insert = $sql->update($this->getDbTable())->set($updateData);
            $result = $db->query($insert->getSqlString($db->getPlatform()))->execute();

            // Check if successful
            if ($result->getAffectedRows() == 0) {
                throw new Exception\DatabaseException(sprintf(
                    'Unable to update record #%s in table %s (class %s)',
                    $this->id,
                    $this->getDbTable(),
                    get_class($this)
                ));
            }
        }
    }

    /**
     * Load instance data from the database
     *
     * @throws \Thinkscape\ActiveRecord\Exception\RuntimeException
     * @throws \Thinkscape\ActiveRecord\Exception\RecordNotFoundException
     * @return void
     */
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

        $db = $this->getDb();
        $sql = new Sql($db);

        // Pick columns to load
        $columns = array_keys(static::$_properties);

        // Try to retrieve data from database
        $select = $sql->select($this->getDbTable())->columns($columns)->limit(1)->where(array(
            'id' => $this->id
        ));
        $results = $db->query($select->getSqlString($db->getPlatform()))->execute();

        // Check if the record has been retrieved
        if (!$results->count()) {
            throw new Exception\RecordNotFoundException(get_class($this), $this->id);
        }

        // Load data
        $row = $results->current();
        $this->_data = $row;
        $this->_dirtyData = [];
        $this->isLoaded = true;
    }

    /**
     * Reload data from the database
     *
     * @throws \Thinkscape\ActiveRecord\Exception\RuntimeException
     * @return void
     */
    public function reload()
    {
        // Check if not loaded
        if (!$this->isLoaded) {
            $this->load();

            return;
        }

        // Check for ID
        if (!$this->id) {
            throw new Exception\RuntimeException('Attempt to reload() a record without an ID');
        }

        // Reset instance status and load the record
        $this->_data = [];
        $this->_dirtyData = [];
        $this->isLoaded = false;

        $this->load();
    }

    /**
     * Delete the instance presence from the database
     *
     * @throws \Thinkscape\ActiveRecord\Exception\RuntimeException
     * @throws \Thinkscape\ActiveRecord\Exception\DatabaseException
     * @return mixed
     */
    public function delete()
    {
        // Check for ID
        if (!$this->id) {
            throw new Exception\RuntimeException('Attempt to reload() a record without an ID');
        }

        $db = $this->getDb();
        $sql = new Sql($db);

        // Prepare delete and run it
        $delete = $sql->delete($this->getDbTable())->where(array('id' => $this->id));
        $result = $db->query($delete->getSqlString($db->getPlatform()))->execute();

        // Check if successful
        if ($result->getAffectedRows() == 0) {
            throw new Exception\DatabaseException(sprintf(
                'Unable to delete record #%s in table %s (class %s)',
                $this->id,
                $this->getDbTable(),
                get_class($this)
            ));
        }

        // Reset instance state
        $this->isLoaded = false;
    }

    /**
     * Return Zend\Db\Adapter to use for current operation
     *
     * @throws ConfigException
     * @return Adapter
     */
    protected function getDb()
    {
        // Try to retrieve the db instance that's associated with this instance.
        if ($this->_db) {
            return $this->_db;
        }

        // Try to get the default database for this class
        return static::getDefaultDb();
    }

    /**
     * Get db table (collection) name for storing this ActiveRecord data.
     *
     * This method is declared as dynamic to allow for table-based sharding.
     *
     * @return string
     */
    public function getDbTable()
    {
        return static::getStaticDbTable();
    }

    /**
     * Get db table (collection) name for storing this ActiveRecord data.
     *
     * This static method is called from find* methods.
     *
     * @return string
     */
    public static function getStaticDbTable()
    {
        if (!empty(static::$_dbTable)) {
            return static::$_dbTable;
        } else {
            // infer db table from class name
            $table = get_called_class();
            $table = substr($table, strrpos($table, '\\') + 1);
            $table = strtolower($table);

            return $table;
        }
    }

    /**
     * Return the default Zend\Db\Adapter for this class
     *
     * @throws ConfigException
     * @return Adapter
     */
    protected static function getDefaultDb()
    {
        // Use subclass default db (set via MySubClass::setDefaultDb())
        $calledClass = get_called_class();
        if (isset(self::$_subclassDefaultDb[$calledClass]) && self::$_subclassDefaultDb[$calledClass]) {
            return self::$_subclassDefaultDb[$calledClass];
        }

        // Use superclass default db
        if (static::$_classDefaultDb) {
            return static::$_classDefaultDb;
        }

        // Use global ActiveRecord default db
        if ($db = ZendDb::getGlobalDefaultDb()) {
            return $db;
        }

        throw new ConfigException('Please configure a Zend\Db\Adapter instance to use with ActiveRecord.');
    }

    /**
     * Retrieve global (trait-level) default db adapter.
     *
     * This method is required to access the reference stored in static property of the
     * trait, because calls to getDb() on classes using the trait will change the scope of self::
     * creating a localized static version of the reference.
     *
     * Interestingly, the method cannot be set as protected - whenever a subclass tries to access
     * such method, PHP will throw "Call to protected method" fatal error, even though the
     * superclass consumes the trait and has access to it.
     *
     * @return null|Adapter
     */
    public static function getGlobalDefaultDb()
    {
        return self::$_globalDefaultDb;
    }

    /**
     * Set the default Zend\Db\Adapter to use for all instances.
     *
     * This method can be used in the following ways:
     *     1) Thinkscape\ActiveRecord\Persistence\ZendDb::setDefaultDb()
     *        Set a default db adapter for all objects that use this persistence method (including all custom
     *        classes and their respective subclasses)
     *
     *     2) App\ModelClass::setDefaultDb()
     *        Set a default db adapter for your custom ModelClass and all its subclasses.
     *
     *     3) App\ModelSubclass::setDefaultDb();
     *        Assuming ModelClassSubclass extends ModelClass, this will set a default adapter for this subclass.
     *
     * @param  Adapter|null $db Adapter to use, or null to remove reference for particular class.
     * @return void
     */
    public static function setDefaultDb(Adapter $db = null)
    {
        $calledClass = get_called_class();

        // Set a global, default db adapter for all classes using Persistence\ZendDb
        if ($calledClass === __TRAIT__) {
            self::$_globalDefaultDb = $db;

            return;
        }

        // Set superclass default db adapter
        if ($calledClass === __CLASS__) {
            static::$_classDefaultDbOwnerClass = __CLASS__;
            static::$_classDefaultDb = $db;

            return;
        }

        // Set subclass default db adapter
        self::$_subclassDefaultDb[$calledClass] = $db;
    }

    /**
     * Set instance-specific DB adapter
     *
     * @param Adapter $db
     */
    public function setDb(Adapter $db)
    {
        $this->_db = $db;
    }
}
