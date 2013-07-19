<?php
namespace Thinkscape\ActiveRecord\Persistence;

use Zend\Db\Adapter\Adapter;
use Thinkscape\ActiveRecord\Exception\ConfigException;

trait ZendDb
{
    public function save()
    {
    }

    public function load()
    {
    }

    public function reload()
    {
    }

    public function delete()
    {
    }

    public function getFieldsFromDatabase()
    {
    }

    /**
     * Instance's db adapter.
     *
     * @var Adapter|null
     */
    protected $_db;

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

        return new ConfigException('Please configure a Zend\Db\Adapter instance to use with ActiveRecord.');
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
     * @param Adapter|null $db Adapter to use, or null to remove reference for particular class.
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