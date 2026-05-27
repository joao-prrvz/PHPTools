<?php
namespace PHPTools\Schemas\Attributes;

use Attribute;
use PHPTools\Schemas\Attributes\Validates\IValidate;
use PHPTools\Schemas\Traits\DefaultMessage;
use PHPTools\Schemas\Traits\TypeConverter;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ArrayType implements IValidate, IMutate {
    public string $message;

    use TypeConverter, DefaultMessage;

    /** @var string[] */
    public array $types;

    /**
     * Undocumented function
     *
     * @param class-string[] ...$types
     */
    public function __construct(array $types, ?string $message = null) {
        $this->types = $types;
        $this->message = $this->default($message, "Must be of type". implode("or ", $this->types));
    }

    public function validate(ReflectionProperty $refProp, mixed $value): bool {
        $result = [];
        $allowsNull = in_array("null", $this->types);
        foreach ($value as $key => $v) {
            foreach ($this->types as $type)
                $result[] = $this->tryConvertType($refProp, $type, $v, $allowsNull);
        }
        return in_array(true, $result) || count($result) === 0;
    }

    public function mutate(ReflectionProperty $refProp, mixed $value): mixed {
        $allowsNull = in_array("null", $this->types);
        foreach ($value as $key => $v) {
            foreach ($this->types as $type) {
                $this->tryConvertType($refProp, $type, $v, $allowsNull);
                $value[$key] = $v;
            }
        }
        return $value;
    }
}