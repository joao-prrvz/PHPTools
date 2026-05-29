<?php
namespace PHPTools\Schemas\Exceptions;

use Exception;

class DateConvertException extends Exception {
    public function __construct(string $message) {
        parent::__construct($message);
    }
}