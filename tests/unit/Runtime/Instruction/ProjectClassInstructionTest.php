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
use Stateforge\Scenario\Ai\Runtime\Instruction\ProjectClassInstruction;

#[CoversClass(ProjectClassInstruction::class)]
#[Group('runtime')]
#[Small]
final class ProjectClassInstructionTest extends TestCase
{
    public function testRestrictsClassRequestsToAvailableUnsharedDefinitions(): void
    {
        $text = (new ProjectClassInstruction())->instruct();

        self::assertStringContainsString('Only request class definitions explicitly listed as available.', $text);
        self::assertStringContainsString('Never request a class definition that has already been provided.', $text);
        self::assertStringContainsString('Never request a class definition that the user has declined to share.', $text);
        self::assertStringContainsString('Add requested fully qualified class names to the classes array.', $text);
    }
}
