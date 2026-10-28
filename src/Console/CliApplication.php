<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Console;

use Stateforge\Scenario\Ai\Console\Command\InstallScenarioAiCommand;
use Stateforge\Scenario\Ai\Console\Command\ScenarioAiMakeCommand;
use Stateforge\Scenario\Core\Console\CliApplication as CoreCliApplication;
use Stateforge\Scenario\Core\Console\CliApplicationExtension;

final class CliApplication implements CliApplicationExtension
{
    public function boot(CoreCliApplication $cli): void
    {
        $cli->command('ai-install', new InstallScenarioAiCommand());
        $cli->command('ai-make', new ScenarioAiMakeCommand());
    }
}
