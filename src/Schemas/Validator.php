<?php
namespace PHPTools\Schemas;

use PHPTools\Schemas\Attributes as SA;
use PHPTools\Schemas\Attributes\ErrorType;
use PHPTools\Schemas\Attributes\Validates\IValidate;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

use function PHPSTORM_META\elementType;

/**
 * @template T
 */
class Validator {
    private bool $_valide = false;
    private array $data;
    private ReflectionClass $refClass;

    /** @var array<string, string[]> */
    public array $errors = [];

    public array $errorsValues {
        get {
            $errorsValues = [];
            foreach ($this->errors as $field => $errors)

                $errorsValues[] = array_map(fn($e) => "'$field' ".substr_replace($e, strtolower($e[0]), 0, 1), $errors);
            return array_merge(... $errorsValues);
        }
    }

    /** @var array<string, string> */
    public array $fields {
        get {
            $fields = [];
            $refProps = $this->refClass->getProperties();
            foreach ($refProps as $refProp) {
                $fields[$refProp->name] = $this->getPropretyFieldName($refProp);
            }
            return $fields;
        }
    }

    /**
     * Undocumented function
     *
     * @param class-string<T> $schemaClass
     */
    public function __construct(string $schemaClass, array $data) {
        $this->refClass = new ReflectionClass($schemaClass);
        $this->data = [... $data];
    }

    /**
     * @return ?T
     */
    public function parse(): ?object {
        $obj = $this->refClass->newInstance();
        foreach ($this->fields as $propName => $field) {
            $refProp = $this->refClass->getProperty($propName);
            if ($this->checkRequired($refProp, $field))
                $this->setValue($refProp, $obj, $field);
        }
        if (count($this->errors) > 0)
            return null;
        return $obj;
    }

    private function checkRequired(ReflectionProperty $refProp, string $field) {
        if (array_key_exists($field, $this->data))
            return true;
        $refRequired = $refProp->getAttributes(SA\Required::class)[0] ?? null;
        if ($refRequired === null)
            return false;
        $attrRequired = $refRequired->newInstance();
        $this->addError($field, $attrRequired->message);
        return false;
    }

    private function addError(string $field, string $message) {
        if (!array_key_exists($field, $this->errors))
            $this->errors[$field] = [];
        $this->errors[$field][] = $message;
    }

    private function setValue(ReflectionProperty $refProp, object $instance, string $field) {
        $refType = $refProp->getType();
        $value = $this->data[$field];
        $refTypes = [];
        if ($refType instanceof ReflectionNamedType) 
            $refTypes[] = $refType;
        else if ($refType instanceof ReflectionUnionType)
            $refTypes = $refType->getTypes();
        $results = [];
        foreach ($refTypes as $refType) {
            $results[] = $this->tryConvertType($refProp, $refType, $value);
        }
        if (!in_array(true, $results))
            $this->addError($field, $this->generateTypeErrorMessage($refProp, $refTypes));
        foreach ($refProp->getAttributes(IValidate::class, ReflectionAttribute::IS_INSTANCEOF) as $refValidate) {
            $attrValidate = $refValidate->newInstance();
            if (!$attrValidate->validate($refProp, $value))
                $this->addError($field, $attrValidate->message);
        }
        if (count($this->errors) < 1)
            $refProp->setValue($instance, $value);
    }

    private function generateTypeErrorMessage(ReflectionProperty $refProp, array $refTypes) {
        $refErrorType = $refProp->getAttributes(ErrorType::class)[0] ?? null;
        if ($refErrorType !== null)
            return $refErrorType->newInstance()->message;

        $message = "Must be of type ";
        $typeNames = array_map(fn($t) => $t->getName(), $refTypes);
        $message .= implode("or ", $typeNames);
        return $message;
    }

    private function tryConvertType(ReflectionProperty $refProp, ReflectionNamedType $refType, mixed &$value): bool {
        $typeName = $refType->getName();
        $convertedValue = $value;
        if ($this->propretyIsStrict($refProp))
            return get_debug_type($value) === $refType->getName();

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
                $convertedValue = get_debug_type($value) === $refType->getName() ? $value : null;
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

    private function getPropretyFieldName(ReflectionProperty $refProp): string {
        $refField = $refProp->getAttributes(SA\Field::class)[0] ?? null;
        if ($refField !== null)
            return $refField->newInstance()->name;
        return $refProp->name;
    }

    private function propretyIsStrict(ReflectionProperty $refProp): bool {
        return count($refProp->getAttributes(SA\Strict::class)) > 0;
    }
     
}