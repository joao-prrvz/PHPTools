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
        $collection = $this->clone();
        $collection->builder->query = new InsertQuery($this->table, $this->builder->columnsToInsert, count($this->items));
        $collection->builder->params[] = $params;
        $builder = $collection->builder;
        $this->run($builder);
    }

    /**
     * @param T ...$items
     * @return void
     */
    public function update(mixed ...$items) {
        $params = [];
        $collection = $this->clone();
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

    public function clone(): DBCollection {
        return new DBCollection($this->itemsType, $this->ctx, $this->builder->clone());
    }

    /**
     * @param callable(T): bool $predicate
     * @return ICollection<T>
     */
    #[Override]
    public function where(callable $predicate): ICollection {
        $collection = $this->clone();
        if($collection->builder->parseWhere($predicate))
            return $collection;
        return parent::where($predicate);
    }

    /**
     * @return ?T
     */
    #[Override]
    public function first(): mixed {
        $collection = $this->clone();
        $query = $collection->builder->query;
        if ($query instanceof SelectQuery) {
            $query->limit = 1;
            $query->offset = 0;
        }
        return $this->fetch($collection->builder->buildQuery(), $collection->builder->params)[0] ?? null;
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
}