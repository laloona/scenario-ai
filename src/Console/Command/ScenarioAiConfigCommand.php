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

use Stateforge\Scenario\Core\Console\Command\CliCommand;
use Stateforge\Scenario\Core\Console\Command\Command;
use Stateforge\Scenario\Core\Contract\CliInput;
use Stateforge\Scenario\Core\Contract\CliOutput;
use Stateforge\Scenario\Core\Runtime\Application;
use function file_get_contents;
use function file_put_contents;
use function is_file;
use const DIRECTORY_SEPARATOR;

final class ScenarioAiConfigCommand extends CliCommand
{
    public function description(): string
    {
        return 'Add the AI config for Scenario AI.';
    }

    protected function execute(CliInput $input, CliOutput $output): Command
    {
        $file = Application::getRootDir() . DIRECTORY_SEPARATOR .
            'vendor' . DIRECTORY_SEPARATOR .
            'stateforge' . DIRECTORY_SEPARATOR .
            'scenario-ai' . DIRECTORY_SEPARATOR .
            'blueprint' . DIRECTORY_SEPARATOR .
            'config.blueprint';

        if (is_file($file) === false) {
            $output->error('Config file generation failed.');
            return Command::Error;
        }

        $configFile = Application::getRootDir() . DIRECTORY_SEPARATOR . 'scenario-ai.dist.xml';
        if (is_file($configFile) === true) {
            $output->error('Config file already exists.');
            return Command::Error;
        }

        file_put_contents($configFile, file_get_contents($file));

        if (is_file($configFile) === false) {
            $output->error('Config file generation failed.');
            return Command::Error;
        }

        $output->success('Config file generated, please modify to your needs.');
        return Command::Success;
    }
}
