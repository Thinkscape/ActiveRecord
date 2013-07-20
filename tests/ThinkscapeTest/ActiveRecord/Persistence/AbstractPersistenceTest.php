<?php
namespace ThinkscapeTest\ActiveRecord;

use ThinkscapeTest\ActiveRecord\TestAsset\CoreModel;

abstract class AbstractPersistenceTest extends \PHPUnit_Framework_TestCase
{
    protected $instance;

    abstract protected function assertEntityPersisted($entity);

    abstract protected function assertEntityPropertyPersisted($value, $instance, $property);

    public function testBasicPersistenceInsert()
    {
        $instance = new CoreModel();
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
        $instance = new CoreModel();
        $instance->magicProperty = 'foo';
        $instance->save();

        $instance->magicProperty = 'bar';

        $this->assertEntityPersisted($instance);
        $this->assertEntityPropertyPersisted('foo', $instance, 'magicProperty');

        $instance->save();

        $this->assertEntityPersisted($instance);
        $this->assertEntityPropertyPersisted('foo', $instance, 'magicProperty');
    }

    /**
     * @depends testBasicPersistenceUpdate
     */
    public function testBasicPersistenceReload()
    {
        $instance = new CoreModel();
        $instance->magicProperty = 'foo';
        $instance->save();

        $instance->magicProperty = 'bar';
        $this->assertSame('bar', $instance->magicProperty);

        // Reload the instance from storage
        $instance->reload();

        // Check property is reverted
        $this->assertSame('foo', $instance->magicProperty);

        // Check the storage after reloading
        $this->assertEntityPersisted($instance->id);
        $this->assertEntityPropertyPersisted('foo', $instance, 'magicProperty');

        // Attempt a save() operation
        $instance->save();

        // Check property is intact
        $this->assertSame('foo', $instance->magicProperty);

        // Check the storage is untouched
        $this->assertEntityPersisted($instance->id);
        $this->assertEntityPropertyPersisted('foo', $instance, 'magicProperty');
    }

    public function testLoadingUnidentifiedEntityThrowsException()
    {
        $instance = new CoreModel();
        $instance->magicProperty = 'foo';

        $this->setExpectedException('Thinkscape\ActiveRecord\Exception\RuntimeException');
        $instance->load();
    }

    public function testReloadingUnidentifiedEntityThrowsException()
    {
        $instance = new CoreModel();
        $instance->magicProperty = 'foo';

        $this->setExpectedException('Thinkscape\ActiveRecord\Exception\RuntimeException');
        $instance->reload();
    }

}
