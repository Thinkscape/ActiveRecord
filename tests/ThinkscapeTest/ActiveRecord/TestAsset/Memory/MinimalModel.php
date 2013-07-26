<?php
namespace ThinkscapeTest\ActiveRecord\TestAsset\Memory;

use Thinkscape\ActiveRecord\Core;
use Thinkscape\ActiveRecord\Persistence;

class MinimalModel
{
    use Core;
    use Persistence\Memory;

    protected static $_properties = ['name', 'value'];
}
