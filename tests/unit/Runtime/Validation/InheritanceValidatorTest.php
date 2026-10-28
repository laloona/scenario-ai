<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\AstBuilder;
use Stateforge\Scenario\Ai\Runtime\Exception\InheritanceValidatorException;
use Stateforge\Scenario\Ai\Runtime\Exception\NoExistingParentClassException;
use Stateforge\Scenario\Ai\Runtime\Exception\NoInheritanceException;
use Stateforge\Scenario\Ai\Runtime\ScenarioAst;
use Stateforge\Scenario\Ai\Runtime\Validation\InheritanceValidator;

#[CoversClass(InheritanceValidator::class)]
#[UsesClass(AstBuilder::class)]
#[UsesClass(ScenarioAst::class)]
#[UsesClass(NoInheritanceException::class)]
#[UsesClass(NoExistingParentClassException::class)]
#[UsesClass(InheritanceValidatorException::class)]
#[Group('runtime')]
#[Small]
final class InheritanceValidatorTest extends TestCase
{
    #[DataProvider('validParentProvider')]
    public function testAcceptsDirectAndIndirectInheritance(string $parent, string $expected): void
    {
        $ast = (new AstBuilder())->build('<?php class Demo extends \\' . $parent . ' {}');

        (new InheritanceValidator($expected))->validate($ast);

        self::assertSame('Demo', $ast->getClassName());
    }

    /** @return array<string, array{string, string}> */
    public static function validParentProvider(): array
    {
        return [
            'direct' => ['RuntimeException', 'RuntimeException'],
            'case and slash' => ['RuntimeException', '\\runtimeexception'],
            'indirect' => ['RuntimeException', 'Exception'],
        ];
    }

    public function testRejectsMissingParent(): void
    {
        $ast = (new AstBuilder())->build('<?php class Demo {}');
        $this->expectException(NoInheritanceException::class);

        (new InheritanceValidator('Exception'))->validate($ast);
    }

    public function testRejectsUnknownParent(): void
    {
        $ast = (new AstBuilder())->build('<?php class Demo extends MissingAiParent {}');
        $this->expectException(NoExistingParentClassException::class);
        $this->expectExceptionMessage('Parent class "MissingAiParent" does not exist.');

        (new InheritanceValidator('Exception'))->validate($ast);
    }

    public function testRejectsUnrelatedParent(): void
    {
        $ast = (new AstBuilder())->build('<?php class Demo extends stdClass {}');
        $this->expectException(InheritanceValidatorException::class);
        $this->expectExceptionMessage('Class "stdClass" must extend "Exception" directly or indirectly.');

        (new InheritanceValidator('Exception'))->validate($ast);
    }
}
