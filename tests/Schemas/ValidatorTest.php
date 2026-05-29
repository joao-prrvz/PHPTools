<?php
namespace PHPTools\Tests\Schemas;

use PHPTools\Schemas\Validator;
use PHPTools\Tests\Schemas\UserInfos;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase {
    /** @var array{
     * email: string, name: string, birthday: string, friends: UserInfos[],
     * website: 
     * array{domain: string, url: string, ip: string, rating: float, tags: string[]}
     * } 
     * */
    private array $data {
        get => [
            "email" => "johndoe@example.com",
            "name" => "John",
            "birthday" => "2000-01-01",
            "website" => [
                "domain" => "johndoe.com",
                "url" => "https://johndoe.com",
                "ip" => "1.1.1.1",
                "rating" => 4.5,
                "tags" => ["portfolio"]
            ],
            "friends" => [],
            "type" => "admin",
        ];
    }

    private UserInfos $user {
        get {
            $website = new WebsiteSchema();
            $website->domain = "johndoe.com";
            $website->url = "https://johndoe.com";
            $website->ip = "1.1.1.1";
            $website->rating = 4.5;
            $website->tags = ["portfolio"];

            $user = new UserInfos();
            $user->email = "johndoe@example.com";
            $user->name = "John";
            $user->birthday = "2000-01-01";
            $user->website = $website;
            $user->friends = [];
            $user->type = UserType::ADMIN;
            return $user;
        }
    }

    #[Test]
    public function set_values() {
        $validator = new Validator(UserInfos::class, $this->data);
        $this->assertEquals($this->user, $validator->parse());
    }

    #[Test]
    public function get_fields() {
        $expected = ["email","name","birthday","website","friends", "type"];
        $validator = new Validator(UserInfos::class, $this->data);
        $fields = array_values($validator->fields);
        $this->assertSame($expected, $fields);
    }

    

    #[Test]
    public function convert_types_strict() {
        $data = $this->data;
        $data["name"] = 0;
        $validator = new Validator(UserInfos::class, $data);
        $user = $validator->parse();
        $this->assertNull($user);
    }

    #[Test]
    public function union_type() {
        $data = $this->data;
        $data["website"] = null;
        $validator = new Validator(UserInfos::class, $data);
        $user = $validator->parse();
        $this->assertNotNull($user);
    }

    #[Test, DataProvider("convertTypesCases")]
    public function convert_types(string $path, mixed $value, mixed $expected) {
        $data = $this->data;
        self::setPath($data, $path, $value);
        $validator = new Validator(UserInfos::class, $data);
        $value = self::getPath($validator->parse(), $path);
        if (is_object($expected))
            $this->assertEquals($expected, $value);
        else
            $this->assertSame($expected, $value);
    }

    #[Test, DataProvider("invalidCases")]
    public function validates_invalid_cases(string $path, mixed $value) {
        $data = $this->data;
        if ($value === "__unset__")
            self::unsetPath($data, $path);
        else
            self::setPath($data, $path, $value);
        $validator = new Validator(UserInfos::class, $data);
        $this->assertNull($validator->parse());
    }

    #[Test, DataProvider("errorMessagesCases")]
    public function error_messages(string $path, mixed $value, array $expected) {
        $data = $this->data;
        if ($value === "__unset__")
            self::unsetPath($data, $path);
        else
            self::setPath($data, $path, $value);
        $validator = new Validator(UserInfos::class, $data);
        $validator->parse();
        $this->assertSame($expected, self::getPath($validator->errors, $path));
    }

    #[Test, DataProvider("mutateCases")]
    public function mutate_cases(string $path, mixed $value, mixed $expected) {
        $data = $this->data;
        self::setPath($data, $path, $value);
        $validator = new Validator(UserInfos::class, $data);
        $data = $validator->parse();
        $value = self::getPath($data, $path);
        $this->assertEquals($expected, $value);
    }


    private static function setPath(array &$data, string $path, mixed $value): void {
        $keys = explode(".", $path);
        $ref =& $data;
        foreach ($keys as $key) {
            if (!array_key_exists($key, $ref)) {
                $ref[$key] = [];
            }
            $ref =& $ref[$key];
        }
        $ref = $value;
    }

    private static function unsetPath(array &$data, string $path): void {
        $keys = explode(".", $path);
        $last = array_pop($keys);
        $ref =& $data;
        foreach ($keys as $key) {
            if (!isset($ref[$key])) {
                return;
            }
            $ref =& $ref[$key];
        }
        unset($ref[$last]);
    }

    private static function getPath(mixed $data, string $path): mixed {
        $keys = explode('.', $path);
        foreach ($keys as $key) {
            if (is_array($data))
                $data = $data[$key] ?? null;
            elseif (is_object($data)) 
                $data = $data->$key ?? null;
            else
                return null;
        }
        return $data;
    }

    public static function invalidCases(): array {
        return [
            "strict type mismatch" => 
                ["name", 0],
            "missing required field" => 
                ["email", "__unset__"],
            "invalid email" => 
                ["email", "john"],
            "invalid ip" => 
                ["website.ip", "1.1.1"],
            "invalid url" => 
                ["website.url", "not an url"],
            "invalid domain" => 
                ["website.domain", "not a domain"],
            "rating too small" => 
                ["website.rating", -1],
            "rating too large" => 
                ["website.rating", 9999],
            "name too short" => 
                ["name", "J"],
            "name too long" => 
                ["name", "Way too long of a test name"],
            "tags count too small" =>
                ["website.tags", []],
            "tags count too large" =>
                ["website.tags", ["a","b","c","d"]],
            "invalid date" =>
                ["birthday", "Bad format"],
            "invalid friends array type" => 
                ["friends", ["csd"]],
            "invalid enum value" => 
                ["type", "guest"],
        ];
    }

    public static function convertTypesCases() {
        return [
            "string to float" =>
                ["website.rating", "4.5", 4.5],
            "int array to string array" => 
                ["website.tags", [1], ["1"]],
        ];
    }

    public static function mutateCases(): array {
        return [
            "sanitize string" => [
                "name",
                "<script></script>",
                "&lt;script&gt;&lt;/script&gt;",
            ],
            "sanitize string array" => [
                "website.tags",
                ["<script></script>"],
                ["&lt;script&gt;&lt;/script&gt;"],
            ],
        ];
    }

    public static function errorMessagesCases() {
        return [
            "strict type mismatch" => 
                ["name", 10, ["Must be of type string"]],
            "missing required field" => 
                ["email", "__unset__", ["Is required"]],
            "invalid email" => 
                ["email", "john", ["Is not a valide email"]],
            "invalid ip" => 
                ["website.ip", "1.1.1", ["Is not a valide ip address"]],
            "invalid url" => 
                ["website.url", "not an url", ["Is not a valide url"]],
            "invalid domain" => 
                ["website.domain", "not a domain", ["Is not a valide domain"]],
            "rating too small" => 
                ["website.rating", -1, ["Must be bigger or equal to 1"]],
            "rating too large" => 
                ["website.rating", 9999, ["Must be smaller or equal to 5"]],
            "name too short" => 
                ["name", "J", ["Must have more or equal to 2 characters"]],
            "name too long" => 
                ["name", "Way too long of a test name", ["Must have less or equal to 20 characters"]],
            "tags count too small" =>
                ["website.tags", [], ["Must have more or equal to 1 items"]],
            "tags count too large" =>
                ["website.tags", ["a","b","c","d"], ["Must have less or equal to 3 items"]],
            "invalid date" =>
                ["birthday", "Bad format", ["The time given must be of the format Y-m-d"]],
            "invalid friends array type" => 
                ["friends", ["csd"], ["One or more items are invalide"]],
            "invalid enum value" => 
                ["type", "guest", ["Must be admin or member"]],
        ];
    }

}