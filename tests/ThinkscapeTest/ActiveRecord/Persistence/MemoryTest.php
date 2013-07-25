<?php
namespace ThinkscapeTest\ActiveRecord;

use Thinkscape\ActiveRecord\Persistence\Memory;
use ThinkscapeTest\ActiveRecord\TestAsset\CoreModel;

class MemoryTest extends AbstractPersistenceTest
{

    protected function assertEntityPersisted($instance)
    {
        $storage = & Memory::_getInternalStorage();
        $this->assertArrayHasKey($instance->id, $storage);
    }

    protected function assertEntityPropertyPersisted($value, $instance, $property)
    {
        $storage = & Memory::_getInternalStorage();
        $this->assertArrayHasKey($instance->id, $storage);
        $this->assertArrayHasKey($property, $storage[$instance->id]);
        $this->assertSame($value, $storage[$instance->id][$property]);
    }

    protected function getInstanceClass()
    {
        return 'ThinkscapeTest\ActiveRecord\TestAsset\CoreModel';
    }
}
