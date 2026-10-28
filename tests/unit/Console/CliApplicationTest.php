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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stateforge\Scenario\Ai\Console\CliApplication;
use Stateforge\Scenario\Ai\Console\Command\InstallScenarioAiCommand;
use Stateforge\Scenario\Ai\Console\Command\ScenarioAiMakeCommand;
use Stateforge\Scenario\Core\Console\CliApplication as CoreCliApplication;
use Stateforge\Scenario\Core\Console\Command\CliCommand;

#[CoversClass(CliApplication::class)]
#[Group('console')]
#[Small]
final class CliApplicationTest extends TestCase
{
    public function testBootRegistersAiCommandsAndPreservesExistingCommands(): void
    {
        $cli = new CoreCliApplication();
        $existing = self::createStub(CliCommand::class);
        $cli->command('custom', $existing);

        (new CliApplication())->boot($cli);

        $commands = (new ReflectionClass(CoreCliApplication::class))->getProperty('commands')->getValue($cli);
        self::assertIsArray($commands);
        self::assertCount(3, $commands);
        self::assertSame($existing, $commands['custom']);
        self::assertInstanceOf(InstallScenarioAiCommand::class, $commands['ai-install']);
        self::assertInstanceOf(ScenarioAiMakeCommand::class, $commands['ai-make']);
    }

    public function testRepeatedBootDoesNotDuplicateCommands(): void
    {
        $cli = new CoreCliApplication();
        $extension = new CliApplication();
        $extension->boot($cli);
        $extension->boot($cli);

        $commands = (new ReflectionClass(CoreCliApplication::class))->getProperty('commands')->getValue($cli);
        self::assertIsArray($commands);
        self::assertCount(2, $commands);
        self::assertInstanceOf(InstallScenarioAiCommand::class, $commands['ai-install']);
        self::assertInstanceOf(ScenarioAiMakeCommand::class, $commands['ai-make']);
    }
}
