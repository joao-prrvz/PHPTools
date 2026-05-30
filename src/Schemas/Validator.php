<?php
namespace PHPTools\Schemas;

use PHPTools\Schemas\Attributes as SA;
use PHPTools\Schemas\Attributes\ErrorType;
use PHPTools\Schemas\Attributes\IMutate;
use PHPTools\Schemas\Attributes\Validates\IValidate;
use PHPTools\Schemas\Exceptions\DateConvertException;
use PHPTools\Schemas\Exceptions\SchemaConvertException;
use PHPTools\Schemas\Exceptions\EnumConvertException;
use PHPTools\Schemas\Exceptions\PrimitiveConvertException;
use PHPTools\Schemas\Traits\TypeConverter;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

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
            $refProps = $this->getNotIgnoredProperties();
            foreach ($refProps as $refProp) {
                $fields[$refProp->name] = $this->getPropretyFieldName($refProp);
            }
            return $fields;
        }
    }

    private function getNotIgnoredProperties() {
        $refProps = $this->refClass->getProperties();
        $props = [];
        foreach ($refProps as $refProp) {
            if (!isset($refProp->getAttributes(SA\Ignore::class)[0]))
                $props[] = $refProp;
        }
        return $props;
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

    /**
     * @return T
     */
    public function tryParse(): object {
        $obj = $this->refClass->newInstance();
        foreach ($this->fields as $propName => $field) {
            $refProp = $this->refClass->getProperty($propName);
            if ($this->checkRequired($refProp, $field))
                $this->setValue($refProp, $obj, $field);
        }
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

    private function addError(string $field, string|array $message) {
        if (!array_key_exists($field, $this->errors))
            $this->errors[$field] = [];
        if (is_array($message))
            $this->errors[$field] = $message;
        else
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
        $errors = [];
        foreach ($refTypes as $refType) {
            try {
                $value = $this->tryConvertType($refProp, $refType->getName(), $value, $refType->allowsNull());
            } catch (PrimitiveConvertException $e) {
                $errors[] = $e->type;
            } catch (SchemaConvertException $e) {
                $this->addError($field, $e->errors);
            } catch (EnumConvertException $e) {
                $this->addError($field, $e->getMessage());
            } catch (DateConvertException $e) {
                $this->addError($field, $e->getMessage());
            }
        }
        if (count($this->errors[$field] ?? []) > 0)
            return;
        if (count($errors) >= count($refTypes)) {
            $this->addError($field, $this->generateTypeErrorMessage($refProp, $refTypes));
            return;
        }
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