<?php
namespace PHPTools\Tests\Schemas;

use PHPTools\Schemas\Validator;
use PHPTools\Tests\Schemas\UserInfos;
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
            "friends" => []
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
        $expected = ["email","name","birthday","website","friends"];
        $validator = new Validator(UserInfos::class, $this->data);
        $fields = array_values($validator->fields);
        $this->assertSame($expected, $fields);
    }

    #[Test]
    public function convert_types() {
        $data = $this->data;
        $data["website"]["rating"] = "4.5";
        $validator = new Validator(UserInfos::class, $data);
        $this->assertEquals($this->user, $validator->parse());
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

    #[Test]
    public function filter_validate_email() {
        $data = $this->data;
        $data["email"] = "john";
        $validator = new Validator(UserInfos::class, $data);
        $this->assertNull($validator->parse());
    }

    #[Test]
    public function filter_validate_ip() {
        $data = $this->data;
        $data["website"]["ip"] = "1.1.1";
        $validator = new Validator(UserInfos::class, $data);
        $this->assertNull($validator->parse());
    }

    #[Test]
    public function filter_validate_url() {
        $data = $this->data;
        $data["website"]["url"] = "not an url";
        $validator = new Validator(UserInfos::class, $data);
        $this->assertNull($validator->parse());
    }

    #[Test]
    public function filter_validate_domain() {
        $data = $this->data;
        $data["website"]["domain"] = "not a domain";
        $validator = new Validator(UserInfos::class, $data);
        $this->assertNull($validator->parse());
    }

    #[Test]
    public function validate_min() {
        $data = $this->data;
        $data["website"]["rating"] = -1;
        $validator = new Validator(UserInfos::class, $data);
        $this->assertNull($validator->parse());
    }

    #[Test]
    public function validate_max() {
        $data = $this->data;
        $data["website"]["rating"] = 9999;
        $validator = new Validator(UserInfos::class, $data);
        $this->assertNull($validator->parse());
    }

    #[Test]
    public function validate_length_min() {
        $data = $this->data;
        $data["name"] = "J";
        $validator = new Validator(UserInfos::class, $data);
        $this->assertNull($validator->parse());
    }

    #[Test]
    public function validate_length_max() {
        $data = $this->data;
        $data["name"] = "Way too long of a test name";
        $validator = new Validator(UserInfos::class, $data);
        $this->assertNull($validator->parse());
    }

    #[Test]
    public function validate_count_min() {
        $data = $this->data;
        $data["website"]["tags"] = [];
        $validator = new Validator(UserInfos::class, $data);
        $this->assertNull($validator->parse());
    }

    #[Test]
    public function validate_count_max() {
        $data = $this->data;
        $data["website"]["tags"] = ["portfolio", "commercial", "coding", "multi-media"];
        $validator = new Validator(UserInfos::class, $data);
        $this->assertNull($validator->parse());
    }

    #[Test]
    public function validate_date() {
        $data = $this->data;
        $data["birthday"] = "Bad format";
        $validator = new Validator(UserInfos::class, $data);
        $this->assertNull($validator->parse());
    }

    #[Test]
    public function mutate_sanitize() {
        $data = $this->data;
        $data["name"] = "<script></script>";
        $validator = new Validator(UserInfos::class, $data);
        $user = $validator->parse();
        $this->assertSame("&lt;script&gt;&lt;/script&gt;", $user->name);
    }

    #[Test]
    public function mutate_sanitize_array() {
        $data = $this->data;
        $data["website"]["tags"] = ["<script></script>"];
        $validator = new Validator(UserInfos::class, $data);
        $user = $validator->parse();
        $this->assertSame(["&lt;script&gt;&lt;/script&gt;"], $user->website->tags);
    }

    #[Test]
    public function array_convert_types() {
        $data = $this->data;
        $data["website"]["tags"] = [1];
        $validator = new Validator(UserInfos::class, $data);
        $user = $validator->parse();
        $this->assertSame(["1"], $user->website->tags);
    }

    #[Test]
    public function array_convert_types_object() {
        $data = $this->data;
        $data["friends"] = ["csd"];
        $validator = new Validator(UserInfos::class, $data);
        $this->assertNull($validator->parse());
    }
}