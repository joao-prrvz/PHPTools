<?php
namespace PHPTools\Schemas\Exceptions;

use Exception;

class ClassConvertException extends Exception {
    public function __construct(public string $type, public array $errors)
    { }
}