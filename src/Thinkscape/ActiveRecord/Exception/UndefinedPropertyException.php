<?php
namespace Thinkscape\ActiveRecord\Exception;

class UndefinedPropertyException extends \DomainException implements ExceptionInterface
{
    public function __construct($class, $property)
    {
        $message = sprintf(
            'Undefined property: %s::$%s in %s:%s',
            $class,
            $property,
            $this->file,
            $this->line
        );
        parent::__construct($message);
    }
}
