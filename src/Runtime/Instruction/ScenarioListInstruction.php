<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Instruction;

use Stateforge\Scenario\Core\Runtime\ScenarioRegistry;
use function count;
use function implode;
use function json_encode;
use function sprintf;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;

final class ScenarioListInstruction implements Instruction
{
    public function instruct(): string
    {
        $scenarios = ScenarioRegistry::getInstance()->all();
        if (count($scenarios) === 0) {
            return '';
        }

        $instruction = 'Registered Scenarios' . PHP_EOL . PHP_EOL;
        $listed = [];
        foreach ($scenarios as $scenario) {
            if (isset($listed[$scenario->class])) {
                continue;
            }
            $listed[$scenario->class] = true;
            $parameters = [];
            foreach ($scenario->parameters as $parameter) {
                $parameters[] = sprintf(
                    "\t\t" . 'parameter: name: %s, description: %s, type: %s, required: %s, repeatable: %s, default: %s',
                    $parameter->name,
                    $parameter->description,
                    $parameter->type->name,
                    $parameter->required === true ? 'yes' : 'no',
                    $parameter->repeatable === true ? 'yes' : 'no',
                    json_encode($parameter->default, JSON_THROW_ON_ERROR),
                );
            }

            $instruction .= sprintf(
                '- %s: name: %s, description: %s, suites: %s, parameters: %s',
                $scenario->class,
                $scenario->attribute->name,
                $scenario->attribute->description,
                $scenario->suite,
                implode(PHP_EOL, $parameters),
            ) . PHP_EOL;
        }

        return $instruction;
    }
}
