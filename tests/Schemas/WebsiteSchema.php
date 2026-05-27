<?php
namespace PHPTools\Tests\Schemas;

use PHPTools\Schemas\Attributes\Validates\Filters;
use PHPTools\Schemas\Attributes as SA;


class WebsiteSchema {
    #[Filters\Domain]
    public string $domain;
    #[Filters\URL]
    public string $url;
    #[Filters\IP]
    public string $ip;
    #[SA\Validates\Min(1), SA\Validates\Max(5)]
    public float $rating;
    #[SA\Sanitize, SA\ArrayType(["string"]), SA\Validates\CountMin(1), SA\Validates\CountMax(3)]
    public array $tags;
}