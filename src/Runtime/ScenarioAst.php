<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Stateforge\Scenario\Ai\Runtime\Exception\ScenarioAstException;
use function array_pop;
use function count;
use function implode;

final class ScenarioAst
{
    /**
     * @var array<Node>
     */
    private array $ast;

    /**
     * @param array<Stmt> $ast
     */
    public function __construct(array $ast)
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        $this->ast = $traverser->traverse($ast);
    }

    public function getClass(): Class_
    {
        $finder = new NodeFinder();
        $classes = $finder->findInstanceOf(
            $this->ast,
            Class_::class,
        );

        if (count($classes) !== 1) {
            throw new ScenarioAstException();
        }

        return $classes[0];
    }

    public function getClassName(): string
    {
        $name = $this->getClass()->name;
        if ($name === null) {
            throw new ScenarioAstException();
        }

        return $name->toString();
    }

    public function getNamespace(): string
    {
        $name = $this->getClass()->namespacedName;
        if ($name === null) {
            throw new ScenarioAstException();
        }

        $parts = $name->getParts();
        array_pop($parts);

        return implode('\\', $parts);
    }
}
