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

use Stateforge\Scenario\Ai\Console\CliSkill;
use Stateforge\Scenario\Core\Console\Command\CliCommand;
use Stateforge\Scenario\Core\Console\Command\Command;
use Stateforge\Scenario\Core\Contract\CliInput;
use Stateforge\Scenario\Core\Contract\CliOutput;

final class ScenarioAiSkillCommand extends CliCommand
{
    public function description(): string
    {
        return 'Makes a skill for IDE usage.';
    }

    protected function execute(CliInput $input, CliOutput $output): Command
    {
        return (new CliSkill())->generate($output, null) === true
            ? Command::Success
            : Command::Error;
    }
}
