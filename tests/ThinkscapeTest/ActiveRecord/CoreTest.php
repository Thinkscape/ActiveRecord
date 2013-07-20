<?php
namespace ThinkscapeTest\ActiveRecord;

use ThinkscapeTest\ActiveRecord\TestAsset\CoreModel;

class CoreTest extends \PHPUnit_Framework_TestCase
{
    public function testPhpVersion()
    {
        $this->assertTrue(version_compare(PHP_VERSION, '5.4.3', '>='), 'This component requires PHP 5.4.3 or newer');
    }

    public function testAttributeManipulation()
    {
        $instance = new CoreModel();
        $instance->magicProperty = 'foo';
        $instance->protectedProperty = 'bar';
        $this->assertSame('foo', $instance->magicProperty);
        $this->assertSame('bar', $instance->protectedProperty);
    }

    public function testAccessingKnownUnsetVariable()
    {
        $instance = new CoreModel();
        $this->assertNull($instance->magicProperty);
        $this->assertNull($instance->protectedProperty);
    }

    public function testAccessingUnknownPropertyThrowsException()
    {
        $instance = new CoreModel();

        $this->setExpectedException('Thinkscape\ActiveRecord\Exception\UndefinedPropertyException');
        $instance->unknownProperty;
    }
}
