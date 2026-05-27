<?php
namespace PHPTools\Schemas\Traits;

trait DefaultMessage {
    public function default(?string $message, string $default): string {
        return $message ?? $default;
    }
}