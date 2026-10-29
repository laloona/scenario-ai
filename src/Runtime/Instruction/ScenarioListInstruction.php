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

        $instruction = <<<INSTRUCTION
Existing Scenarios

- The project provides a list of existing Scenarios, including their fully qualified class names and descriptions.
- Before generating a new Scenario, examine the available Scenarios to determine whether an existing implementation already provides the requested functionality or could support the new Scenario.
- Prefer reusing existing Scenarios over duplicating their functionality, provided that the Scenario framework supports the intended reuse.
- If an existing Scenario appears relevant but its description does not provide sufficient information, request access to its source code.
- Only request source code when necessary to determine whether and how an existing Scenario can be reused.
- Do not assume the existence of methods, properties, constructor arguments, or mechanisms for executing or composing existing Scenarios.
- Only reuse existing Scenarios when their compatibility and the required integration mechanism can be established from the available information.
- If an existing Scenario already fulfills the user's requirements, inform the user instead of generating a duplicate implementation.
- If no suitable Scenario exists, generate a new implementation according to the framework instructions.


INSTRUCTION;

        $instruction .= 'Registered Scenarios' . PHP_EOL . PHP_EOL;
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
