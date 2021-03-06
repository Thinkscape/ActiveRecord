<?php
namespace ThinkscapeTest\ActiveRecord\TestAsset;

use Thinkscape\ActiveRecord\AbstractActiveRecord;
use Thinkscape\ActiveRecord\Persistence;

class BaseSuperclass extends AbstractActiveRecord
{
    use Persistence\ZendDb;

    protected static $_properties = ['name'];

    public function publicGetDb()
    {
        return $this->getDb();
    }

    public static function publicStaticGetDb()
    {
        return static::getDefaultDb();
    }
}
