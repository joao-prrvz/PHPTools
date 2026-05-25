<?php
namespace PHPTools\ORM\Parsers;

use Exception;
use PhpParser\Node\Expr\BinaryOp as OP;
use PhpParser\ParserFactory;
use PHPTools\ORM\Queries\SQLCondition;
use ReflectionFunction;
use Throwable;

class WhereParser implements IParser {
    private string $table;
    /** @var SQLCondition[] */
    public array $conditions = [];
    public array $params = [];
    private array $columns;
    
    public function __construct(string $table, array $columns) {
        $this->table = $table;
        $this->columns = $columns;
    }

    public function parse(callable $predicate) {
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
            $this->conditions[] = new SQLCondition("`$this->table`.`$columnName` $sign ?");
            $this->params[] = $paramValue;

        } catch (Throwable $error) {
            trigger_error("WhereParser: {$error->getMessage()}", E_USER_WARNING);
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