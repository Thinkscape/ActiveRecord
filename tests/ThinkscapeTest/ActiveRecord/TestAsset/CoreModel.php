<?php
namespace ThinkscapeTest\ActiveRecord\TestAsset;

use Thinkscape\ActiveRecord\Core;
use Thinkscape\ActiveRecord\Persistence;

class CoreModel
{
    use Core;
    use Persistence\Memory;

    /**
     * A protected local variable behind accessors
     *
     * @var mixed
     */
    protected $protectedProperty;

    protected static $_properties = [
        'magicProperty',                // < this property is set inline
        'protectedProperty' => true,    // < this property does not have any properties, but a boolean
    ];

    /**
     * @param mixed $bar
     */
    public function setProtectedProperty($bar)
    {
        $this->protectedProperty = $bar;
    }

    /**
     * @return mixed
     */
    public function getProtectedProperty()
    {
        return $this->protectedProperty;
    }
}
