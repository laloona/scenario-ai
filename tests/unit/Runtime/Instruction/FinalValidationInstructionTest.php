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
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Instruction\FinalValidationInstruction;

#[CoversClass(FinalValidationInstruction::class)]
#[Group('runtime')]
#[Small]
final class FinalValidationInstructionTest extends TestCase
{
    public function testChecksTaskIdentityAndCompatibleMatchBeforeReturning(): void
    {
        $text = (new FinalValidationInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('The response uses the correct generation type.', $text);
        self::assertStringContainsString('The response belongs to the current task.', $text);
        self::assertStringContainsString('The original requirements have been preserved unless explicitly modified.', $text);
        self::assertStringContainsString('The mandatory existing implementation decision has been completed.', $text);
        self::assertStringContainsString('The selected response state matches the current conversation state.', $text);
        self::assertStringContainsString(
            'If “match” is set, the existing implementation fully satisfies ALL requirements of the user and the user has not explicitly requested a new implementation.',
            $text,
        );
        self::assertStringNotContainsString('the user has accepted the existing implementation', $text);
        self::assertStringContainsString('If “match” is set, “php” is null.', $text);
    }

    public function testChecksGeneratedCodeAndResponseSchema(): void
    {
        $text = (new FinalValidationInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('If PHP code is generated, generation has been authorized by the mandatory pre-generation decision.', $text);
        self::assertStringContainsString('If PHP code is generated, it fulfills the original or explicitly modified requirements.', $text);
        self::assertStringContainsString('The generated class name and description match the implemented functionality.', $text);
        self::assertStringContainsString('The generated implementation follows the selected blueprint.', $text);
        self::assertStringContainsString('The response contains exactly the required five JSON fields.', $text);
    }

    public function testRejectsContradictoryMatchesAndRequestsDefinitionsWhenCompatibilityIsUncertain(): void
    {
        $text = (new FinalValidationInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Before returning “match”, compare the requested functionality and validation conditions with the existing implementation’s documented behavior.', $text);
        self::assertStringContainsString('An existing implementation MUST NOT be returned in “match” if any behavior contradicts the user’s requirements.', $text);
        self::assertStringContainsString('Semantic similarity alone is insufficient. For example, past and future date validation are incompatible.', $text);
        self::assertStringContainsString('If compatibility cannot be established from the available information, request the relevant class definition instead of returning “match”.', $text);
    }

    public function testExcludesAlreadyAvailableDefinitionsFromClassRequests(): void
    {
        $text = (new FinalValidationInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('If class definitions are requested, verify that their complete source code is not already available in the current task context.', $text);
        self::assertStringContainsString('Never include a class in the “classes” response field if its complete definition has already been provided through framework instructions, base class definitions, or previous conversation messages.', $text);
    }

}
