<?php
namespace ThinkscapeTest\ActiveRecord\TestAsset\ZendDb;

use Thinkscape\ActiveRecord\Core;
use Thinkscape\ActiveRecord\Persistence;

class MinimalModel
{
    use Core;
    use Persistence\ZendDb;

    protected static $_properties = ['name', 'value'];
}
