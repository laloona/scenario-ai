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
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterImplementationInstruction;

#[CoversClass(ParameterImplementationInstruction::class)]
#[Group('runtime')]
#[Small]
final class ParameterImplementationInstructionTest extends TestCase
{
    public function testRequiresCompleteCompatibleCastingAndValueTypeInstances(): void
    {
        $text = (new ParameterImplementationInstruction())->instruct();

        self::assertStringContainsString('The cast() method MUST implement the requested validation and conversion behavior.', $text);
        self::assertStringContainsString('The cast() method MUST return a value compatible with its declared return type.', $text);
        self::assertStringContainsString('Invalid or unsupported input values MUST be handled explicitly.', $text);
        self::assertStringContainsString('The valueType() method MUST return an instance of the appropriate ValueType class.', $text);
        self::assertStringContainsString('NEVER return a class name string from valueType().', $text);
        self::assertStringContainsString('Pass the required constructor arguments when creating ValueType instances.', $text);
        self::assertStringContainsString('The selected ValueType MUST be consistent with the values returned by cast().', $text);
    }

    public function testRequiresMissingInformationInsteadOfPlaceholderImplementations(): void
    {
        $text = (new ParameterImplementationInstruction())->instruct();

        self::assertStringContainsString('Both cast() and valueType() MUST contain complete implementations.', $text);
        self::assertStringContainsString('Never replace required validation or conversion logic with placeholder comments.', $text);
        self::assertStringContainsString('If implementation requires unavailable project-specific dependencies, request the necessary information.', $text);
        self::assertStringContainsString('If meaningful implementation is impossible, return the "Unable to generate" response state.', $text);
    }
}
