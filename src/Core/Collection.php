<?php
namespace PHPTools\Core;

use Countable;
use Iterator;
use TypeError;

/**
 * @template T
 */
class Collection implements Iterator, ICollection, Countable {
    public int $length { get => $this->count(); }

    private $position = 0;

    /** @var T[] */
    protected array $items = [];
    /** @param class-string<T> $itemsType */
    protected string $itemsType; 
    
    /**
     * @param class-string<T> $itemsType
     */
    public function __construct(string $itemsType, public bool $nullable = false) {
        $this->itemsType = $itemsType;
    }
    
    public function rewind(): void {
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
    public function current() {
        return $this->items[$this->position];
    }

    #[\ReturnTypeWillChange]
    public function key(): int {
        return $this->position;
    }

    public function next(): void {
        ++$this->position;
    }

    public function valid(): bool {
        return isset($this->items[$this->position]);
    }

    public function count(): int {
        return count($this->items);
    }

    /**
     * 
     * @param T ...$items
     * @throws TypeError When an item that's not of the type defined at the declaration of the collection
     */
    public function add(mixed ...$items)  {
        foreach ($items as $key => $item) {
            if (!$this->isAssignable($item, $this->itemsType))
                throw new TypeError("The item at the index of $key isn't of type $this->itemsType");
            $this->items[] = $item;
        }
    }

    /**
     * @param T ...$items
     */
    public function remove(mixed ...$items) {
        foreach ($items as $item) {
            $index = array_search($item, $this->items, strict: true);
            if ($index === false)
                continue;
            $this->removeAt($index);
        }
    }

    public function removeAt(int $index) {
        array_splice($this->items, $index, 1);
    }

    /**
     * @param callable(T): bool $predicate
     * @return ICollection<T>
     */
    public function where(callable $predicate): ICollection {

        $collection = new Collection($this->itemsType);
        foreach ($this->items as $item) {
            if ($predicate($item))
                $collection->add($item);
        }
        return $collection;
    }

    /**
     * @template TResult
     * @param callable(T): TResult  $selector
     * @return ICollection<TResult>
     */
    public function select(callable $selector): ICollection {
        $array = [];
        $type = "null";
        foreach ($this->items as $key => $value) {
            $array[] = $selector($value);
            if ($key === 0)
                $type = get_debug_type($array[$key]);
        }
        $collection = new Collection($type);
        $collection->add(...$array);
        return $collection;
    }

    /**
     * Creates
     * 
     * @template TComparer
     * @param callable(T): TComparer $comparer
     * @return ICollection<T>
     */
    public function orderBy(callable $comparer): ICollection {
        $collection = new Collection($this->itemsType);
        $collection->add(... Sort::merge($this->items, $comparer));
        return $collection;
    }


    public function contains(mixed $value): bool {
        return in_array($value, $this->items, true);
    }

    /**
     * Retrieves the last element of the collection but if the collection is empty it will return null
     *
     * @return ?T
     */
    public function last(): mixed {
        if ($this->length < 1)
            return null;
        return $this->items[$this->length - 1];
    }

    /**
     * Retrieves the first element of the collection but if the collection is empty it will return null
     *
     * @return ?T
     */
    public function first(): mixed {
        if ($this->length < 1)
            return null;
        return $this->items[0];
    }

    public function take(int $limit, int $offset = 0): array {
        $items = [... $this->items];
        return array_splice($items, $offset, $limit);
    }

    protected function isAssignable(mixed $value, string $targetType): bool {
        if (class_exists($targetType) && $value instanceof $targetType)
            return true;
        if (is_null($value) && $this->nullable)
            return true;
        $compatibleTypes = [
            "int" => ["int"],
            "float" => ["int", "float"],
            "string" => ["string"],
            "bool" => ["bool"],
            "array" => ["array"],
        ];
        $valueType = get_debug_type($value);
        return in_array($valueType, $compatibleTypes[$targetType] ?? []);
    }

    /**
     * @return T[]
     */
    public function toArray(): array {
        return [... $this->items];
    }

}