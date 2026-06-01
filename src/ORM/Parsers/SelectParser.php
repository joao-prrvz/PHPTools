<?php
namespace PHPTools\ORM\Parsers;

use Exception;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\ParserFactory;
use ReflectionFunction;
use Throwable;

class SelectParser implements IParser {

    private string $table;
    private array $columns;
    private ReflectionFunction $ref;
    private string $arrowParam;
    public bool $isAssoc = false;

    public array $select = [];
    public array $selectColumnNames = [];

    public function __construct(string $table, array $columns) {
        $this->table = $table;
        $this->columns = $columns;
    }

    public function parse(callable $predicate) {
        try {
            $this->ref = new ReflectionFunction($predicate);

            $source = file_get_contents($this->ref->getFileName());
            $parser = (new ParserFactory())->createForNewestSupportedVersion();
            $ast = $parser->parse($source);

            $arrowFn = $this->findArrowFunctionNode(
                $ast,
                $this->ref->getStartLine(),
                $this->ref->getEndLine()
            );

            if (!$arrowFn) {
                throw new Exception("Could not locate arrow function in AST");
            }

            // Store parameter name ($u)
            $this->arrowParam = $arrowFn->params[0]->var->name;

            // Parse the return expression
            $this->select = $this->parseSelectExpression($arrowFn->expr);

            return true;

        } catch (Throwable $e) {
            trigger_error("SelectParser failed: " . $e->getMessage(), E_USER_NOTICE);
            return false;
        }
    }

    private function findArrowFunctionNode(array $nodes, int $start, int $end): ?Expr\ArrowFunction {
        foreach ($nodes as $node) {
            if (!($node instanceof Node)) continue;

            if ($node instanceof Expr\ArrowFunction &&
                $node->getStartLine() === $start &&
                $node->getEndLine() === $end) {
                return $node;
            }

            foreach ($node->getSubNodeNames() as $subName) {
                $sub = $node->$subName;
                $children = is_array($sub) ? $sub : [$sub];
                $found = $this->findArrowFunctionNode($children, $start, $end);
                if ($found) return $found;
            }
        }
        return null;
    }

    private function parseSelectExpression(Node $node): array {

        // Single column: fn($u) => $u->email
        if ($node instanceof Expr\PropertyFetch) {
            $col = $this->parseColumn($node);
            $this->selectColumnNames[] = $col;
            return ["`{$this->table}`.`{$col}`"];
        }

        // Array: fn($u) => [$u->email, $u->id]
        // Or associative: fn($u) => ["E-mail" => $u->email]
        if ($node instanceof Expr\Array_) {
            $cols = [];

            foreach ($node->items as $item) {
                $value = $item->value;

                if (!($value instanceof Expr\PropertyFetch)) {
                    throw new Exception("SELECT only supports direct property fetches");
                }

                $col = $this->parseColumn($value);
                $this->selectColumnNames[] = $col;
                $formatedCol = "`{$this->table}`.`$col`";
                if ($item->key !== null) {
                    $this->isAssoc = true;
                    $alias = $item->key->value;
                    $formatedCol .= " AS `$alias`";
                } 
                $cols[] = $formatedCol;
            }

            return $cols;
        }

        throw new Exception("Unsupported SELECT expression: " . get_class($node));
    }

    private function parseColumn(Expr\PropertyFetch $node): string {
        if (!($node->var instanceof Expr\Variable) ||
            $node->var->name !== $this->arrowParam) {
            throw new Exception("SELECT only supports direct entity properties");
        }

        $prop = $node->name->name;
        $column = $this->columns[$prop] ?? throw new Exception("Unknown property: $prop");

        return $column;
    }
}
