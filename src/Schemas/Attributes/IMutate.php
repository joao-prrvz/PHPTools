<?php
namespace PHPTools\Schemas\Attributes;

use ReflectionProperty;

interface IMutate {
    public function mutate(ReflectionProperty $refProp, mixed $value): mixed;
}