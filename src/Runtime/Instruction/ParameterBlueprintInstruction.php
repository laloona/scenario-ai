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
ParameterType Rules

- A ParameterType MUST extend ParameterTypeDefinition as defined by the selected blueprint.
- A ParameterType MUST use the #[AsParameterType] attribute.
- The attribute description MUST explain the validation or conversion behavior.
- Implement the validation behavior requested by the user.
- Implement the appropriate value conversion.
- Never generate validation rules unrelated to the user’s request.
- Never derive validation behavior from unrelated existing Parameter Types.
- Never reuse an unrelated class name from previous tasks.

INSTRUCTIONS;
        if ($this->parameterDirectory !== null) {
            $instructions .= PHP_EOL . 'Configured Parameter Types: ';
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

INSTRUCTIONS .
            'Parameter Type Blueprint: ' . PHP_EOL . file_get_contents($this->parameterPath);

        return $instructions;
    }
}
