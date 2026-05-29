<?php
namespace PHPTools\ORM;

use Override;
use PDO;
use PHPTools\Core\Collection;
use PHPTools\Core\ICollection;
use PHPTools\ORM\Queries\InsertQuery;
use PHPTools\ORM\Queries\SelectQuery;
use PHPTools\ORM\Queries\SQLCondition;
use PHPTools\ORM\Queries\UpdateQuery;
use ReflectionClass;
use ReflectionProperty;
use TypeError;

/**
 * @template T
 */
class DBCollection extends Collection {
    public const string TIMESTAMP_FORMAT = "Y-m-d H:i:s";

    private DBContext $ctx;
    public SQLBuilder $builder;
    private ?array $_items = null;

    /** @var T[] */
    #[Override]
    public array $items {
        get {
            if ($this->_items === null)
                return $this->fetch($this->builder->buildQuery(), $this->builder->params);
            return $this->_items;
        }
        set => $this->_items = $value;
    }

    public string $table { get => $this->builder->table; }
    
    public function __construct(string $modelClass, DBContext $ctx, ?SQLBuilder $builder = null) {
        $this->ctx = $ctx;
        parent::__construct($modelClass);
        if ($builder === null)
            $builder = new SQLBuilder($modelClass);
        $this->builder = $builder;
    }

    #[Override]
    public function count(): int {
        $collection = clone $this;
        $collection->builder->query->columns = ["COUNT(*)"];
        $sttmt = $this->run($collection->builder);
        return $sttmt->fetch(PDO::FETCH_ASSOC)["COUNT(*)"];
    }

    #[Override]
    public function take(int $limit, int $offset = 0): ICollection {
        $collection = clone $this;
        $query = $collection->builder->query;
        if ($query instanceof SelectQuery) {
            $query->limit = $limit;
            $query->offset = $offset;
        }
        return $collection;
    }

    /**
     * @param T ...$items
     * @return void
     */
    #[Override]
    public function add(mixed ...$items) {
        $params = [];
        foreach ($items as $key => $item) {
            if (!$this->isAssignable($item, $this->itemsType))
                throw new TypeError("The item at the index of $key isn't of type $this->itemsType");
            foreach ($this->builder->columnsToInsert as $propName => $column) {
                $reflection = new ReflectionProperty($this->itemsType, $propName);
                $params[] = $reflection->getValue($item);
            }
        }
        $collection = clone $this;
        $collection->builder->query = new InsertQuery($this->table, $this->builder->columnsToInsert, count($items));
        $builder = $collection->builder;
        $builder->params = array_merge($params, $builder->params);
        $this->run($builder);
    }

    /**
     * @param T ...$items
     * @return void
     */
    public function update(mixed ...$items) {
        $params = [];
        $collection = clone $this;
        $builder = $collection->builder;
        foreach ($items as $index => $item) {
            if (!$this->isAssignable($item, $this->itemsType))
                throw new TypeError("The item at the index of $index isn't of type $this->itemsType");
            $conditions = [];
            foreach ($builder->columnsToUpdate as $propName => $column) {
                $reflection = new ReflectionProperty($this->itemsType, $propName);
                $params[] = $reflection->getValue($item);
            }
            foreach ($builder->primaryKeys as $propName => $key) {
                $reflection = new ReflectionProperty($this->itemsType, $propName);
                $conditions[] = new SQLCondition("`$this->table`.`$key` = ?");
                $params[] = $reflection->getValue($item);
            }
            $builder->params = $params;
            $builder->query = new UpdateQuery($this->table, $builder->columnsToUpdate, $conditions);
            $this->run($builder);
        }
        return $collection;
    }

    /**
     * @param callable(T): bool $predicate
     * @return ICollection<T>
     */
    #[Override]
    public function where(callable $predicate): ICollection {
        $collection = clone $this;
        $result = $collection->builder->parseWhere($predicate);
        if($result)
            return $collection;
        return parent::where($predicate);
    }

    #[Override]
    public function select(callable $selector): ICollection {
        $collection = clone $this;
        $result = $collection->builder->parseSelect($selector);
        if($result) {
            $sttmt = $this->ctx->run($collection->builder->buildQuery(), $collection->builder->params);
            $selectMeta = [
                "columns" => $result->select,
                "isAssoc" => $result->isAssoc,
                "count"   => count($result->select),
            ];
            $items = array_map(fn($r) => $this->hydrateSelectRow($r, $selectMeta), $sttmt->fetchAll(PDO::FETCH_ASSOC));
            $columnNames = $result->selectColumnNames;
            $propName = array_search($columnNames[0], $collection->builder->columns);
            $type = count($columnNames) > 1 || $result->isAssoc ? "array" : $this->getPropertyType($propName);
            $collection = new Collection($type, true);
            $collection->add(...$items);
            return $collection;
        }
        return parent::where($selector);
    }

    private function getPropertyType(string $propName): string {
        $refProp = new ReflectionClass($this->itemsType)->getProperty($propName);
        return $refProp->getType()->getName() ?? $refProp->getType()->getTypes()[0]->getName();
    }

    private function hydrateSelectRow(array $row, array $meta) {

        // Single column, no alias → return scalar
        if ($meta["count"] === 1 && !$meta["isAssoc"]) {
            return array_values($row)[0];
        }

        // Multiple columns, no alias → numeric array
        if (!$meta["isAssoc"]) {
            return array_values($row);
        }

        // Associative → return as-is
        return $row;
    }


    /**
     * @return ?T
     */
    #[Override]
    public function first(): mixed {
        return $this->take(1, 0)->toArray()[0] ?? null;
    }

    /**
     * @return T[]
     */
    #[Override]
    public function toArray(): array {
        return parent::toArray();
    }

     /**
     * @param string $sql
     * @param array $params
     * @return T[]
     */
    public function fetch(string $sql, array $params = []): array {
        $data = $this->ctx->run($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
        $refClass = new ReflectionClass($this->itemsType);
        $result = [];
        foreach ($data as $entity) {
            $obj = $refClass->newInstance();
            foreach ($this->builder->columns as $propName => $column) {
                $prop = $refClass->getProperty($propName);
                $prop->setValue($obj, $entity[$column]);
            }
            $result[] = $obj;
        }
        return $result;
    }

    public function run(SQLBuilder $builder) {
        return $this->ctx->run($builder->buildQuery(), $builder->params);
    }

    public function __clone() {
        $this->builder = clone $this->builder;
    }
}