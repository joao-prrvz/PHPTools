<?php
namespace PHPTools\Schemas\Exceptions;

use Exception;

class EnumConvertException extends Exception {
    public function __construct(string $enum) {
        $values = array_map(fn($c) => $c->value, $enum::cases());
        parent::__construct("Must be ".implode(" or ", $values));
    }
}