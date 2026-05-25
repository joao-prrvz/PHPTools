<?php
namespace PHPTools\ORM;

use Override;
use PDO;
use PHPTools\Core\Collection;
use PHPTools\Core\ICollection;
use ReflectionClass;

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
    public function add(mixed ...$items) {
        //Insert into DB
        parent::add(... $items);
    }

    /**
     * @param callable(T): bool $predicate
     * @return ICollection<T>
     */
    #[Override]
    public function where(callable $predicate): ICollection {
        $this->builder->parseWhere($predicate);
        return parent::where($predicate);
    }

    /**
     * @return ?T
     */
    #[Override]
    public function first(): mixed {
        return parent::first();
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
}