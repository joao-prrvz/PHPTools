<?php
namespace PHPTools\Schemas\Attributes;

use Attribute;
use Exception;
use PHPTools\Schemas\Attributes\Validates\IValidate;
use PHPTools\Schemas\Traits\DefaultMessage;
use PHPTools\Schemas\Traits\TypeConverter;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ArrayType implements IValidate, IMutate {

    use TypeConverter, DefaultMessage;

    /** @var string[] */
    public array $types;

    /**
     * Undocumented function
     *
     * @param class-string[] ...$types
     */
    public function __construct(array $types, public string $message = "One or more items are invalide") {
        $this->types = $types;
    }

    public function validate(ReflectionProperty $refProp, mixed $value): bool {
        $result = [];
        $allowsNull = in_array("null", $this->types);
        foreach ($value as $key => $v) {
            $errorCount = 0;
            foreach ($this->types as $type) {
                try {
                    $this->tryConvertType($refProp, $type, $v, $allowsNull);
                }
                catch(Exception $e) {
                    $errorCount ++;
                }
            }
            if ($errorCount >= count($this->types))
                return false;
        }
        return true;
    }

    public function mutate(ReflectionProperty $refProp, mixed $value): mixed {
        $allowsNull = in_array("null", $this->types);
        foreach ($value as $key => $v) {
            foreach ($this->types as $type) {
                try {
                    $value[$key] = $this->tryConvertType($refProp, $type, $v, $allowsNull);
                } catch (\Throwable) { }
            }
        }
        return $value;
    }
}