<?php
namespace ThinkscapeTest\ActiveRecord;

use Thinkscape\ActiveRecord\Persistence\Memory;

class MemoryTest extends AbstractPersistenceTest
{

    protected function assertEntityPersisted($instance)
    {
        $storage = & Memory::_getInternalStorage();
        $class = get_class($instance);
        $this->assertArrayHasKey($class, $storage);
        $this->assertArrayHasKey($instance->id, $storage[$class]);
    }

    protected function assertEntityPropertyPersisted($value, $instance, $property)
    {
        $storage = & Memory::_getInternalStorage();
        $class = get_class($instance);
        $this->assertArrayHasKey($class, $storage);
        $this->assertArrayHasKey($instance->id, $storage[$class]);
        $this->assertArrayHasKey($property, $storage[$class][$instance->id]);
        $this->assertSame($value, $storage[$class][$instance->id][$property]);
    }

    protected function getTestAssetNS()
    {
        return 'ThinkscapeTest\ActiveRecord\TestAsset\Memory';
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
        $storage = & Memory::_getInternalStorage();
        if (!isset($storage[$class])) {
            $storage[$class] = [];
        }

        $id = mt_rand();
        $storage[$class][$id] = $data;

        return $id;
    }
}
