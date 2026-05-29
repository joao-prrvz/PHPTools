<?php
namespace PHPTools\Tests\Schemas;

enum UserType : string {
    case ADMIN = "admin";
    case MEMBER = "member";
}