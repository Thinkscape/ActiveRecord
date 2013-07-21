<?php
namespace ThinkscapeTest\ActiveRecord\TestAsset;

use Thinkscape\ActiveRecord\Core;
use Thinkscape\ActiveRecord\Persistence;
use Thinkscape\ActiveRecord\PropertyFilter;

/**
 * Class PropertyFilteredModel
 *
 * @property int    $filterFunction
 * @property string $filterStaticMethod
 * @property string $filterStaticMethod2
 * @property string $filterClosure
 * @property string $filterChain
 * @property string $filterChain2
 * @property mixed  $unfiltered
 */
class PropertyFilteredModel
{
    use Core;
    use PropertyFilter;
    use Persistence\Memory;

    protected static $_properties = [
        'filterFunction'      => true,
        'filterStaticMethod'  => true,
        'filterStaticMethod2' => true,
        'filterChain'         => true,
        'filterChain2'        => true,
        'unfiltered'          => true
    ];

    protected static $_filters = [
        'filterFunction'      => 'intval',
        'filterStaticMethod'  => array(__CLASS__, 'filterStaticMethod'),
        'filterStaticMethod2' => 'static::filterStaticMethod',
        'filterChain'         => [
            'intval',
            array(__CLASS__, 'filterStaticMethod'),
        ],
        'filterChain2'        => [
            'doubleval',
            'round',
            'number_format'
        ]
    ];

    public static function filterStaticMethod($value)
    {
        return 'filterStaticMethod';
    }
}
