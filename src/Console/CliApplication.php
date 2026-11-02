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

use Stateforge\Scenario\Ai\Console\Command\ScenarioAiConfigCommand;
use Stateforge\Scenario\Ai\Console\Command\ScenarioAiMakeCommand;
use Stateforge\Scenario\Ai\Console\Command\ScenarioAiSkillCommand;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Core\Console\CliApplication as CoreCliApplication;
use Stateforge\Scenario\Core\Console\CliApplicationExtension;
use Throwable;

final class CliApplication implements CliApplicationExtension
{
    public function boot(CoreCliApplication $cli): void
    {
        try {
            $config = Ai::config();
            if ($config->adapter->mode->isCli()) {
                $cli->command('ai-make', new ScenarioAiMakeCommand());
            }
            if ($config->adapter->mode->isSkill()) {
                $cli->command('ai-skill', new ScenarioAiSkillCommand());
            }
        } catch (Throwable $exception) {
            $cli->command('ai-config', new ScenarioAiConfigCommand());
        }
    }
}
