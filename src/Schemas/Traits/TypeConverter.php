<?php
namespace PHPTools\Schemas\Traits;

use PHPTools\Schemas\Attributes as SA;
use PHPTools\Schemas\ClassConvertException;
use PHPTools\Schemas\PrimitiveConvertException;
use PHPTools\Schemas\Validator;
use ReflectionProperty;

trait TypeConverter {
    private function tryConvertType(ReflectionProperty $refProp, string $typeName, mixed $value, bool $allowsNull): mixed {
        $convertedValue = $value;
        if ($allowsNull && $value === null)
            return null; 
        if ($this->propretyIsStrict($refProp)) {
            if (get_debug_type($value) === $typeName)
                return $value;
            throw new PrimitiveConvertException($typeName);
        }
            
        switch($typeName) {
            case "int":
                $value = $this->toInt($value);
                break;
            case "float":
                $value = $this->toFloat($value);
                break;
            case "bool":
                $value = $this->toBool($value);
                break;
            case "string":
                $value = (string)$value;
                break;
            default:
                if (class_exists($typeName) && is_array($value)) {
                    $validator = new Validator($typeName, $value);
                    $value = $validator->parse();
                    if ($value === null)
                        throw new ClassConvertException($typeName, $validator->errors);
                }
                    
                else
                    $value = get_debug_type($value) === $typeName ? $value : null;
                break;
        }

        if ($value === null)
            throw new PrimitiveConvertException($typeName);
        return $value;
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