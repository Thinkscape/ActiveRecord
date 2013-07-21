<?php
namespace ThinkscapeTest\ActiveRecord;

use ThinkscapeTest\ActiveRecord\TestAsset\PropertyFilteredModel;

class PropertyFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testExistingFieldUnfiltered()
    {
        $instance = new PropertyFilteredModel();
        $instance->unfiltered = 'foo';
        $this->assertSame('foo', $instance->unfiltered);
    }

    public function testNamedFunction()
    {
        $instance = new PropertyFilteredModel();
        $instance->filterFunction = '1000';
        $this->assertSame(1000, $instance->filterFunction);
    }

    public function testStaticMethod()
    {
        $instance = new PropertyFilteredModel();
        $instance->filterStaticMethod = 'foo';
        $this->assertSame('filterStaticMethod', $instance->filterStaticMethod);
    }

    public function testStaticMethod2()
    {
        $instance = new PropertyFilteredModel();
        $instance->filterStaticMethod2 = 'foo';
        $this->assertSame('filterStaticMethod', $instance->filterStaticMethod2);
    }

    public function testFilterChain()
    {
        $instance = new PropertyFilteredModel();
        $instance->filterChain = 'foo';
        $this->assertSame('filterStaticMethod', $instance->filterChain);
    }

    public function testFilterChain2()
    {
        $instance = new PropertyFilteredModel();
        $instance->filterChain2 = 1000000.49;
        $this->assertSame(number_format(1000000), $instance->filterChain2);
    }

}
