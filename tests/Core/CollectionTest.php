<?php
namespace PHPTools\Tests\Core;

use PHPTools\Core\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TypeError;

class CollectionTest extends TestCase {

    #[Test]
    public function collection_add() {
        $collection = new Collection("int");
        $result = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $collection->add(...$result);
        $this->assertSame($result, $collection->toArray());
    }

    #[Test]
    public function collection_add_wrong_type() {
        $this->expectException(TypeError::class);
        $collection = new Collection("int");
        $collection->add(1, 2, 3, 4, "5", 6, 7, 8, 9, 10);
    }

    #[Test]
    public function collection_where() {
        $collection = new Collection("int");
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
        $result = [2, 4, 6, 8, 10];
        $pair = $collection->where(fn($n) => $n % 2 === 0);
        $this->assertSame($result, $pair->toArray());
    }

    #[Test]
    public function collection_select() {
        $collection = new Collection("int");
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
        $result = [2, 4, 6, 8, 10, 12, 14, 16, 18, 20];
        $double = $collection->select(fn($n) => $n * 2);
        $this->assertSame($result, $double->toArray());
    }

    #[Test]
    public function collection_order_by_int() {
        $collection = new Collection("int");
        $collection->add(10, 9, 8, 7, 6, 5, 4, 3, 2, 1);
        $result = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $sorted = $collection->orderBy(fn($n) => $n);
        $this->assertSame($result, $sorted->toArray());
    }

    #[Test]
    public function collection_first() {
        $collection = new Collection("int");
        $collection->add(10, 9, 8, 7, 6, 5, 4, 3, 2, 1);
        $this->assertEquals(10, $collection->first());
    }

    #[Test]
    public function collection_last() {
        $collection = new Collection("int");
        $collection->add(10, 9, 8, 7, 6, 5, 4, 3, 2, 1);
        $this->assertEquals(1, $collection->last());
    }

    #[Test]
    public function collection_last_null() {
        $collection = new Collection("int");
        $this->assertEquals(null, $collection->last());
    }

    #[Test]
    public function collection_first_null() {
        $collection = new Collection("int");
        $this->assertEquals(null, $collection->first());
    }

    #[Test]
    public function collection_order_by_property_string() {
        /** @var Collection<array{name: string, price: float}> */
        $collection = new Collection("array");
        $result = [
            ["name" => "bread", "price" => 3.99],
            ["name" => "hot-dogs", "price" => 0.59],
            ["name" => "milk", "price" => 5.99],
            ["name" => "soda", "price" => 1],
        ];
        $items = [... $result];
        shuffle($items);
        $collection->add(... $items);
        $sorted = $collection->orderBy(fn($n) => $n["name"]);
        $this->assertSame($result, $sorted->toArray());
    }

    #[Test]
    public function collection_order_by_property_float() {
        /** @var Collection<array{name: string, price: float}> */
        $collection = new Collection("array");
        $result = [
            ["name" => "hot-dogs", "price" => 0.59],
            ["name" => "soda", "price" => 1],
            ["name" => "bread", "price" => 3.99],
            ["name" => "milk", "price" => 5.99],
        ];
        $items = [... $result];
        shuffle($items);
        $collection->add(... $items);
        $sorted = $collection->orderBy(fn($n) => $n["price"]);
        $this->assertSame($result, $sorted->toArray());
    }

    #[Test]
    public function collection_contains() {
        /** @var Collection<array{name: string, price: float}> */
        $collection = new Collection("array");
        $result = [
            ["name" => "hot-dogs", "price" => 0.59],
            ["name" => "soda", "price" => 1],
            ["name" => "bread", "price" => 3.99],
            ["name" => "milk", "price" => 5.99],
        ];
        $items = [...$result];
        shuffle($items);
        $collection->add(["name" => "hot-dogs", "price" => 0.59]);
        $this->assertTrue($collection->contains($result[0]));
    }

    #[Test]
    public function collection_take() {
        $collection = new Collection("int");
        $collection->add(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
        $result = $collection->take(3, 1);
        $this->assertSame([2, 3, 4], $result);
    }

    
}