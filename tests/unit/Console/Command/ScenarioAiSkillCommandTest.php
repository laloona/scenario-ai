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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use Stateforge\Scenario\Ai\Console\CliSkill;
use Stateforge\Scenario\Ai\Console\Command\ScenarioAiSkillCommand;
use Stateforge\Scenario\Ai\Contract\AiAdapter;
use Stateforge\Scenario\Ai\Contract\AiSkill;
use Stateforge\Scenario\Ai\Runtime\Adapter\AdapterRegistry;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Ai\Runtime\Configuration;
use Stateforge\Scenario\Ai\Runtime\Context;
use Stateforge\Scenario\Ai\Runtime\Instruction\BlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ClassInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\GeneralInstructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterBlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ScenarioBlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\SourceCodeRegistry;
use Stateforge\Scenario\Core\Console\Command\Command;
use Stateforge\Scenario\Core\Contract\CliOutput;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration as CoreConfiguration;

#[CoversClass(ScenarioAiSkillCommand::class)]
#[UsesClass(CliSkill::class)]
#[UsesClass(Ai::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(AdapterConfiguration::class)]
#[UsesClass(AdapterRegistry::class)]
#[UsesClass(Context::class)]
#[UsesClass(Instructions::class)]
#[UsesClass(GeneralInstructions::class)]
#[UsesClass(ClassInstruction::class)]
#[UsesClass(SourceCodeRegistry::class)]
#[UsesClass(BlueprintInstruction::class)]
#[UsesClass(ScenarioBlueprintInstruction::class)]
#[UsesClass(ParameterBlueprintInstruction::class)]
#[Small]
final class ScenarioAiSkillCommandTest extends CommandTestCase
{
    public function testDescribesSkillGeneration(): void
    {
        self::assertSame('Generate a skill IDE-ready skill for AI.', (new ScenarioAiSkillCommand())->description());
    }

    #[DataProvider('resultProvider')]
    public function testRunsSkillGenerationWithDefaultContext(?string $path, Command $expected): void
    {
        $core = self::createStub(CoreConfiguration::class);
        $core->method('getSuites')->willReturn([]);
        $core->method('getParameterDirectory')->willReturn('parameters');
        (new ReflectionClass(Application::class))->getProperty('configuration')->setValue(null, $core);
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(
            null,
            new Configuration(null, false, 3, new AdapterConfiguration(AdapterMode::Skill, 'test', null, null, [])),
        );
        $skill = $this->createMock(AiSkill::class);
        $skill->expects(self::once())->method('generate')->willReturn($path);
        $adapter = $this->createMock(AiAdapter::class);
        $adapter->expects(self::once())->method('skill')->with(self::isInstanceOf(AdapterConfiguration::class), self::isInstanceOf(Instructions::class))->willReturn($skill);
        $adapter->expects(self::never())->method('client');
        $registry = new AdapterRegistry();
        $registry->register('test', $adapter);
        (new ReflectionClass(Ai::class))->getProperty('registry')->setValue(null, $registry);
        $output = $this->createMock(CliOutput::class);
        $output->expects($expected === Command::Success ? self::once() : self::never())->method('success')->with('Skill was generated successfully: ' . $path);
        $output->expects($expected === Command::Error ? self::once() : self::never())->method('error')->with('Skill generation failed.');

        self::assertSame($expected, (new ScenarioAiSkillCommand())->run($this->input(), $output));
    }

    /** @return iterable<string, array{?string, Command}> */
    public static function resultProvider(): iterable
    {
        yield 'success' => ['.agents/skills/stateforge-scenario/SKILL.md', Command::Success];
        yield 'failure' => [null, Command::Error];
    }
}
