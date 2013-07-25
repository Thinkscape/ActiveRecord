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
     * Returns the instance class to use for all tests
     *
     * @return string
     */
    abstract protected function getInstanceClass();

    /**
     * Create new ActiveRecord instance for testing persistence.
     *
     * @param  null|array $config
     * @return object
     */
    protected function newInstance($config = null)
    {
        $className = $this->getInstanceClass();

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
        $instance = $this->newInstance();
        $instance->magicProperty = mt_rand();
        $instance->save();

        $class = $this->getInstanceClass();
        $instance2 = $class::findById($instance->id);
        $this->assertInstanceOf($this->getInstanceClass(), $instance2);
        $this->assertSame($instance, $instance2);
        $this->assertSame($instance->id, $instance2->id);
        $this->assertSame($instance->magicProperty, $instance2->magicProperty);
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
