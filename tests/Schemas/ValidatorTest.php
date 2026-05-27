<?php
namespace PHPTools\Tests\Schemas;

use PHPTools\Schemas\Validator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase {
    private static $data = [
        "email" => "johndoe@example.com",
        "name" => "John",
        "birth_year" => 2000
    ];

    #[Test]
    public function set_values() {
        $data = [
            "email" => "johndoe@example.com",
            "name" => "John",
            "birth_year" => 2000
        ];
        $validator = new Validator(UserSchema::class, $data);
        $user = $validator->parse();
        $expected = new UserSchema();
        $expected->email = "johndoe@example.com";
        $expected->name = "John";
        $expected->birthYear = 2000;
        $this->assertEquals($expected, $user);
    }

    #[Test]
    public function convert_types() {
        $data = [
            "email" => "johndoe@example.com",
            "name" => "John",
            "birth_year" => "2000"
        ];
        $validator = new Validator(UserSchema::class, $data);
        $user = $validator->parse();
        $this->assertEquals("int", get_debug_type($user->birthYear));
    }

    #[Test]
    public function convert_types_strict() {
        $data = [
            "email" => "johndoe@example.com",
            "name" => ["John"],
            "birth_year" => 2000
        ];
        $validator = new Validator(UserSchema::class, $data);
        $user = $validator->parse();
        $this->assertNull($user);
    }

    #[Test]
    public function convert_types_strict_message() {
        $data = [
            "email" => "johndoe@example.com",
            "name" => ["John"],
            "birth_year" => 2000
        ];
        $validator = new Validator(UserSchema::class, $data);
        $validator->parse();
        $this->assertTrue(in_array("'name' must be of type string", $validator->errorsValues));
    }

    #[Test]
    public function filter_validate_email() {
        $data = [
            "email" => "johndoeexample.com",
            "name" => "John",
            "birth_year" => 2000
        ];
        $validator = new Validator(UserSchema::class, $data);
        $user = $validator->parse();
        $this->assertNull($user);
    }

    #[Test]
    public function filter_validate_email_message() {
        $data = [
            "email" => "johndoeexample.com",
            "name" => "John",
            "birth_year" => 2000
        ];
        $validator = new Validator(UserSchema::class, $data);
        $user = $validator->parse();
        $this->assertTrue(in_array("'email' is not a valide email", $validator->errorsValues));
    }

    #[Test]
    public function filter_validate_ip_null() {
        $data = ["ip" => "1.1"];
        $validator = new Validator(UserSchema::class, $data);
        $validator->parse();
        $this->assertTrue(in_array("'ip' is not a valide ip address", $validator->errorsValues));
    }
}