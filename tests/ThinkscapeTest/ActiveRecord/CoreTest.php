<?php
namespace ThinkscapeTest\ActiveRecord;

class CoreTest extends \PHPUnit_Framework_TestCase
{
    public function testPhpVersion()
    {
        $this->assertTrue(version_compare(PHP_VERSION, '5.4.3', '>='), 'This component requires PHP 5.4.3 or newer');
    }
}
