<?php
namespace ThinkscapeTest\ActiveRecord;

use Thinkscape\ActiveRecord\Core;
use Thinkscape\ActiveRecord\Persistence\ZendDb;
use ThinkscapeTest\ActiveRecord\TestAsset\BaseSubclass;
use ThinkscapeTest\ActiveRecord\TestAsset\BaseSuperclass;
use ThinkscapeTest\ActiveRecord\TestAsset\ZendDb\MinimalModel;
use ThinkscapeTest\ActiveRecord\TestAsset\ZendDb\Model;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Ddl;

class ZendDbTest extends AbstractPersistenceTest
{
    /**
     * @var Adapter
     */
    protected $adapter;

    protected static $schemaCleanup = false;

    public function setup()
    {
        if (!class_exists('Zend\Db\Adapter\Adapter')) {
            $this->markTestSkipped('Zend\Db is required for this test.');
        }

        // Create new adapter
        $this->adapter = $this->getAdapter();

        // Set default adapter on classes
        Model::setDefaultDb($this->adapter);
        MinimalModel::setDefaultDb($this->adapter);
    }

    public function tearDown()
    {
        if (!class_exists('Zend\Db\Adapter\Adapter')) {
            return;
        }

        // drop test tables
        if (static::$schemaCleanup) {
            static::$schemaCleanup = false;

            try {
                $adapter = $this->adapter;
                $ddl = new Ddl\DropTable('model');
                $sql = new Sql($adapter);
                $adapter->query($sql->getSqlStringForSqlObject($ddl))->execute();
            } catch (\PDOException $e) {}
        }

        // disconnect
        if ($this->adapter && $this->adapter->getDriver()->getConnection()->isConnected()) {
            $this->adapter->getDriver()->getConnection()->disconnect();
        }

        // reset default db adapter
        Model::setDefaultDb(null);
        MinimalModel::setDefaultDb(null);
        ZendDb::setDefaultDb(null);

        // clear instance registry
        Core::clearInstanceRegistry();
    }

    protected function getTestAssetNS()
    {
        return 'ThinkscapeTest\ActiveRecord\TestAsset\ZendDb';
    }

    protected function assertEntityPersisted($entity)
    {
        $adapter = $this->adapter;
        $sql = new Sql($adapter);
        $select = $sql->select($entity->getDbTable())->where(array('id' => $entity->id))->columns(array('id'));
        $result = $adapter->query($select->getSqlString($adapter->getPlatform()))->execute();
        $this->assertEquals(
            1,
            $result->count(),
            'Entity ' . get_class($entity) . ' #' . $entity->id . ' is persisted'
        );
    }

    protected function assertEntityPropertyPersisted($value, $entity, $property)
    {
        $adapter = $this->adapter;
        $sql = new Sql($adapter);
        $select = $sql->select($entity->getDbTable())->where(array('id' => $entity->id))->columns(array($property));
        $result = $adapter->query($select->getSqlString($adapter->getPlatform()))->execute();
        $this->assertEquals(
            1,
            $result->count(),
            'Entity ' . get_class($entity) . ' #' . $entity->id . ' is persisted'
        );
        $row = $result->current();
        $this->assertArrayHasKey($property, $row);
        $this->assertEquals($value, $row[$property], 'Entity property "' . $property . '"');
    }

    /**
     * Insert entity data into the database
     *
     * @param  string $class Class name of object being created
     * @param  array  $data  The data to insert
     * @return int    The ID of newly inserted record
     */
    protected function injectDbWithEntityData($class, $data = [])
    {
        $table = $class::getStaticDbTable();
        $adapter = $this->adapter;
        $sql = new Sql($adapter);
        $select = $sql->insert($table)->values($data);
        $result = $adapter->query($select->getSqlString($adapter->getPlatform()))->execute();
        $this->assertNotNull($result->getAffectedRows());
        $id = $adapter->getDriver()->getLastGeneratedValue();
        $this->assertTrue(is_numeric($id));

        return (int) $id;
    }

    /**
     * @return Adapter
     */
    protected function getMockAdapter()
    {
        $mockDriver = $this->getMock('Zend\Db\Adapter\Driver\DriverInterface');
        $mockConnection = $this->getMock('Zend\Db\Adapter\Driver\ConnectionInterface');
        $mockDriver->expects($this->any())->method('checkEnvironment')->will($this->returnValue(true));
        $mockDriver->expects($this->any())->method('getConnection')->will($this->returnValue($mockConnection));
        $mockPlatform = $this->getMock('Zend\Db\Adapter\Platform\PlatformInterface');
        $mockStatement = $this->getMock('Zend\Db\Adapter\Driver\StatementInterface');
        $mockDriver->expects($this->any())->method('createStatement')->will($this->returnValue($mockStatement));

        return new Adapter($mockDriver, $mockPlatform);
    }

    /**
     * @return Adapter
     */
    protected function getAdapter()
    {
        global $globalTestConfiguration;

        if (
            !isset($globalTestConfiguration) ||
            !isset($globalTestConfiguration['zenddb']) ||
            !isset($globalTestConfiguration['zenddb']['driver'])
        ) {
            $this->markTestIncomplete(
                'Invalid configuration found in test.config.php. Make sure "zenddb" is set and contains' .
                'a valid config array for Zend\Db\Adapter\Adapter'
            );
        }

        $this->adapter = $adapter = new Adapter($globalTestConfiguration['zenddb']);

        // attempt to connect
        $adapter->getDriver()->getConnection()->connect();
        $this->assertTrue($adapter->getDriver()->getConnection()->isConnected(), 'DB connection established.');

        $meta = new Metadata($adapter);
        $sql = new Sql($adapter);

        static::$schemaCleanup = true;

        // drop previous tables if needed
        if (in_array('model', $meta->getTableNames())) {
            $ddl = new Ddl\DropTable('model');
            $adapter->query($sql->getSqlStringForSqlObject($ddl))->execute();
        }
        if (in_array('minimalmodel', $meta->getTableNames())) {
            $ddl = new Ddl\DropTable('minimalmodel');
            $adapter->query($sql->getSqlStringForSqlObject($ddl))->execute();
        }

        // create test tables
        $ddl = new Ddl\CreateTable('model');
        $ddl->addColumn(new Ddl\Column\Integer('id', true, null, ['auto_increment' => true]));
        $ddl->addColumn((new Ddl\Column\Varchar('magicProperty', 255))->setNullable(true));
        $ddl->addColumn((new Ddl\Column\Varchar('protectedProperty', 255))->setNullable(true));
        $ddl->addConstraint(new Ddl\Constraint\PrimaryKey('id'));
        $adapter->query($sql->getSqlStringForSqlObject($ddl), $adapter::QUERY_MODE_EXECUTE);

        $ddl = new Ddl\CreateTable('minimalmodel');
        $ddl->addColumn(new Ddl\Column\Integer('id', true, null, ['auto_increment' => true]));
        $ddl->addColumn((new Ddl\Column\Varchar('name', 255))->setNullable(true));
        $ddl->addColumn((new Ddl\Column\Varchar('value', 255))->setNullable(true));
        $ddl->addConstraint(new Ddl\Constraint\PrimaryKey('id'));
        $adapter->query($sql->getSqlStringForSqlObject($ddl), $adapter::QUERY_MODE_EXECUTE);

        // return the adapter
        return $adapter;
    }

    public function testSetDefaultGlobalDb()
    {
        $adapter = $this->getMockAdapter();
        ZendDb::setDefaultDb($adapter);

        $instance = new BaseSuperclass();
        $this->assertSame($adapter, $instance->publicGetDb());
        $this->assertSame($adapter, $instance::publicStaticGetDb());

        $instance = new BaseSubclass();
        $this->assertSame($adapter, $instance->publicGetDb());
        $this->assertSame($adapter, $instance::publicStaticGetDb());
    }

    public function testSetInstanceDb()
    {
        $adapter1 = $this->getMockAdapter();
        $adapter2 = $this->getMockAdapter();
        ZendDb::setDefaultDb($adapter1);

        $instance1 = new BaseSuperclass();
        $this->assertSame($adapter1, $instance1->publicGetDb());
        $this->assertSame($adapter1, $instance1::publicStaticGetDb());

        $instance2 = new BaseSuperclass();
        $instance2->setDb($adapter2);
        $this->assertSame($adapter2, $instance2->publicGetDb());
        $this->assertSame($adapter1, $instance2::publicStaticGetDb());

        // first instance should remain untouched
        $this->assertSame($adapter1, $instance1->publicGetDb());
        $this->assertSame($adapter1, $instance1::publicStaticGetDb());

        // new instances still use the global default db
        $instance3 = new BaseSubclass();
        $this->assertSame($adapter1, $instance3->publicGetDb());
        $this->assertSame($adapter1, $instance3::publicStaticGetDb());
    }

    public function testSetSuperclassDb()
    {
        $adapter1 = $this->getMockAdapter();
        $adapter2 = $this->getMockAdapter();
        ZendDb::setDefaultDb($adapter1);

        BaseSuperClass::setDefaultDb($adapter2);

        $instance = new BaseSuperclass();
        $this->assertSame($adapter2, $instance->publicGetDb());
        $this->assertSame($adapter2, $instance::publicStaticGetDb());

        $instance = new BaseSubclass();
        $this->assertSame($adapter2, $instance->publicGetDb());
        $this->assertSame($adapter2, $instance::publicStaticGetDb());
    }

    public function testSetSubclassDb()
    {
        $adapter1 = $this->getMockAdapter();
        $adapter2 = $this->getMockAdapter();
        $adapter3 = $this->getMockAdapter();
        ZendDb::setDefaultDb($adapter1);

        BaseSuperClass::setDefaultDb($adapter2);

        $instance1 = new BaseSuperclass();
        $this->assertSame($adapter2, $instance1->publicGetDb());
        $this->assertSame($adapter2, $instance1::publicStaticGetDb());

        // Set the subclass db adapter
        BaseSubClass::setDefaultDb($adapter3);

        // The superclass instance adapter should stay the same
        $this->assertSame($adapter2, $instance1->publicGetDb());
        $this->assertSame($adapter2, $instance1::publicStaticGetDb());

        // Create new instance of subclass
        $instance2 = new BaseSubclass();
        $this->assertSame($adapter3, $instance2->publicGetDb());
        $this->assertSame($adapter3, $instance2::publicStaticGetDb());
    }

}
