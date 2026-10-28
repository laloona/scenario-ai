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

use PhpParser\Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\AstBuilder;
use Stateforge\Scenario\Ai\Runtime\Exception\ScenarioAstException;
use Stateforge\Scenario\Ai\Runtime\ScenarioAst;

#[CoversClass(AstBuilder::class)]
#[UsesClass(ScenarioAst::class)]
#[UsesClass(ScenarioAstException::class)]
#[Group('runtime')]
#[Small]
final class AstBuilderTest extends TestCase
{
    public function testBuildParsesNamespacedClass(): void
    {
        $ast = (new AstBuilder())->build('<?php namespace App\\Scenario; final class Demo {}');

        self::assertSame('Demo', $ast->getClassName());
        self::assertSame('App\\Scenario', $ast->getNamespace());
    }

    public function testBuildRejectsInvalidPhp(): void
    {
        $this->expectException(Error::class);

        (new AstBuilder())->build('<?php class {');
    }

    public function testEmptyPhpProducesAstWithoutClass(): void
    {
        $ast = (new AstBuilder())->build('<?php');

        $this->expectException(ScenarioAstException::class);
        $ast->getClass();
    }
}
