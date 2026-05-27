<?php
namespace PHPTools\Schemas\Traits;

use PHPTools\Schemas\Attributes as SA;
use PHPTools\Schemas\Validator;
use ReflectionProperty;

trait TypeConverter {
    private function tryConvertType(ReflectionProperty $refProp, string $typeName, mixed &$value, bool $allowsNull): bool {
        $convertedValue = $value;
        if ($allowsNull && $value === null)
            return true; 
        if ($this->propretyIsStrict($refProp))
            return get_debug_type($value) === $typeName;
        switch($typeName) {
            case "int":
                $convertedValue = $this->toInt($value);
                break;
            case "float":
                $convertedValue = $this->toFloat($value);
                break;
            case "bool":
                $convertedValue = $this->toBool($value);
                break;
            case "string":
                $convertedValue = (string)$value;
                break;
            default:
                if (class_exists($typeName) && is_array($value)) {
                    $v = new Validator($typeName, $value);
                    $convertedValue = $v->parse();
                }
                else
                    $convertedValue = get_debug_type($value) === $typeName ? $value : null;
                break;
        }

        if ($convertedValue === null)
            return false;
        $value = $convertedValue;
        return true;
    }

    private function toInt(mixed $value): ?int {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);
        if ($intValue === false)
            return null;
        return $intValue;
    }

    private function toFloat(mixed $value): ?float {
        $intValue = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($intValue === false)
            return null;
        return $intValue;
    }

    private function toBool(mixed $value): ?bool {
        $boolValue = filter_var($value, FILTER_VALIDATE_BOOL);
        return $boolValue; 
    }

    private function propretyIsStrict(ReflectionProperty $refProp): bool {
        return count($refProp->getAttributes(SA\Strict::class)) > 0;
    }
}