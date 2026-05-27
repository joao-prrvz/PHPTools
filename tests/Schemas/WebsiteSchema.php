<?php
namespace PHPTools\Tests\Schemas;

use PHPTools\Schemas\Attributes\Validates\Filters;

class WebsiteSchema {
    #[Filters\Domain]
    public string $domain;
    #[Filters\URL]
    public string $url;
}