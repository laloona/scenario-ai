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
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instructions;
use const PHP_EOL;

#[CoversClass(Instructions::class)]
#[Group('runtime')]
#[Small]
final class InstructionsTest extends TestCase
{
    public function testEmptyInstructionsReturnEmptyString(): void
    {
        self::assertSame('', (new Instructions())->instruct());
    }

    public function testConcatenatesInstructionsInInsertionOrder(): void
    {
        $first = $this->createMock(Instruction::class);
        $first->expects(self::once())->method('instruct')->willReturn('First');
        $second = $this->createMock(Instruction::class);
        $second->expects(self::once())->method('instruct')->willReturn('Second');
        $instructions = new Instructions();
        $instructions->add($first);
        $instructions->add($second);

        self::assertSame('First' . PHP_EOL . PHP_EOL . 'Second' . PHP_EOL . PHP_EOL, $instructions->instruct());
    }
}
