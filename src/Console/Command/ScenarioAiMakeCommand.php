<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Console\Command;

use Stateforge\Scenario\Ai\Console\CliConversation;
use Stateforge\Scenario\Core\Console\Command\CliCommand;
use Stateforge\Scenario\Core\Console\Command\Command;
use Stateforge\Scenario\Core\Contract\CliInput;
use Stateforge\Scenario\Core\Contract\CliOutput;

final class ScenarioAiMakeCommand extends CliCommand
{
    public function description(): string
    {
        return 'Make a scenario or parameter type with AI.';
    }

    protected function execute(CliInput $input, CliOutput $output): Command
    {
        return (new CliConversation('Scenario AI', 'Scenario framework'))->talk($output, null) === true
            ? Command::Success
            : Command::Error;
    }
}
