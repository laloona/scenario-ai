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
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Instruction\ClassInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ValueTypeInstruction;
use Stateforge\Scenario\Ai\Runtime\SourceCodeReader;

#[CoversClass(ValueTypeInstruction::class)]
#[UsesClass(ClassInstruction::class)]
#[UsesClass(SourceCodeReader::class)]
#[Group('runtime')]
#[Small]
final class ValueTypeInstructionTest extends TestCase
{
    public function testIncludesSupportedClassDefinitions(): void
    {
        $text = (new ValueTypeInstruction())->instruct();

        self::assertStringContainsString('Some class definitions:', $text);
        self::assertStringContainsString('class BooleanType', $text);
        self::assertStringContainsString('class FloatType', $text);
        self::assertStringContainsString('class IntegerType', $text);
        self::assertStringContainsString('class StringType', $text);
    }
}
