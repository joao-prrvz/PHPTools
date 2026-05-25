<?php
namespace PHPTools\ORM\Parsers;

use Exception;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar;
use PhpParser\ParserFactory;
use PHPTools\ORM\Queries\SQLCondition;
use ReflectionFunction;
use Throwable;

class WhereParser implements IParser {
    private string $table;
    public array $conditions = [];
    public array $params = [];
    private array $columns;
    private ReflectionFunction $ref;

    public function __construct(string $table, array $columns) {
        $this->table = $table;
        $this->columns = $columns;
    }

    public function parse(callable $predicate) {
        try {
            $this->ref = new ReflectionFunction($predicate);

            // Parse the entire file
            $source = file_get_contents($this->ref->getFileName());
            $parser = (new ParserFactory())->createForNewestSupportedVersion();
            $ast = $parser->parse($source);

            // Find the arrow function node by matching line numbers
            $arrowFn = $this->findArrowFunctionNode(
                $ast,
                $this->ref->getStartLine(),
                $this->ref->getEndLine()
            );

            if (!$arrowFn) {
                throw new Exception("Could not locate arrow function in AST");
            }

            // Parse the arrow function expression
            $result = $this->parseExpression($arrowFn->expr);

            $this->conditions[] = new SQLCondition($result['sql']);
            $this->params = array_merge($this->params, $result['params']);

            return true;

        } catch (Throwable $e) {
            trigger_error("WhereParser: {$e->getMessage()}", E_USER_WARNING);
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

    private function parseExpression(Node $node): array {
        // Boolean AND
        if ($node instanceof Expr\BinaryOp\BooleanAnd) {
            return $this->combine($node->left, $node->right, 'AND');
        }

        // Boolean OR
        if ($node instanceof Expr\BinaryOp\BooleanOr) {
            return $this->combine($node->left, $node->right, 'OR');
        }

        // NOT
        if ($node instanceof Expr\BooleanNot) {
            $inner = $this->parseExpression($node->expr);
            return [
                'sql' => "NOT ({$inner['sql']})",
                'params' => $inner['params']
            ];
        }

        // Comparison operators
        if ($node instanceof Expr\BinaryOp) {
            return $this->parseComparison($node);
        }

        // LIKE / NOT LIKE via string functions
        if ($node instanceof Expr\FuncCall) {
            return $this->parseStringFunction($node);
        }

        // Property fetch
        if ($node instanceof Expr\PropertyFetch) {
            $prop = $node->name->name;
            $column = $this->columns[$prop] ?? throw new Exception("Unknown property: $prop");
            return ['sql' => "`{$this->table}`.`$column`", 'params' => []];
        }

        // Scalars
        if ($node instanceof Scalar) {
            return ['sql' => '?', 'params' => [$node->value]];
        }

        // Const fetch (true, false, null)
        if ($node instanceof Expr\ConstFetch) {
            $name = strtolower($node->name->toString());
            return match ($name) {
                'null' => ['sql' => 'NULL', 'params' => []],
                'true' => ['sql' => '?', 'params' => [true]],
                'false' => ['sql' => '?', 'params' => [false]],
                default => throw new Exception("Unknown constant: $name")
            };
        }

        // Variables captured from closure
        if ($node instanceof Expr\Variable) {
            $vars = $this->ref->getStaticVariables();
            $value = $vars[$node->name] ?? null;

            if ($value === null) {
                return ['sql' => 'NULL', 'params' => []];
            }

            return ['sql' => '?', 'params' => [$value]];
        }

        throw new Exception("Unsupported expression type: " . get_class($node));
    }

    private function combine(Node $left, Node $right, string $op): array {
        $l = $this->parseExpression($left);
        $r = $this->parseExpression($right);

        return [
            'sql' => "({$l['sql']} $op {$r['sql']})",
            'params' => array_merge($l['params'], $r['params'])
        ];
    }

    private function parseComparison(Expr\BinaryOp $node): array {
        $op = $this->mapOperator($node);

        $left = $this->parseExpression($node->left);
        $right = $this->parseExpression($node->right);

        // NULL special case: use IS / IS NOT but still bind ?
        if ($right['sql'] === 'NULL') {
            return [
                'sql' => "{$left['sql']} " . ($op === '=' ? 'IS ?' : 'IS NOT ?'),
                'params' => [null]
            ];
        }

        return [
            'sql' => "{$left['sql']} $op ?",
            'params' => [$right['params'][0]]
        ];
    }

    private function mapOperator(Expr\BinaryOp $node): string {
        return match (true) {
            $node instanceof Expr\BinaryOp\Equal => '=',
            $node instanceof Expr\BinaryOp\Identical => '=',
            $node instanceof Expr\BinaryOp\NotEqual => '<>',
            $node instanceof Expr\BinaryOp\NotIdentical => '<>',
            $node instanceof Expr\BinaryOp\Greater => '>',
            $node instanceof Expr\BinaryOp\GreaterOrEqual => '>=',
            $node instanceof Expr\BinaryOp\Smaller => '<',
            $node instanceof Expr\BinaryOp\SmallerOrEqual => '<=',
            default => throw new Exception("Unsupported operator: " . get_class($node))
        };
    }

    private function parseStringFunction(Expr\FuncCall $node): array {
        if (!($node->name instanceof Node\Name)) {
            throw new Exception("Unsupported function call");
        }

        $fn = strtolower($node->name->toString());
        $args = $node->args;

        if (count($args) !== 2) {
            throw new Exception("String functions require 2 arguments");
        }

        $left = $this->parseExpression($args[0]->value);
        $right = $this->parseExpression($args[1]->value);

        $value = $right['params'][0] ?? null;

        return match ($fn) {
            'str_contains' => [
                'sql' => "{$left['sql']} LIKE ?",
                'params' => ["%$value%"]
            ],
            'str_starts_with' => [
                'sql' => "{$left['sql']} LIKE ?",
                'params' => ["$value%"]
            ],
            'str_ends_with' => [
                'sql' => "{$left['sql']} LIKE ?",
                'params' => ["%$value"]
            ],
            default => throw new Exception("Unsupported string function: $fn")
        };
    }
}