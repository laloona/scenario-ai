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
use Stateforge\Scenario\Ai\Runtime\Instruction\ScenarioBlueprintInstruction;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Value\SuiteValue;
use function file_put_contents;
use const PHP_EOL;

#[CoversClass(ScenarioBlueprintInstruction::class)]
#[Group('runtime')]
#[Small]
final class ScenarioBlueprintInstructionTest extends FilesystemTestCase
{
    public function testIncludesSuiteDetailsDerivedNamespaceAndBlueprint(): void
    {
        $path = $this->testDirectory . '/scenario.blueprint';
        file_put_contents($path, 'Scenario blueprint contents');
        $text = (new ScenarioBlueprintInstruction($path, [
            'main' => new SuiteValue('main', 'Main suite', 'scenario/main'),
            'other' => new SuiteValue('other', 'Other suite', 'tests/other'),
        ]))->instruct();

        self::assertStringContainsString('name: main, description: Main suite, directory: scenario/main, namespace: Scenario\\Main', $text);
        self::assertStringContainsString('name: other, description: Other suite, directory: tests/other, namespace: Tests\\Other', $text);
        self::assertStringContainsString('Scenario Blueprint: ' . PHP_EOL . 'Scenario blueprint contents', $text);
    }

    public function testEmptySuitesDoNotListAvailableNamespaces(): void
    {
        $path = $this->testDirectory . '/scenario.blueprint';
        file_put_contents($path, 'Empty suites blueprint');
        $text = (new ScenarioBlueprintInstruction($path, []))->instruct();

        self::assertStringNotContainsString('Available Scenario Suites:', $text);
        self::assertStringContainsString('Empty suites blueprint', $text);
    }
}
