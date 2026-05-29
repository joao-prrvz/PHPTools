<?php
namespace PHPTools\ORM;

use PHPTools\ORM\Attributes as DB;
use PHPTools\ORM\Queries\SelectQuery;

use PHPTools\ORM\Parsers\SelectParser;
use PHPTools\ORM\Queries\IQuery;
use PHPTools\ORM\Parsers\WhereParser;
use ReflectionClass;
use ReflectionProperty;

/** @template T */
class SQLBuilder {
    /** @var class-string<T> $modelClass */
    public readonly string $modelClass;
    public IQuery $query;

    /** @var ReflectionClass<T> */
    private ReflectionClass $ref;

    public array $params = [];

    public string $table {
        get {
            $attrs = $this->ref->getAttributes(DB\Table::class);
            if (!empty($attrs)) 
                return $attrs[0]->newInstance()->name;
            $path = explode("\\", $this->modelClass);
            return array_pop($path);
        }
    }

    public array $columns {
        get {
            $columns = [];
            $refProps = $this->getNotIgnoredProperties();
            foreach ($refProps as $refProp)
                $columns[$refProp->name] = $this->getPropertyColumn($refProp);
            return $columns;
        }
    }

    public array $columnsToInsert {
        get {
            $columns = [];
            $refProps = $this->getNotIgnoredProperties();
            foreach ($refProps as $refProp) {
                if ($this->hasBlock($refProp, DB\Block::INSERT))
                    continue;
                $columns[$refProp->name] = $this->getPropertyColumn($refProp);
            }
            return $columns;
        }
    }

    public array $columnsToUpdate {
        get {
            $columns = [];
            $refProps = $this->getNotIgnoredProperties();
            foreach ($refProps as $refProp) {
                if ($this->hasBlock($refProp, DB\Block::UPDATE))
                    continue;
                $columns[$refProp->name] = $this->getPropertyColumn($refProp);
            }
            return $columns;
        }
    }

    /** @var string[] */
    public array $primaryKeys {
        get {
            $keys = [];
            $refProps = $this->getNotIgnoredProperties();
            foreach ($refProps as $refProp) {
                if (count($refProp->getAttributes(DB\PrimaryKey::class)) > 0)
                    $keys[$refProp->name] = $this->getPropertyColumn($refProp);
            }
            if (count($keys) < 1) {
                foreach ($refProps as $refProp) {
                    if (strtolower($refProp->name) == "id" || strtolower($this->getPropertyColumn($refProp)) == "id")
                        $keys[$refProp->name] = $this->getPropertyColumn($refProp);
                }
            }
            return $keys;
        }
    }

    /**
     * @param class-string<T> $modelClass
     */
    public function __construct(string $modelClass) {
        $this->modelClass = $modelClass;
        $this->ref = new ReflectionClass($this->modelClass);
        $this->query = new SelectQuery($this->table, $this->formatColumns());
    }

    public function buildQuery(): string {
        return "$this->query";
    }

    private function hasBlock(ReflectionProperty $refProp, int $value): bool {
        $attr = $refProp->getAttributes(DB\Block::class)[0] ?? null;
        if ($attr === null)
            return false;
        $values = $attr->newInstance()->values;
        return in_array(DB\Block::ALL, $values) || in_array($value, $values);
    }

    private function getPropertyColumn(ReflectionProperty $refProp): string {
        $attr = $refProp->getAttributes(DB\Column::class)[0] ?? null;
        if ($attr === null)
            return $refProp->name;
        return $attr->newInstance()->name;
    }

    private function getNotIgnoredProperties() {
        $refProps = $this->ref->getProperties();
        $props = [];
        foreach ($refProps as $refProp) {
            if (!isset($refProp->getAttributes(DB\Ignore::class)[0]))
                $props[] = $refProp;
        }
        return $props;
    }

    public function parseWhere(callable $predicate): bool {
        $parser = new WhereParser($this->table, $this->columns);
        $result = $parser->parse($predicate);
        if (!$result)
            return false;
        if ($this->query instanceof SelectQuery) {
            $this->query->conditions = array_merge($parser->conditions, $this->query->conditions);
            $this->params = array_merge($parser->params, $this->params);
            return true;
        }
        $this->query = new SelectQuery($this->table, $this->formatColumns(), $parser->conditions);
        $this->params = $parser->params;
        return true;
    }

    private function formatColumns() {
        return array_map(fn(string $c) => "`{$this->table}`.`{$c}`", $this->columns);
    }

    public function parseSelect(callable $selector): SelectParser|false {
        $parser = new SelectParser($this->table, $this->columns);
        $result = $parser->parse($selector);
        if (!$result)
            return false;
        if ($this->query instanceof SelectQuery) {
            $this->query->columns = $parser->select;
            return $parser;
        }
        $this->query = new SelectQuery($this->table, $parser->select);
        return $parser;
    }

    public function __clone() {
        $this->query = clone $this->query;
    }
}   