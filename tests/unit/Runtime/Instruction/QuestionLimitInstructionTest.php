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
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Instruction\QuestionLimitInstruction;

#[CoversClass(QuestionLimitInstruction::class)]
#[UsesClass(AdapterMode::class)]
#[Group('runtime')]
#[Small]
final class QuestionLimitInstructionTest extends TestCase
{
    public function testIncludesSectionHeadingInCliMode(): void
    {
        $text = (new QuestionLimitInstruction(7, 3))->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Conversation rules:', $text);
    }

    public function testSkillAndAllModesOmitConversationLimits(): void
    {
        $instruction = new QuestionLimitInstruction(7, 3);
        self::assertSame('', $instruction->instruct(AdapterMode::Skill));
        self::assertSame('', $instruction->instruct(AdapterMode::All));
    }
}
