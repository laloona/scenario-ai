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
use Stateforge\Scenario\Ai\Runtime\Instruction\AttributeInstruction;
use Stateforge\Scenario\Ai\Runtime\SourceCodeReader;

#[CoversClass(AttributeInstruction::class)]
#[UsesClass(SourceCodeReader::class)]
#[Group('runtime')]
#[Small]
final class AttributeInstructionTest extends TestCase
{
    public function testIncludesSupportedClassDefinitions(): void
    {
        $text = (new AttributeInstruction())->instruct();

        self::assertStringContainsString('Available attributes:', $text);
        self::assertStringContainsString('class AsScenario', $text);
        self::assertStringContainsString('class AsParameterType', $text);
        self::assertStringContainsString('class ApplyScenario', $text);
        self::assertStringContainsString('class Parameter', $text);
        self::assertStringContainsString('class ParameterTypeCondition', $text);
        self::assertStringContainsString('class RefreshDatabase', $text);
    }
}
