<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Instruction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Instruction\BaseInstruction;
use function json_decode;
use function preg_match_all;
use function str_replace;
use const JSON_THROW_ON_ERROR;

#[CoversClass(BaseInstruction::class)]
#[Group('runtime')]
#[Small]
final class BaseInstructionTest extends TestCase
{
    public function testIncludesFrameworkAndGenerationTypeRules(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString('Generate PHP code compatible with PHP ^8.2.', $text);
        self::assertStringContainsString('If code generation is required, generate exactly one PHP class.', $text);
        self::assertStringContainsString('Never invent framework methods, attributes or interfaces.', $text);
        self::assertStringContainsString('Return responses exclusively using the provided JSON schema.', $text);
        self::assertStringContainsString('The “type” field MUST always represent the original requested generation type.', $text);
        self::assertStringContainsString('Missing project information MUST NOT cause a change of generation type.', $text);
    }

    public function testRequiresEvaluatingExistingImplementationsBeforeGeneratingCode(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertMatchesRegularExpression(
            '/1\. Determine the requested generation type\.[\s\S]*'
            . '2\. Examine all available existing implementations of that generation type\.[\s\S]*'
            . '3\. Compare the user’s requested behavior[\s\S]*'
            . '4\. If an existing implementation fully satisfies the request:[\s\S]*'
            . 'Do not generate code\.[\s\S]*'
            . 'Do not set “match” until the user confirms[\s\S]*'
            . '5\. If an existing implementation might satisfy the request but its description is insufficient:[\s\S]*'
            . 'Request its source code[\s\S]*'
            . '6\. If the user accepts the proposed existing implementation:[\s\S]*'
            . '7\. If the user explicitly requests a new implementation:[\s\S]*'
            . '8\. If no suitable existing implementation is available:/',
            $text,
        );
        self::assertStringContainsString('This decision process takes precedence over all instructions requiring code generation.', $text);
        self::assertStringContainsString('Generate code only when authorized by the mandatory pre-generation decision.', $text);
        self::assertStringContainsString('If code generation is authorized and sufficient information is available, generate the complete implementation.', $text);
    }

    public function testRequiresVerifiedProjectInformationAndPermissionForSourceAccess(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString('Only consider implementations compatible with the originally requested generation type.', $text);
        self::assertStringContainsString('Compare requested functionality against documented behavior, not class names alone.', $text);
        self::assertStringContainsString('Source code of existing project classes must only be requested when additional implementation details are required.', $text);
        self::assertStringContainsString('Never assume that access to a class name or description also grants permission to access its source code.', $text);
        self::assertStringContainsString('Use the framework’s existing source code request mechanism to request additional information.', $text);
        self::assertStringContainsString('If access to a class is denied, continue using the available information without inventing missing implementation details.', $text);
    }

    public function testAcceptedImplementationCompletesTheRequestWithoutFurtherGeneration(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString('Resolve the user’s response according to the actual question asked, not merely the presence of “yes” or “no”.', $text);
        self::assertStringContainsString('If the user accepts an existing implementation, return the completed match response immediately.', $text);
        self::assertStringContainsString('Do not repeat the existing implementation search after acceptance.', $text);
        self::assertStringContainsString('Do not generate code after acceptance.', $text);
        self::assertStringContainsString('Do not change the generation type after acceptance.', $text);
        self::assertStringContainsString('Do not ask additional questions after acceptance.', $text);
        self::assertStringContainsString('Do not repeatedly ask for confirmation once the user has made a decision.', $text);
        self::assertStringContainsString('A completed match response is a successful result, not a generation failure.', $text);
    }

    #[DataProvider('lineEndings')]
    public function testDefinesFourValidJsonResponseExamplesIncludingAcceptedMatch(string $lineEnding): void
    {
        $text = str_replace("\r\n", "\n", (new BaseInstruction())->instruct());
        $text = str_replace("\n", $lineEnding, $text);
        self::assertSame(4, preg_match_all('/^\{\r?\n.*?^\}/ms', $text, $matches));

        $expected = [
            ['php' => null, 'question' => null, 'classes' => ['Fully\\Qualified\\ClassName'], 'type' => 'parameter', 'match' => null],
            ['php' => null, 'question' => 'One specific question', 'classes' => [], 'type' => 'parameter', 'match' => null],
            ['php' => 'Generated PHP code', 'question' => null, 'classes' => [], 'type' => 'parameter', 'match' => null],
            ['php' => null, 'question' => null, 'classes' => [], 'type' => 'parameter', 'match' => 'Fully\\Qualified\\ClassName'],
        ];
        $decoded = [];
        foreach ($matches[0] as $json) {
            $decoded[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }
        self::assertSame($expected, $decoded);
    }

    /** @return iterable<string, array{string}> */
    public static function lineEndings(): iterable
    {
        yield 'Unix LF' => ["\n"];
        yield 'Windows CRLF' => ["\r\n"];
    }

    public function testRequiresExclusiveResponseStatesAndConfirmationBeforeSettingMatch(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString('The four response states are mutually exclusive.', $text);
        self::assertStringContainsString('Exactly one response state MUST be selected per response.', $text);
        self::assertStringContainsString('The “match” field MUST only be set after the user has accepted the proposed existing implementation.', $text);
        self::assertStringContainsString('The “php” and “match” fields MUST NEVER contain non-null values simultaneously.', $text);
        self::assertStringContainsString('Do not set “match” while waiting for confirmation.', $text);
        self::assertStringContainsString('The “match” field MUST NOT contain a description or explanatory text.', $text);
        self::assertStringContainsString('Always preserve the originally requested generation type.', $text);
    }
}
