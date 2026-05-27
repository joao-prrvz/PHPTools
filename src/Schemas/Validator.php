<?php
namespace PHPTools\Schemas;

use PHPTools\Schemas\Attributes as SA;
use PHPTools\Schemas\Attributes\ErrorType;
use PHPTools\Schemas\Attributes\IMutate;
use PHPTools\Schemas\Attributes\Validates\IValidate;
use PHPTools\Schemas\Traits\TypeConverter;
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

    use TypeConverter;

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
        foreach ($refTypes as $refType)
            $results[] = $this->tryConvertType($refProp, $refType->getName(), $value, $refType->allowsNull());
        if (!in_array(true, $results))
            $this->addError($field, $this->generateTypeErrorMessage($refProp, $refTypes));
        foreach ($refProp->getAttributes(IValidate::class, ReflectionAttribute::IS_INSTANCEOF) as $refValidate) {
            $attrValidate = $refValidate->newInstance();
            if (!$attrValidate->validate($refProp, $value))
                $this->addError($field, $attrValidate->message);
        }
        foreach ($refProp->getAttributes(IMutate::class, ReflectionAttribute::IS_INSTANCEOF) as $refMutate) {
            $attrValidate = $refMutate->newInstance();
            $value = $attrValidate->mutate($refProp, $value);
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

    private function getPropretyFieldName(ReflectionProperty $refProp): string {
        $refField = $refProp->getAttributes(SA\Field::class)[0] ?? null;
        if ($refField !== null)
            return $refField->newInstance()->name;
        return $refProp->name;
    }
     
}