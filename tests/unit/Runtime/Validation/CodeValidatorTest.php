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
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\AstBuilder;
use Stateforge\Scenario\Ai\Runtime\Exception\AttributeValidatorException;
use Stateforge\Scenario\Ai\Runtime\Exception\InheritanceValidatorException;
use Stateforge\Scenario\Ai\Runtime\Exception\InvalidGeneratedCodeException;
use Stateforge\Scenario\Ai\Runtime\ScenarioAst;
use Stateforge\Scenario\Ai\Runtime\Validation\AttributeFinder;
use Stateforge\Scenario\Ai\Runtime\Validation\AttributeValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\CodeValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\InheritanceValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\SyntaxValidator;

#[CoversClass(CodeValidator::class)]
#[UsesClass(AiResponse::class)]
#[UsesClass(AstBuilder::class)]
#[UsesClass(ScenarioAst::class)]
#[UsesClass(SyntaxValidator::class)]
#[UsesClass(InheritanceValidator::class)]
#[UsesClass(AttributeValidator::class)]
#[UsesClass(AttributeFinder::class)]
#[UsesClass(InvalidGeneratedCodeException::class)]
#[UsesClass(InheritanceValidatorException::class)]
#[UsesClass(AttributeValidatorException::class)]
#[Group('runtime')]
#[Small]
final class CodeValidatorTest extends TestCase
{
    #[DataProvider('incompleteResponseProvider')]
    public function testSkipsResponseWithoutCodeOrGenerationType(?string $php, ?string $type): void
    {
        $response = new AiResponse(null, $php, $type === null ? null : GenerationType::from($type), null, []);
        $this->validator()->validate($response);

        self::assertSame($php, $response->php);
    }

    /** @return array<string, array{?string, ?string}> */
    public static function incompleteResponseProvider(): array
    {
        return ['no code' => [null, 'scenario'], 'no type' => ['not php', null], 'neither' => [null, null]];
    }

    #[DataProvider('validCodeProvider')]
    public function testValidatesSelectedGenerationType(string $type, string $code): void
    {
        $response = new AiResponse(null, $code, GenerationType::from($type), null, []);
        $this->validator()->validate($response);

        self::assertSame($code, $response->php);
    }

    /** @return array<string, array{string, string}> */
    public static function validCodeProvider(): array
    {
        return [
            'scenario' => ['scenario', '<?php #[\\Attribute] class Demo extends \\RuntimeException {}'],
            'parameter' => ['parameter', '<?php #[\\Deprecated] class Demo extends \\LogicException {}'],
        ];
    }

    public function testRejectsInvalidSyntax(): void
    {
        $this->expectException(InvalidGeneratedCodeException::class);
        $this->validator()->validate(new AiResponse(null, '<?php class {', GenerationType::Scenario, null, []));
    }

    public function testRejectsWrongInheritance(): void
    {
        $this->expectException(InheritanceValidatorException::class);
        $this->validator()->validate(new AiResponse(null, '<?php #[\\Attribute] class Demo extends \\stdClass {}', GenerationType::Scenario, null, []));
    }

    public function testRejectsMissingAttribute(): void
    {
        $this->expectException(AttributeValidatorException::class);
        $this->validator()->validate(new AiResponse(null, '<?php class Demo extends \\RuntimeException {}', GenerationType::Scenario, null, []));
    }

    private function validator(): CodeValidator
    {
        return new CodeValidator('RuntimeException', 'Attribute', 'LogicException', 'Deprecated');
    }
}
