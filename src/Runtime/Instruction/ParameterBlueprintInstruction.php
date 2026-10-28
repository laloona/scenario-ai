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

use Stateforge\Scenario\Core\Runtime\NamespaceResolver;
use function file_get_contents;
use const PHP_EOL;

final class ParameterBlueprintInstruction implements Instruction
{
    public function __construct(
        private readonly string $parameterPath,
        private readonly ?string $parameterDirectory,
    ) {
    }

    public function instruct(): string
    {
        $instructions = <<<'INSTRUCTIONS'
Blueprint ParameterType rules:

- A ParameterType MUST extend ParameterTypeDefinition as defined by the selected blueprint.
- A ParameterType MUST use the #[AsParameterType] attribute.
- The attribute description MUST explain the validation or conversion behavior.

INSTRUCTIONS;
        if ($this->parameterDirectory !== null) {
            $instructions .= PHP_EOL . 'Configured Parameter Type: ';
            $instructions .= PHP_EOL . "\t" .
                '- [ directory: ' . $this->parameterDirectory . ', namespace: ' . (new NamespaceResolver())->toNamespace($this->parameterDirectory) . ' ]';
        }
        $instructions .= <<<'INSTRUCTIONS'


ParameterType namespace substitution example:

Blueprint: namespace Stateforge\Suite\%nameSpace%;
Configured namespace: Scenario\Parameter
Correct result: namespace Stateforge\Suite\Scenario\Parameter;
Incorrect result: namespace Stateforge\Suite\Parameter;
The configured namespace MUST be inserted without removing or interpreting any of its segments.

ParameterType implementation rules:

- The cast() method MUST implement the requested validation and conversion behavior.
- The cast() method MUST return a value compatible with its declared return type.
- Invalid or unsupported input values MUST be handled explicitly.
- The valueType() method MUST return an instance of the appropriate ValueType class.
- NEVER return a class name string from valueType().
- Pass the required constructor arguments when creating ValueType instances.
- The selected ValueType MUST be consistent with the values returned by cast().
- Both cast() and valueType() MUST contain complete implementations.
- Never replace required validation or conversion logic with placeholder comments.
- If implementation requires unavailable project-specific dependencies, request the necessary information.
- If meaningful implementation is impossible, return the "Unable to generate" response state.
INSTRUCTIONS .
            'Parameter Type Blueprint: ' . PHP_EOL . file_get_contents($this->parameterPath);

        return $instructions;
    }
}
