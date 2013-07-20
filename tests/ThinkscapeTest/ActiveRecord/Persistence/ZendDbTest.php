<?php
namespace ThinkscapeTest\ActiveRecord;

use Thinkscape\ActiveRecord\Persistence\ZendDb;
use ThinkscapeTest\ActiveRecord\TestAsset\BaseSubclass;
use ThinkscapeTest\ActiveRecord\TestAsset\BaseSuperclass;
use Zend\Db\Adapter\Adapter;

class ZendDbTest extends AbstractPersistenceTest
{
    /**
     * @var Adapter
     */
    protected $adapter;

    public function setup()
    {
        if (!class_exists('Zend\Db\Adapter\Adapter')) {
            $this->markTestSkipped('Zend\Db is required for this test.');
        }
    }

    public function tearDown()
    {
        if (!class_exists('Zend\Db\Adapter\Adapter')) {
            return;
        }

        // reset default db adapter
        ZendDb::setDefaultDb(null);
    }

    protected function assertEntityPersisted($entity)
    {
        $this->markTestIncomplete('Zend\Db Persistence is not yet implemented');
    }

    protected function assertEntityPropertyPersisted($value, $instance, $property)
    {
        $this->markTestIncomplete('Zend\Db Persistence is not yet implemented');
    }

    /**
     * @return Adapter
     */
    public function getMockAdapter()
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
