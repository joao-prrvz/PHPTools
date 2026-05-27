<?php
namespace PHPTools\Tests\Schemas;

use PHPTools\Schemas\Attributes as SA;
use PHPTools\Schemas\Attributes\Validates\Filters;

class UserSchema {
    #[Filters\Email]
    public string $email;
    #[SA\Strict]
    public string $name;
    #[SA\Field("birth_year"), SA\Required]
    public int $birthYear;
    #[Filters\IP]
    public string $ip;
    public WebsiteSchema $website;
}