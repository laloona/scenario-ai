<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UseItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Exception\ScenarioAstException;
use Stateforge\Scenario\Ai\Runtime\ScenarioAst;

#[CoversClass(ScenarioAst::class)]
#[UsesClass(ScenarioAstException::class)]
#[Group('runtime')]
#[Small]
final class ScenarioAstTest extends TestCase
{
    public function testFindsClassAndResolvesImportedParent(): void
    {
        $class = new Class_('Demo', ['extends' => new Name('BaseScenario')]);
        $ast = new ScenarioAst([new Namespace_(new Name('App\\Scenarios'), [
            new Use_([new UseItem(new Name('Vendor\\Scenario'), 'BaseScenario')]),
            $class,
        ])]);

        self::assertSame($class, $ast->getClass());
        self::assertSame('Demo', $ast->getClassName());
        self::assertSame('App\\Scenarios', $ast->getNamespace());
        self::assertNotNull($class->extends);
        self::assertSame('Vendor\\Scenario', $class->extends->toString());
    }

    public function testGlobalClassHasEmptyNamespace(): void
    {
        $ast = new ScenarioAst([new Class_('Demo')]);

        self::assertSame('Demo', $ast->getClassName());
        self::assertSame('', $ast->getNamespace());
    }

    public function testRejectsAstWithoutClass(): void
    {
        $this->expectException(ScenarioAstException::class);
        $this->expectExceptionMessage('Exactly one class is required.');

        (new ScenarioAst([]))->getClass();
    }

    public function testRejectsMultipleClasses(): void
    {
        $this->expectException(ScenarioAstException::class);

        (new ScenarioAst([new Class_('First'), new Class_('Second')]))->getClass();
    }

    public function testRejectsAnonymousClassName(): void
    {
        $ast = new ScenarioAst([new Class_(null)]);

        $this->expectException(ScenarioAstException::class);
        $ast->getClassName();
    }

    public function testRejectsAnonymousClassNamespace(): void
    {
        $ast = new ScenarioAst([new Class_(null)]);

        $this->expectException(ScenarioAstException::class);
        $ast->getNamespace();
    }
}
