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
use Stateforge\Scenario\Ai\Runtime\Instruction\FinalValidationInstruction;

#[CoversClass(FinalValidationInstruction::class)]
#[UsesClass(AdapterMode::class)]
#[Group('runtime')]
#[Small]
final class FinalValidationInstructionTest extends TestCase
{
    public function testIncludesSectionHeading(): void
    {
        $text = (new FinalValidationInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Final Validation', $text);
    }
}
