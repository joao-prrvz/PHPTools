<?php
namespace PHPTools\Schemas\Attributes;

use ReflectionProperty;

class Sanitize implements IMutate {
    public function __construct(private int $sanitizer)
    { }

    public function mutate(ReflectionProperty $refProp, mixed $value): mixed {
        if (get_debug_type($value) === "array")
            return filter_var_array($value, $this->sanitizer);
        return filter_var($value, $this->sanitizer);
    }
}