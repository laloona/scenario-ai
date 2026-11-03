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
use Stateforge\Scenario\Ai\Runtime\Instruction\SkillHeaderInstruction;

#[CoversClass(SkillHeaderInstruction::class)]
#[Group('runtime')]
#[Small]
final class SkillHeaderInstructionTest extends TestCase
{
    public function testIncludesSkillMetadataHeader(): void
    {
        $text = (new SkillHeaderInstruction())->instruct(AdapterMode::Skill);

        self::assertMatchesRegularExpression(
            '/\A---\Rname: stateforge-scenario\Rdescription: \S[^\r\n]*\R---\z/',
            $text,
        );
    }
}
