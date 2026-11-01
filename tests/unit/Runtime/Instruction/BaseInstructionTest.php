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
        self::assertStringContainsString('Never invent framework methods, attributes, or interfaces.', $text);
        self::assertStringContainsString('Return responses exclusively using the provided JSON schema.', $text);
        self::assertStringContainsString('The “type” field MUST represent the requested generation type.', $text);
        self::assertStringContainsString('Missing project information MUST NOT cause a change of generation type.', $text);
    }

    public function testRequiresEvaluatingExistingImplementationsBeforeGeneratingCode(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertMatchesRegularExpression(
            '/Step 1: Determine the Request[\s\S]*'
            . 'Step 2: Determine the Current Decision State[\s\S]*'
            . 'Step 3: Examine Existing Implementations[\s\S]*'
            . 'Step 4: Evaluate Existing Implementations[\s\S]*'
            . 'Set “match” to null until the user accepts[\s\S]*'
            . 'Step 5: Process the User’s Decision[\s\S]*'
            . 'Step 6: Authorize Code Generation/',
            $text,
        );
        self::assertStringContainsString('The mandatory pre-generation decision takes precedence over all general instructions requiring code generation.', $text);
        self::assertStringContainsString('Generate code only after the mandatory pre-generation decision authorizes generation.', $text);
        self::assertStringContainsString('If sufficient information is available, generate a complete implementation.', $text);
    }

    public function testRequiresVerifiedProjectInformationAndPermissionForSourceAccess(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString('Only consider implementations compatible with the requested generation type.', $text);
        self::assertStringContainsString('Compare requested functionality against documented behavior, not class names alone.', $text);
        self::assertStringContainsString('Request source code when additional implementation details are necessary to verify compatibility.', $text);
        self::assertStringContainsString('Never assume that access to a class name or description grants access to its source code.', $text);
        self::assertStringContainsString('Use the framework’s existing source code request mechanism.', $text);
        self::assertStringContainsString('If access to a class is denied, continue using the available information without inventing missing implementation details.', $text);
    }

    public function testAcceptedImplementationCompletesTheRequestWithoutFurtherGeneration(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString('Interpret the user’s response according to the question that was actually asked.', $text);
        self::assertStringContainsString('If the user accepts the existing implementation, return the completed match response immediately.', $text);
        self::assertStringContainsString('Do not repeat the existing implementation search after the user has made a decision for unchanged requirements.', $text);
        self::assertStringContainsString('Do not generate PHP code, request additional class definitions, or ask further questions after returning this state.', $text);
        self::assertStringContainsString('Do not change the generation type after confirmation.', $text);
        self::assertStringContainsString('Do not ask additional questions after acceptance of an existing implementation.', $text);
        self::assertStringContainsString('Do not repeatedly ask for confirmation once the user has made a decision.', $text);
        self::assertStringContainsString('A completed match response is a successful result, not a generation failure.', $text);
    }

    #[DataProvider('lineEndings')]
    public function testDefinesFiveValidJsonResponseExamplesIncludingAcceptedMatchAndFailure(string $lineEnding): void
    {
        $text = str_replace("\r\n", "\n", (new BaseInstruction())->instruct());
        $text = str_replace("\n", $lineEnding, $text);
        self::assertSame(5, preg_match_all('/^\{\r?\n.*?^\}/ms', $text, $matches));

        $expected = [
            ['php' => null, 'question' => null, 'classes' => ['Fully\\Qualified\\ClassName'], 'type' => 'parameter', 'match' => null],
            ['php' => null, 'question' => 'One specific question', 'classes' => [], 'type' => 'parameter', 'match' => null],
            ['php' => 'Generated PHP code', 'question' => null, 'classes' => [], 'type' => 'parameter', 'match' => null],
            ['php' => null, 'question' => null, 'classes' => [], 'type' => 'parameter', 'match' => 'Fully\\Qualified\\ClassName'],
            ['php' => null, 'question' => null, 'classes' => [], 'type' => 'parameter', 'match' => null],
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

        self::assertStringContainsString('The five response states are mutually exclusive.', $text);
        self::assertStringContainsString('Exactly one response state MUST be selected per response.', $text);
        self::assertStringContainsString('The “match” field MUST be null unless the user has accepted an existing implementation.', $text);
        self::assertStringContainsString('The “php” and “match” fields MUST NEVER contain non-null values simultaneously.', $text);
        self::assertStringContainsString('Do not set “match” while waiting for confirmation.', $text);
        self::assertStringContainsString('The “match” field contains the fully qualified class name of an accepted existing implementation or null.', $text);
        self::assertStringContainsString('Always preserve the requested generation type.', $text);
    }

    public function testPreservesOriginalRequirementsAcrossConfirmationsAndIndependentTasks(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString('Confirmation messages MUST NOT replace the original implementation requirements.', $text);
        self::assertStringContainsString('Only modify the original requirements when the user explicitly requests a change.', $text);
        self::assertStringContainsString('Previous tasks MUST NOT influence the implementation requirements of the current task.', $text);
        self::assertStringContainsString('Preserve the original implementation requirements.', $text);
        self::assertStringContainsString('If the user explicitly changes the requirements, repeat the existing implementation check using the updated requirements.', $text);
    }

    public function testQuestionLimitDoesNotAuthorizeIncompleteOrSpeculativeCode(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString('Ask only one specific clarification question per response.', $text);
        self::assertStringContainsString('Reaching a clarification question limit MUST NOT authorize speculative code generation.', $text);
        self::assertStringContainsString('If essential information remains unavailable, do not generate incomplete or unrelated code.', $text);
    }

    public function testUnavailableInformationCanEndTheTaskWithoutPlaceholderCode(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString('Response state 5: Unable to fulfill request', $text);
        self::assertStringContainsString('This response indicates that no valid implementation could be produced.', $text);
        self::assertStringContainsString('Do not generate placeholder code or invent missing functionality.', $text);
    }


    public function testInitialGenerationRequestCannotBypassAnExistingFunctionalMatch(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString('The user’s initial request to create or generate code MUST NOT bypass the existing implementation check.', $text);
        self::assertStringContainsString('An explicitly documented functional match MUST be proposed before generating new code.', $text);
        self::assertStringContainsString('Do not request source code when the available description already establishes compatibility.', $text);
        self::assertStringContainsString('The user has explicitly requested a new implementation after being offered a suitable existing implementation.', $text);
    }

    public function testAmbiguousDecisionKeepsCandidateAndDoesNotAuthorizeGeneration(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString('Do not assume acceptance or rejection.', $text);
        self::assertStringContainsString('Preserve the proposed existing implementation as the active candidate.', $text);
        self::assertStringContainsString('If clarification is not permitted and the decision remains unresolved, return the defined unable-to-fulfill response.', $text);
        self::assertStringContainsString('A completed decision MUST NOT be repeated unless the requirements change.', $text);
    }

}
