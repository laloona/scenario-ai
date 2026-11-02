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
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Instruction\BaseInstruction;
use function array_slice;
use function json_decode;
use function preg_match_all;
use const JSON_THROW_ON_ERROR;

#[UsesClass(AdapterMode::class)]
#[CoversClass(BaseInstruction::class)]
#[Group('runtime')]
#[Small]
final class BaseInstructionTest extends TestCase
{
    public function testIncludesFrameworkAndGenerationTypeRules(): void
    {
        $text = (new BaseInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Generate PHP code compatible with PHP ^8.2.', $text);
        self::assertStringContainsString('If code generation is required, generate exactly one PHP class.', $text);
        self::assertStringContainsString('Never invent framework methods, attributes, or interfaces.', $text);
        self::assertStringContainsString('Return responses exclusively using the provided JSON schema.', $text);
        self::assertStringContainsString('The “type” field MUST represent the requested generation type.', $text);
        self::assertStringContainsString('Missing project information MUST NOT cause a change of generation type.', $text);
    }

    public function testRequiresEvaluatingExistingImplementationsBeforeGeneratingCode(): void
    {
        $text = (new BaseInstruction())->instruct(AdapterMode::Skill);

        self::assertMatchesRegularExpression(
            '/Step 1: Determine the Request[\s\S]*'
            . 'Step 2: Determine the Current Task[\s\S]*'
            . 'Step 3: Examine Existing Implementations[\s\S]*'
            . 'Step 4: Evaluate Existing Implementations[\s\S]*'
            . 'Reuse the existing implementation automatically[\s\S]*'
            . 'Step 5: Authorize Code Generation/',
            $text,
        );
        self::assertStringContainsString('The mandatory pre-generation decision takes precedence over all general instructions requiring code generation.', (new BaseInstruction())->instruct(AdapterMode::Cli));
        self::assertStringContainsString('Generate code only after the mandatory pre-generation decision authorizes generation.', $text);
        self::assertStringContainsString('If sufficient information is available, generate a complete implementation.', $text);
    }

    public function testRequiresVerifiedProjectInformationAndPermissionForSourceAccess(): void
    {
        $text = (new BaseInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Only consider implementations compatible with the requested generation type.', $text);
        self::assertStringContainsString('Compare requested functionality against documented behavior, not class names alone.', $text);
        self::assertStringContainsString('Request source code when additional implementation details are necessary to verify compatibility.', $text);
        self::assertStringContainsString('Never assume that access to a class name or description grants access to its source code.', $text);
        self::assertStringContainsString('Use the framework’s existing source code request mechanism.', $text);
        self::assertStringContainsString('If access to a class is denied, continue using the available information without inventing missing implementation details.', $text);
    }

    public function testCompatibleImplementationCompletesTheRequestWithoutConfirmationOrGeneration(): void
    {
        $text = (new BaseInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Reuse suitable existing implementations automatically.', $text);
        self::assertStringContainsString('Do not ask the user whether an existing implementation should be reused.', $text);
        self::assertStringContainsString('Return the fully qualified class name in "match" when compatibility is established.', $text);
        self::assertStringContainsString('Returning "match" is a successful terminal response.', $text);
        self::assertStringContainsString('Do not require previous user acceptance before returning "match".', $text);
        self::assertStringContainsString('Response State 4: Existing Implementation Reused', $text);
        self::assertStringContainsString('Do not generate PHP code or ask additional questions.', $text);
        self::assertStringContainsString('Respect explicit user requests to generate a new implementation instead.', $text);
    }

    #[DataProvider('adapterModes')]
    public function testDefinesValidJsonExamplesForEachMode(AdapterMode $mode): void
    {
        $text = (new BaseInstruction())->instruct($mode);
        self::assertSame($mode === AdapterMode::Skill ? 1 : 5, preg_match_all('/^\{\r?\n.*?^\}/ms', $text, $matches));

        $expected = [
            ['php' => null, 'question' => null, 'classes' => [], 'type' => 'parameter', 'match' => 'Stateforge\\Scenario\\Laravel\\Parameter\\UuidType'],
            ['php' => null, 'question' => null, 'classes' => ['Fully\\Qualified\\ClassName'], 'type' => 'parameter', 'match' => null],
            ['php' => null, 'question' => 'One specific question', 'classes' => [], 'type' => 'parameter', 'match' => null],
            ['php' => 'Generated PHP code', 'question' => null, 'classes' => [], 'type' => 'parameter', 'match' => null],
            ['php' => null, 'question' => null, 'classes' => [], 'type' => 'parameter', 'match' => 'Fully\\Qualified\\ClassName'],
            ['php' => null, 'question' => null, 'classes' => [], 'type' => 'parameter', 'match' => null],
        ];
        $expected = $mode === AdapterMode::Skill ? [$expected[0]] : array_slice($expected, 1);
        $decoded = [];
        foreach ($matches[0] as $json) {
            $decoded[] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }
        self::assertSame($expected, $decoded);
    }

    /** @return iterable<string, array{AdapterMode}> */
    public static function adapterModes(): iterable
    {
        foreach (AdapterMode::cases() as $mode) {
            yield $mode->value => [$mode];
        }
    }

    public function testRequiresExclusiveResponseStatesAndCompatibleMatch(): void
    {
        $text = (new BaseInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('The five response states are mutually exclusive.', $text);
        self::assertStringContainsString('Exactly one response state MUST be selected per response.', $text);
        self::assertStringContainsString('Never return an unrelated implementation in "match".', $text);
        self::assertStringContainsString('The “php” and “match” fields MUST NEVER contain non-null values simultaneously.', $text);
        self::assertStringContainsString('No user confirmation is required.', $text);
        self::assertStringContainsString(
            "The \"match\" field MUST be null unless an existing implementation fully satisfies the user's requirements.",
            $text,
        );
        self::assertStringNotContainsString('The “match” field MUST be null unless the user has accepted an existing implementation.', $text);
        self::assertStringContainsString(
            "The \"match\" field contains the fully qualified class name of an existing implementation that fully satisfies the user's requirements, or null.",
            $text,
        );
        self::assertStringContainsString('Always preserve the requested generation type.', $text);
    }

    public function testPreservesOriginalRequirementsAcrossConfirmationsAndIndependentTasks(): void
    {
        $text = (new BaseInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Confirmation messages MUST NOT replace the original implementation requirements.', $text);
        self::assertStringContainsString('Only modify the original requirements when the user explicitly requests a change.', $text);
        self::assertStringContainsString('Previous tasks MUST NOT influence the implementation requirements of the current task.', $text);
        self::assertStringContainsString('Preserve the original requirements and generation type.', $text);
        self::assertStringContainsString('If the user explicitly changes the requirements, evaluate the updated requirements.', $text);
    }

    public function testQuestionLimitDoesNotAuthorizeIncompleteOrSpeculativeCode(): void
    {
        $text = (new BaseInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Ask only one specific clarification question per response.', $text);
        self::assertStringContainsString('Reaching a clarification question limit MUST NOT authorize speculative code generation.', $text);
        self::assertStringContainsString('If essential information remains unavailable, do not generate incomplete or unrelated code.', $text);
    }

    public function testUnavailableInformationCanEndTheTaskWithoutPlaceholderCode(): void
    {
        $text = (new BaseInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Response state 5: Unable to fulfill request', $text);
        self::assertStringContainsString('This response indicates that no valid implementation could be produced.', $text);
        self::assertStringContainsString('Do not generate placeholder code or invent missing functionality.', $text);
    }

    public function testInitialGenerationRequestAutomaticallyReusesAnExistingFunctionalMatch(): void
    {
        $text = (new BaseInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('The user’s initial request to create or generate code MUST NOT bypass the existing implementation check.', $text);
        self::assertStringContainsString('An existing implementation that fully satisfies the request MUST be returned in “match” automatically.', $text);
        self::assertStringContainsString('No user confirmation is required before returning a suitable existing implementation.', $text);
        self::assertStringContainsString('Do not request source code when the available description already establishes compatibility.', (new BaseInstruction())->instruct(AdapterMode::Skill));
        self::assertStringContainsString('If compatibility is confirmed, return the existing implementation in “match” immediately.', (new BaseInstruction())->instruct(AdapterMode::Skill));
    }

    public function testExplicitNewImplementationRequestBypassesReuseWithoutChangingRequirements(): void
    {
        $text = (new BaseInstruction())->instruct(AdapterMode::Skill);

        self::assertStringContainsString('The existing implementation check MUST precede code generation unless the user explicitly requires a new implementation.', (new BaseInstruction())->instruct(AdapterMode::Cli));
        self::assertStringContainsString('Generate a new class fulfilling the requested requirements.', $text);
        self::assertStringContainsString('Do not return an existing implementation in “match”.', $text);
        self::assertStringContainsString('Do not ask whether an existing implementation should be reused.', $text);
        self::assertStringContainsString('A previous decision to reuse or generate an implementation does not automatically apply to a new task.', $text);
    }

    public function testReuseRequiresFullCompatibilityAndExcludesContradictoryBehavior(): void
    {
        $text = (new BaseInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Compare each implementation’s documented functionality with ALL requirements of the user’s request.', $text);
        self::assertStringContainsString('A suitable match requires full functional compatibility, not merely semantic similarity.', $text);
        self::assertStringContainsString('Pay particular attention to contradictory conditions, such as past vs. future, minimum vs. maximum, or required vs. forbidden values.', $text);
        self::assertStringContainsString('An implementation MUST NOT be reused if any documented behavior contradicts the user’s requirements.', $text);
    }

    public function testSourceRequestsDependOnWhetherCompatibilityIsEstablishedUncertainOrExcluded(): void
    {
        $text = (new BaseInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('If the description explicitly confirms full compatibility, return the implementation in “match” without requesting its source code.', $text);
        self::assertStringContainsString('If compatibility is uncertain, request the class definition before deciding.', $text);
        self::assertStringContainsString('If an implementation is clearly incompatible, exclude it without requesting its source code.', $text);
    }

    public function testDoesNotRequireAConfirmationQuestionForSuitableExistingImplementations(): void
    {
        $text = (new BaseInstruction())->instruct(AdapterMode::Skill);

        self::assertStringNotContainsString('Ask whether the user wants to use the existing implementation or generate a new one.', $text);
        self::assertStringNotContainsString('Do not set “match” while waiting for confirmation.', $text);
        self::assertStringNotContainsString('A confirmation question about using an existing implementation is part of the mandatory pre-generation decision', $text);
        self::assertStringContainsString('Do not ask for confirmation.', $text);
    }

    public function testReusesAvailableDefinitionsInsteadOfRequestingThemAgain(): void
    {
        $text = (new BaseInstruction())->instruct(AdapterMode::Skill);

        self::assertStringContainsString('Before requesting the source code of a potentially compatible implementation, check whether its complete class definition is already available.', $text);
        self::assertStringContainsString('If the class definition is available, evaluate its compatibility directly without requesting it again.', $text);
        self::assertStringContainsString('Do not repeatedly request the same class definition when its source code has already been provided during the current task.', $text);
        self::assertStringContainsString('Class definitions included in the framework instructions, available attributes, base class definitions, or previously provided conversation messages are considered available.', $text);
        self::assertStringContainsString('Only request class definitions whose source code is not already available and whose implementation details are necessary to fulfill the current request.', $text);
    }

    #[DataProvider('sectionModes')]
    public function testIncludesSectionsForRequestedMode(AdapterMode $mode, bool $responseRules, bool $detailedEvaluation): void
    {
        $text = (new BaseInstruction())->instruct($mode);
        self::assertStringContainsString('Original Request Preservation', $text);
        self::assertStringContainsString('Conversation Context Rules', $text);
        self::assertStringContainsString('Implementation Rules', $text);
        self::assertStringContainsString('Existing Implementation Reuse Rules', $text);
        if ($responseRules) {
            self::assertStringContainsString('Response Rules', $text);
            self::assertStringContainsString('Final Decision Constraints', $text);
            self::assertStringContainsString('Never generate PHP code with “type” set to null.', $text);
        } else {
            self::assertStringNotContainsString('Response Rules', $text);
            self::assertStringNotContainsString('Final Decision Constraints', $text);
            self::assertStringNotContainsString('Never generate PHP code with “type” set to null.', $text);
        }
        if ($detailedEvaluation) {
            self::assertStringContainsString('Step 4: Evaluate Existing Implementations', $text);
            self::assertStringContainsString('Step 5: Authorize Code Generation', $text);
        } else {
            self::assertStringNotContainsString('Step 4: Evaluate Existing Implementations', $text);
            self::assertStringNotContainsString('Step 5: Authorize Code Generation', $text);
        }
    }

    /** @return iterable<string, array{AdapterMode, bool, bool}> */
    public static function sectionModes(): iterable
    {
        yield 'cli' => [AdapterMode::Cli, true, false];
        yield 'skill' => [AdapterMode::Skill, false, true];
        yield 'all' => [AdapterMode::All, true, false];
    }

}
