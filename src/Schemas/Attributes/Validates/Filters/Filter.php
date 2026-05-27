<?php
namespace PHPTools\Schemas\Attributes\Validates\Filters;

use PHPTools\Schemas\Attributes\Validates\IValidate;
use ReflectionProperty;

abstract class Filter implements IValidate {

    public function __construct(private int $filter, public string $message)
    { }

    public function validate(ReflectionProperty $refProp, mixed $value): bool {
        if (get_debug_type($value) === "array")
            return filter_var_array($value, $this->filter);
        return filter_var($value, $this->filter) !== false;
    }
}