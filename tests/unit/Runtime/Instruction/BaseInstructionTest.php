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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Instruction\BaseInstruction;

#[CoversClass(BaseInstruction::class)]
#[Group('runtime')]
#[Small]
final class BaseInstructionTest extends TestCase
{
    public function testIncludesFrameworkGenerationAndResponseRules(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString('Generate PHP code compatible with PHP ^8.2.', $text);
        self::assertStringContainsString('If code generation is required, generate exactly one PHP class.', $text);
        self::assertStringContainsString('Never invent framework methods, attributes or interfaces.', $text);
        self::assertStringContainsString('Return responses exclusively using the provided JSON schema.', $text);
        self::assertStringContainsString('These three response states are mutually exclusive.', $text);
    }

    public function testIncludesRulesForAccessToExistingProjectClasses(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString('Access to Existing Project Classes', $text);
        self::assertStringContainsString('Existing class names and descriptions may be provided as project context.', $text);
        self::assertStringContainsString('Source code of existing project classes must only be requested when additional implementation details are required.', $text);
        self::assertStringContainsString('Never assume that access to a class name or description also grants permission to access its source code.', $text);
        self::assertStringContainsString("Use the framework's existing source code request mechanism to request additional information.", $text);
        self::assertStringContainsString('If access to a class is denied, continue using the available information without inventing missing implementation details.', $text);
    }

    public function testIncludesRulesForEvaluatingExistingImplementations(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString("Before generating new code, check whether an existing implementation already fulfills the user's requirements.", $text);
        self::assertStringContainsString('If an existing implementation appears suitable but additional information is required to verify its compatibility, request access to its source code.', $text);
        self::assertStringContainsString('Prefer reusing existing implementations when they satisfy the requirements and their usage is supported by the framework.', $text);
        self::assertStringContainsString("If an existing implementation already fulfills the user's requirements, inform the user about the existing class, including its name and description.", $text);
        self::assertStringContainsString('If the user provides new requirements, evaluate them against the available implementations before generating code.', $text);
        self::assertStringContainsString('Never assume the existence of methods, properties, constructor arguments, or other implementation details that have not been provided or verified.', $text);
    }

    public function testIncludesRulesForConfirmingDuplicateImplementation(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertStringContainsString('Ask the user whether a new implementation should still be generated.', $text);
        self::assertStringContainsString('If the user confirms, proceed with generating the new implementation.', $text);
        self::assertStringContainsString('If the user declines, ask for new or modified requirements instead of generating duplicate code.', $text);
        self::assertStringContainsString('Do not repeatedly ask for confirmation once the user has explicitly decided to create a new implementation.', $text);
    }
    public function testRequiresEvaluatingExistingImplementationsBeforeGeneratingCode(): void
    {
        $text = (new BaseInstruction())->instruct();

        self::assertMatchesRegularExpression(
            '/1\. Examine all available existing implementations\.[\s\S]*'
            . '2\. Compare the user\x27s requested behavior[\s\S]*'
            . '3\. If an existing implementation fully satisfies the request:[\s\S]*'
            . 'Do not generate code\.[\s\S]*'
            . 'Ask whether a new implementation is still desired\.[\s\S]*'
            . '4\. If an existing implementation might satisfy the request but its description is insufficient:[\s\S]*'
            . 'Request its source code\.[\s\S]*'
            . 'Do not generate code until compatibility\s+has been evaluated\.[\s\S]*'
            . '5\. Only if no suitable existing implementation is available, proceed with code generation\./',
            $text,
        );
        self::assertStringContainsString(
            'This decision process takes precedence over all general instructions requiring code generation.',
            $text,
        );
    }

}
