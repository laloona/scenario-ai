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
use Stateforge\Scenario\Ai\Runtime\Instruction\BlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterBlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ScenarioBlueprintInstruction;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use function file_put_contents;
use function substr_count;
use const PHP_EOL;

#[CoversClass(BlueprintInstruction::class)]
#[UsesClass(ScenarioBlueprintInstruction::class)]
#[UsesClass(ParameterBlueprintInstruction::class)]
#[Group('runtime')]
#[Small]
final class BlueprintInstructionTest extends FilesystemTestCase
{
    public function testCombinesBlueprintRulesAndBothBlueprintsInOrder(): void
    {
        $scenario = $this->testDirectory . '/scenario.blueprint';
        $parameter = $this->testDirectory . '/parameter.blueprint';
        file_put_contents($scenario, 'SCENARIO_MARKER');
        file_put_contents($parameter, 'PARAMETER_MARKER');
        $text = (new BlueprintInstruction(
            new ScenarioBlueprintInstruction($scenario, []),
            new ParameterBlueprintInstruction($parameter, null),
        ))->instruct();

        self::assertStringContainsString('Use only the blueprint corresponding to the selected generation type.', $text);
        self::assertSame(1, substr_count($text, PHP_EOL . 'Namespace rules:' . PHP_EOL));
        self::assertStringContainsString('The %nameSpace% placeholder MUST be replaced using literal string substitution.', $text);
        self::assertStringContainsString('SCENARIO_MARKER', $text);
        self::assertStringContainsString('PARAMETER_MARKER', $text);
        self::assertMatchesRegularExpression('/SCENARIO_MARKER[\s\S]*PARAMETER_MARKER/', $text);
    }
}
