<?php
namespace PHPTools\Schemas\Attributes\Validates;

use Attribute;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Max implements IValidate{
    public string $message;

    public function __construct(private float $value, ?string $message = null){
        if ($message === null)
            $message = "Must be smaller or equal to $value";
        $this->message = $message;
    }

    public function validate(ReflectionProperty $refProp, mixed $value): bool {
        $type = get_debug_type($value);
        if ($type !== "int" && $type !== "float") {
            trigger_error("Max is supposed to be used with an int ot a float");
            return false;
        }
            return $value <= $this->value;
    }
}