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
        'magicProperty'     => true,
        'protectedProperty' => true,
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
