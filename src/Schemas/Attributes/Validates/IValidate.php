<?php
namespace PHPTools\Schemas\Attributes\Validates;

use ReflectionProperty;

interface IValidate {
    public string $message { get; }
    public function validate(ReflectionProperty $refProp, mixed $value): bool;
}