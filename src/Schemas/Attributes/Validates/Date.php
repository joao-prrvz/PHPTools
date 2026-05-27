<?php
namespace PHPTools\Schemas\Attributes\Validates;

use DateTime;
use ReflectionProperty;

class Date implements IValidate {
    public function __construct(public string $format, public string $message = "Is the wrong time format")
    { }

    public function validate(ReflectionProperty $refProp, mixed $value): bool {
        return DateTime::createFromFormat($this->format, $value) !== false;
    }
}