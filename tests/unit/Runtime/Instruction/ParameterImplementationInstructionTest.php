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
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterImplementationInstruction;

#[CoversClass(ParameterImplementationInstruction::class)]
#[Group('runtime')]
#[Small]
final class ParameterImplementationInstructionTest extends TestCase
{
    public function testIncludesSectionHeading(): void
    {
        $text = (new ParameterImplementationInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('ParameterType implementation rules:', $text);
    }
}
