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
        self::assertStringContainsString('Generate exactly one PHP class.', $text);
        self::assertStringContainsString('Never invent framework methods, attributes or interfaces.', $text);
        self::assertStringContainsString('Return responses exclusively using the provided JSON schema.', $text);
        self::assertStringContainsString('These three response states are mutually exclusive.', $text);
    }
}
