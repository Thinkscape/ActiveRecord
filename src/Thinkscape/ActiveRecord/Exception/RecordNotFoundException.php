<?php
namespace Thinkscape\ActiveRecord\Exception;

class RecordNotFoundException extends RuntimeException implements ExceptionInterface
{
    protected $className;
    protected $id;

    public function __construct($className, $id)
    {
        $this->className = $className;
        $this->id = $id;

        return parent::__construct(sprintf(
            'Cannot find record #%s of class %s',
            $id,
            $className
        ));
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

}
