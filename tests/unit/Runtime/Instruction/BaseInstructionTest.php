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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Instruction\BaseInstruction;

#[CoversClass(BaseInstruction::class)]
#[UsesClass(AdapterMode::class)]
#[Group('runtime')]
#[Small]
final class BaseInstructionTest extends TestCase
{
    /**
     * @param list<string> $included
     * @param list<string> $excluded
     */
    #[DataProvider('sectionModes')]
    public function testIncludesSectionHeadingsForRequestedMode(AdapterMode $mode, array $included, array $excluded): void
    {
        $text = (new BaseInstruction())->instruct($mode);

        foreach ([
            'Introduction',
            'General Rules',
            'Generation Type Rules',
            'Original Request Preservation',
            'Conversation Context Rules',
            'Mandatory Pre-Generation Decision',
            'Step 1: Determine the Request',
            'Step 2: Determine the Current Task',
            'Step 3: Examine Existing Implementations',
            'Implementation Rules',
            'Access to Existing Project Classes',
            'Existing Implementation Reuse Rules',
            'Clarification Rules',
            ...$included,
        ] as $heading) {
            self::assertStringContainsString($heading, $text);
        }
        foreach ($excluded as $heading) {
            self::assertStringNotContainsString($heading, $text);
        }
    }

    /** @return iterable<string, array{AdapterMode, list<string>, list<string>}> */
    public static function sectionModes(): iterable
    {
        $cliHeadings = [
            'Final Decision Constraints',
            'Response Rules',
            'Response State 1: Request Existing Class Definitions',
            'Response State 2: Ask a Clarification Question',
            'Response State 3: Generate PHP Code',
            'Response State 4: Existing Implementation Reused',
            'Response state 5: Unable to fulfill request',
            'Response State Constraints',
        ];
        $decisionHeadings = [
            'Step 4: Evaluate Existing Implementations',
            'Case A: A Suitable Existing Implementation Is Found',
            'Case B: An Existing Implementation Might Be Suitable',
            'Case C: No Suitable Existing Implementation Is Available',
            'Case D: The User Explicitly Requires a New Implementation',
            'Step 5: Authorize Code Generation',
        ];

        $cliHeadings = [...$decisionHeadings, ...$cliHeadings];

        yield 'cli' => [AdapterMode::Cli, $cliHeadings, []];
        yield 'skill' => [AdapterMode::Skill, [], $cliHeadings];
        yield 'all' => [AdapterMode::All, $cliHeadings, []];
    }
}
