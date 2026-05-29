<?php
namespace PHPTools\Tests\Schemas;

use PHPTools\Schemas\Attributes as SA;
use PHPTools\Schemas\Attributes\ArrayType;
use PHPTools\Schemas\Attributes\Validates\Filters;

class UserInfos {
    #[SA\Required, Filters\Email]
    public ?string $email;
    #[SA\Strict, SA\Sanitize(FILTER_SANITIZE_FULL_SPECIAL_CHARS), SA\Validates\LengthMax(20), SA\Validates\LengthMin(2)]
    public string $name;
    #[SA\Validates\Date("Y-m-d")]
    public string $birthday;
    public ?WebsiteSchema $website;
    #[ArrayType([UserInfos::class])]
    public array $friends;
    public UserType $type;
}