<?php
namespace PHPTools\Schemas\Exceptions;

use Exception;

class PrimitiveConvertException extends Exception {
    public function __construct(public string $type)
    { }
}