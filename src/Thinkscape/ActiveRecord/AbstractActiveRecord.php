<?php

namespace Thinkscape\ActiveRecord;

use Thinkscape\ActiveRecord\Persistence\PersistenceInterface;

abstract class AbstractActiveRecord implements PersistenceInterface
{
    use Core;
}