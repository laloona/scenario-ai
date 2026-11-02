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
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Instruction\ScenarioBlueprintInstruction;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Value\SuiteValue;
use function file_put_contents;

#[CoversClass(ScenarioBlueprintInstruction::class)]
#[UsesClass(AdapterMode::class)]
#[Group('runtime')]
#[Small]
final class ScenarioBlueprintInstructionTest extends FilesystemTestCase
{
    public function testIncludesSectionHeadingsWithConfiguredSuites(): void
    {
        $path = $this->testDirectory . '/scenario.blueprint';
        file_put_contents($path, 'Scenario blueprint contents');
        $text = (new ScenarioBlueprintInstruction($path, [
            'main' => new SuiteValue('main', 'Main suite', 'scenario/main'),
            'other' => new SuiteValue('other', 'Other suite', 'tests/other'),
        ]))->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Scenario Rules', $text);
        self::assertStringContainsString('Scenario Blueprint:', $text);
        self::assertStringContainsString('Scenario Namespace Rules', $text);
        self::assertStringContainsString('Available Scenario Suites:', $text);

    }

    public function testOmitsSuiteHeadingWithoutConfiguredSuites(): void
    {
        $path = $this->testDirectory . '/scenario.blueprint';
        file_put_contents($path, 'Empty suites blueprint');
        $text = (new ScenarioBlueprintInstruction($path, []))->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Scenario Rules', $text);
        self::assertStringContainsString('Scenario Blueprint:', $text);
        self::assertStringContainsString('Scenario Namespace Rules', $text);
        self::assertStringNotContainsString('Available Scenario Suites:', $text);

    }

    public function testIncludesSectionHeadingsForSkillAndAllModes(): void
    {
        $path = $this->testDirectory . '/scenario.blueprint';
        file_put_contents($path, 'Skill blueprint marker');
        $instruction = new ScenarioBlueprintInstruction($path, ['main' => new SuiteValue('main', 'Project suite marker', 'scenario/main')]);
        $text = $instruction->instruct(AdapterMode::Skill);

        self::assertStringContainsString('Scenario Rules', $text);
        self::assertStringContainsString('Scenario Blueprint:', $text);
        self::assertStringContainsString('Scenario Namespace Rules', $text);
        self::assertStringNotContainsString('Available Scenario Suites:', $text);
        self::assertStringContainsString('Available Scenario Suites:', $instruction->instruct(AdapterMode::All));

    }
}
