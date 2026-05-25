<?php
namespace PHPTools\ORM;

use PhpParser\Node\Expr\BinaryOp as OP;
use PhpParser\ParserFactory;
use PHPTools\ORM\Attributes as DB;
use PHPTools\ORM\Queries\SelectQuery;

use Exception;
use PHPTools\ORM\Queries\SQLCondition;
use ReflectionClass;
use ReflectionFunction;
use ReflectionProperty;
use Throwable;

/** @template T */
class SQLBuilder {
    /** @var class-string<T> $modelClass */
    public readonly string $modelClass;
    public SelectQuery $select;

    /** @var ReflectionClass<T> */
    private ReflectionClass $ref;

    public SQLBuilderMode $mode = SQLBuilderMode::SELECT;
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
        $this->select = new SelectQuery($this->table);
        $this->select->columns = array_values($this->columns);
    }

    public function buildQuery(): string {
        switch ($this->mode) {
            case SQLBuilderMode::SELECT:
                return $this->buildSelectQuery();
        }
        throw new Exception("You must chose a SQLBuilderMode");
    }

    private function buildSelectQuery(): string {
        return $this->select->__toString();
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
        $ref = new ReflectionFunction($predicate);
        $source = file($ref->getFileName());
        $code = implode('', array_slice($source, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $paramValue = null;
        try {
            $ast = $parser->parse("<?php $code ?>");

            // Robustly find the ArrowFunction node regardless of call context
            $arrowFn = $this->findNode($ast, \PhpParser\Node\Expr\ArrowFunction::class);
            if ($arrowFn === null)
                throw new Exception("Could not locate arrow function in AST");

            $comparaisonExpr = $arrowFn->expr;
            $propName = $comparaisonExpr->left->name->name;
            $sign = $this->getSignByComparaisonExprType($comparaisonExpr);
            $columnName = $this->columns[$propName] ?? null;
            if ($columnName === null)
                throw new Exception("No column found for the property $propName");

            $rightNode = $comparaisonExpr->right;
            $isNullValue = false;

            if ($rightNode instanceof \PhpParser\Node\Scalar) {
                $isNullValue = false;
                $paramValue = $rightNode->value;
            }
            elseif ($rightNode instanceof \PhpParser\Node\Expr\ConstFetch) {
                $constName = strtolower($rightNode->name->toString());
                $isNullValue = ($constName === 'null');

                $paramValue = match($constName) {
                    'null'  => null,
                    'true'  => true,
                    'false' => false,
                    default => null,
                };
            }
            elseif ($rightNode instanceof \PhpParser\Node\Expr\Variable) {
                $usedVars = $ref->getStaticVariables();
                $varName = $rightNode->name;
                $isNullValue = array_key_exists($varName, $usedVars) && $usedVars[$varName] === null;
                $paramValue = $usedVars[$varName] ?? null;
            }

            if ($isNullValue) {
                $sign = match($sign) {
                    '==' => 'IS',
                    '!=' => 'IS NOT',
                    default => throw new Exception("Cannot compare null with operator $sign"),
                };
            }

            $this->select->conditions[] = new SQLCondition("`$this->table`.`$columnName` $sign ?");
            $this->params[] = $paramValue;

        } catch (Throwable $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return false;
        }
        return true;
    }

    /**
     * Recursively find the first node of a given class in an AST node array.
     */
    private function findNode(array $nodes, string $class): ?\PhpParser\Node {
        foreach ($nodes as $node) {
            if ($node instanceof $class) return $node;
            if (!($node instanceof \PhpParser\Node)) continue;
            foreach ($node->getSubNodeNames() as $subName) {
                $sub = $node->$subName;
                $children = is_array($sub) ? $sub : [$sub];
                $found = $this->findNode($children, $class);
                if ($found !== null) return $found;
            }
        }
        return null;
    }

    private function getSignByComparaisonExprType(mixed $comparaisonExpr): string {
        if ($comparaisonExpr instanceof OP\Equal || $comparaisonExpr instanceof OP\Identical)
            return "==";
        if ($comparaisonExpr instanceof OP\NotEqual || $comparaisonExpr instanceof OP\NotIdentical)
            return "!=";
        throw new Exception("Unsupported comparison operator: " . get_class($comparaisonExpr));
    }
}   