<?php
namespace PHPTools\Tests\Models;

enum UserType : string {
    case ADMIN = "admin";
    case MEMBER = "member";
}