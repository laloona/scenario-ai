<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use Stateforge\Scenario\Ai\Console\CliApplication;
use Stateforge\Scenario\Ai\Console\Command\ScenarioAiConfigCommand;
use Stateforge\Scenario\Ai\Console\Command\ScenarioAiMakeCommand;
use Stateforge\Scenario\Ai\Console\Command\ScenarioAiSkillCommand;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Ai\Runtime\Configuration;
use Stateforge\Scenario\Ai\Runtime\ConfigurationBuilder;
use Stateforge\Scenario\Ai\Runtime\Exception\NoConfigException;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use Stateforge\Scenario\Core\Console\CliApplication as CoreCliApplication;
use Stateforge\Scenario\Core\Console\Command\CliCommand;
use Stateforge\Scenario\Core\Runtime\Application;
use function copy;
use function file_put_contents;
use function mkdir;

#[UsesClass(AdapterMode::class)]
#[CoversClass(CliApplication::class)]
#[UsesClass(ConfigurationBuilder::class)]
#[UsesClass(NoConfigException::class)]
#[UsesClass(Ai::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(AdapterConfiguration::class)]
#[Small]
final class CliApplicationTest extends FilesystemTestCase
{
    protected function tearDown(): void
    {
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, null);
        (new ReflectionClass(Application::class))->getProperty('rootDir')->setValue(null, null);
        parent::tearDown();
    }

    #[DataProvider('modeProvider')]
    public function testBootRegistersCommandsForConfiguredModeAndPreservesExistingCommands(AdapterMode $mode, bool $make, bool $skill): void
    {
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(
            null,
            new Configuration(null, false, 3, new AdapterConfiguration($mode, 'openai', null, null, [])),
        );
        $cli = new CoreCliApplication();
        $existing = self::createStub(CliCommand::class);
        $cli->command('custom', $existing);
        $extension = new CliApplication();
        $extension->boot($cli);
        $extension->boot($cli);

        $commands = (new ReflectionClass(CoreCliApplication::class))->getProperty('commands')->getValue($cli);
        self::assertIsArray($commands);
        self::assertCount(1 + (int) $make + (int) $skill, $commands);
        self::assertSame($existing, $commands['custom']);
        self::assertArrayNotHasKey('ai-config', $commands);
        if ($make) {
            self::assertInstanceOf(ScenarioAiMakeCommand::class, $commands['ai-make']);
        } else {
            self::assertArrayNotHasKey('ai-make', $commands);
        }
        if ($skill) {
            self::assertInstanceOf(ScenarioAiSkillCommand::class, $commands['ai-skill']);
        } else {
            self::assertArrayNotHasKey('ai-skill', $commands);
        }
    }

    /** @return iterable<string, array{AdapterMode, bool, bool}> */
    public static function modeProvider(): iterable
    {
        yield 'cli' => [AdapterMode::Cli, true, false];
        yield 'skill' => [AdapterMode::Skill, false, true];
        yield 'all' => [AdapterMode::All, true, true];
    }
    #[DataProvider('configurationFailures')]
    public function testRegistersOnlyConfigCommandWhenConfigurationCannotBeLoaded(string $failure): void
    {
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, null);
        (new ReflectionClass(Application::class))->getProperty('rootDir')->setValue(null, $this->testDirectory);
        if ($failure !== 'missing schema') {
            $schemaDir = $this->testDirectory . '/vendor/stateforge/scenario-ai/xsd';
            mkdir($schemaDir, 0777, true);
            copy(__DIR__ . '/../../../xsd/scenario-ai.xsd', $schemaDir . '/scenario-ai.xsd');
        }
        if ($failure === 'invalid configuration') {
            file_put_contents($this->testDirectory . '/scenario-ai.xml', '<scenario-ai>');
        }
        $cli = new CoreCliApplication();
        $existing = self::createStub(CliCommand::class);
        $cli->command('custom', $existing);
        (new CliApplication())->boot($cli);
        $commands = (new ReflectionClass(CoreCliApplication::class))->getProperty('commands')->getValue($cli);

        self::assertIsArray($commands);
        self::assertCount(2, $commands);
        self::assertSame($existing, $commands['custom']);
        self::assertInstanceOf(ScenarioAiConfigCommand::class, $commands['ai-config']);
        self::assertArrayNotHasKey('ai-make', $commands);
        self::assertArrayNotHasKey('ai-skill', $commands);
    }

    /** @return iterable<string, array{string}> */
    public static function configurationFailures(): iterable
    {
        yield 'missing schema' => ['missing schema'];
        yield 'missing config' => ['missing configuration'];
        yield 'invalid config' => ['invalid configuration'];
    }

}
