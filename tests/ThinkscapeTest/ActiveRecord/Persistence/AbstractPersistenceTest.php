<?php
namespace ThinkscapeTest\ActiveRecord;

abstract class AbstractPersistenceTest extends \PHPUnit_Framework_TestCase
{
    protected $instance;

    /**
     * Check that the entity has been stored in the database
     *
     * @param  object $entity
     * @return void
     */
    abstract protected function assertEntityPersisted($entity);

    /**
     * Check that entity has a property value correctly saved in the database
     *
     * @param  mixed  $expectedValue
     * @param  object $instance
     * @param  string $property
     * @return void
     */
    abstract protected function assertEntityPropertyPersisted($expectedValue, $instance, $property);

    /**
     * Returns the namespace for test assets
     *
     * @return string
     */
    abstract protected function getTestAssetNS();

    /**
     * Insert entity data into the database
     *
     * @param  string $class Class name of object being created
     * @param  array  $data  The data to insert
     * @return int    The ID of newly inserted record
     */
    abstract protected function injectDbWithEntityData($class, $data = []);

    /**
     * Create new ActiveRecord instance for testing persistence.
     *
     * @param  null|array $config
     * @param  string     $type
     * @return object
     */
    protected function newInstance($config = null, $type = 'Model')
    {
        $className = $this->getTestAssetNS() . '\\' . $type;

        return $className::factory($config);
    }

    public function testBasicPersistenceInsert()
    {
        $instance = $this->newInstance();
        $instance->magicProperty = 'foo';
        $this->assertSame('foo', $instance->magicProperty);
        $instance->save();

        $this->assertNotNull($instance->getId());
        $this->assertNotNull($instance->id);
        $this->assertTrue(is_numeric($instance->id));

        $this->assertEntityPersisted($instance);
        $this->assertEntityPropertyPersisted('foo', $instance, 'magicProperty');
    }

    /**
     * @depends testBasicPersistenceInsert
     */
    public function testBasicPersistenceUpdate()
    {
        $instance = $this->newInstance();
        $instance->magicProperty = 'foo';
        $instance->save();

        $instance->magicProperty = 'bar';

        $this->assertEntityPersisted($instance);
        $this->assertEntityPropertyPersisted('foo', $instance, 'magicProperty');

        $instance->save();

        $this->assertEntityPersisted($instance);
        $this->assertEntityPropertyPersisted('bar', $instance, 'magicProperty');
    }

    /**
     * @depends testBasicPersistenceUpdate
     */
    public function testBasicPersistenceReloadDiscardsData()
    {
        $instance = $this->newInstance();
        $instance->magicProperty = 'foo';
        $instance->save();

        $instance->magicProperty = 'bar';
        $this->assertSame('bar', $instance->magicProperty);

        // Reload the instance from storage
        $instance->reload();

        // Check property is reverted
        $this->assertSame('foo', $instance->magicProperty);

        // Check the storage after reloading
        $this->assertEntityPersisted($instance);
        $this->assertEntityPropertyPersisted('foo', $instance, 'magicProperty');

        // Attempt a save() operation
        $instance->save();

        // Check property is intact
        $this->assertSame('foo', $instance->magicProperty);

        // Check the storage is untouched
        $this->assertEntityPersisted($instance);
        $this->assertEntityPropertyPersisted('foo', $instance, 'magicProperty');
    }

    /**
     * @depends testBasicPersistenceInsert
     */
    public function testBasicPersistenceLoad()
    {
        $class = $this->getTestAssetNS() . '\\Model';
        $data = ['magicProperty' => mt_rand()];

        $id = $this->injectDbWithEntityData($class, $data);

        $instance = $class::findById($id);
        $this->assertInstanceOf($class, $instance);
        $this->assertEquals($id, $instance->id);
        $this->assertEquals($data['magicProperty'], $instance->magicProperty);
    }

    /**
     * @depends testBasicPersistenceInsert
     */
    public function testRegistryServingSameObjectReference()
    {
        $instance = $this->newInstance();
        $instance->magicProperty = mt_rand();
        $instance->save();

        $class = $this->getTestAssetNS() . '\\Model';
        $instance2 = $class::findById($instance->id);
        $this->assertInstanceOf($class, $instance2);
        $this->assertSame($instance, $instance2);
    }

    public function testAutoTableName()
    {
        $instance = $this->newInstance([], 'MinimalModel');
        $value = $instance->value = mt_rand();
        $name  = $instance->name  = mt_rand();
        $instance->save();

        $class = $this->getTestAssetNS() . '\\MinimalModel';
        $instance2 = $class::findById($instance->id);
        $this->assertInstanceOf($class, $instance2);
        $this->assertSame($instance, $instance2);
    }

    public function testLoadingUnidentifiedEntityThrowsException()
    {
        $instance = $this->newInstance(['id' => 100000]);
        $this->setExpectedException('Thinkscape\ActiveRecord\Exception\RecordNotFoundException');
        $instance->load();
    }

    public function testReloadingUnidentifiedEntityThrowsException()
    {
        $instance = $this->newInstance(['id' => 100000]);
        $this->setExpectedException('Thinkscape\ActiveRecord\Exception\RecordNotFoundException');
        $instance->reload();
    }

}
