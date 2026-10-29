<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Console\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use Stateforge\Scenario\Ai\Console\Command\ScenarioAiConfigCommand;
use Stateforge\Scenario\Ai\Runtime\Adapter\AdapterRegistry;
use Stateforge\Scenario\Core\Console\Command\Command;
use Stateforge\Scenario\Core\Contract\CliOutput;
use function file_get_contents;
use function file_put_contents;
use function mkdir;

#[CoversClass(ScenarioAiConfigCommand::class)]
#[UsesClass(AdapterRegistry::class)]
#[Group('runtime')]
#[Small]
final class ScenarioAiConfigCommandTest extends CommandTestCase
{
    public function testDescription(): void
    {
        self::assertSame('Add the AI config for Scenario.', (new ScenarioAiConfigCommand())->description());
    }

    public function testReportsMissingBlueprint(): void
    {
        $output = $this->createMock(CliOutput::class);
        $output->expects(self::once())->method('error')->with('Config file generation failed.');
        $output->expects(self::never())->method('success');

        self::assertSame(Command::Error, (new ScenarioAiConfigCommand())->run($this->input(), $output));
        self::assertFileDoesNotExist($this->testDirectory . '/scenario-ai.dist.xml');
    }

    public function testCopiesBlueprintToProjectRoot(): void
    {
        $this->writeBlueprint();
        $target = $this->testDirectory . '/scenario-ai.dist.xml';
        $output = $this->createMock(CliOutput::class);
        $output->expects(self::once())->method('success')->with('Config file generated, please modify to your needs.');
        $output->expects(self::never())->method('error');

        self::assertSame(Command::Success, (new ScenarioAiConfigCommand())->run($this->input(), $output));
        self::assertSame('<scenario-ai><adapter type="test" /></scenario-ai>', file_get_contents($target));
        self::assertFileDoesNotExist($this->testDirectory . '/scenario.dist.xml');
    }

    public function testPreservesExistingConfiguration(): void
    {
        $this->writeBlueprint();
        $target = $this->testDirectory . '/scenario-ai.dist.xml';
        file_put_contents($target, 'Existing configuration');
        $output = $this->createMock(CliOutput::class);
        $output->expects(self::once())->method('error')->with('Config file already exists.');
        $output->expects(self::never())->method('success');

        self::assertSame(Command::Error, (new ScenarioAiConfigCommand())->run($this->input(), $output));
        self::assertSame('Existing configuration', file_get_contents($target));
    }

    public function testReportsFailureWhenTargetIsDirectory(): void
    {
        $this->writeBlueprint();
        mkdir($this->testDirectory . '/scenario-ai.dist.xml');
        $output = $this->createMock(CliOutput::class);
        $output->expects(self::once())->method('error')->with('Config file generation failed.');
        $output->expects(self::never())->method('success');

        // Suppress the expected file_put_contents warning for a directory target.
        self::assertSame(Command::Error, @(new ScenarioAiConfigCommand())->run($this->input(), $output));
    }

    private function writeBlueprint(): void
    {
        $directory = $this->testDirectory . '/vendor/stateforge/scenario-ai/blueprint';
        mkdir($directory, 0777, true);
        file_put_contents($directory . '/config.blueprint', '<scenario-ai><adapter type="test" /></scenario-ai>');
    }
}
