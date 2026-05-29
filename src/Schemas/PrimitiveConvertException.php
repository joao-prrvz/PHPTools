<?php
namespace PHPTools\Schemas;

use Exception;

class PrimitiveConvertException extends Exception {
    public function __construct(public string $type)
    { }
}