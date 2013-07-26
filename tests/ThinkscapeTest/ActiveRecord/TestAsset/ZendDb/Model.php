<?php
namespace ThinkscapeTest\ActiveRecord\TestAsset\ZendDb;

use Thinkscape\ActiveRecord\Core;
use Thinkscape\ActiveRecord\Persistence;

class Model
{
    use Core;
    use Persistence\ZendDb;

    /**
     * A protected local variable behind accessors
     *
     * @var mixed
     */
    protected $protectedProperty;

    protected static $_dbTable = 'model';

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
