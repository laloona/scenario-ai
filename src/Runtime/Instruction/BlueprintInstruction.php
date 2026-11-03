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

use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use const PHP_EOL;

final class BlueprintInstruction implements Instruction
{
    public function __construct(
        private readonly ScenarioBlueprintInstruction $scenarioBlueprintInstruction,
        private readonly ParameterBlueprintInstruction $parameterBlueprintInstruction,
    ) {
    }

    public function instruct(AdapterMode $mode): string
    {
        $instructions = <<<'INSTRUCTIONS'
Blueprint Rules

- Use only the blueprint corresponding to the selected generation type.
- The selected blueprint is authoritative for class structure, inheritance, attributes, and method signatures.
- Documentation examples illustrate framework usage but MUST NOT override the selected blueprint.
- Preserve the required inheritance, attributes, and method signatures.
- Replace all blueprint placeholders with valid PHP identifiers.
- Do not combine different blueprints.
- Generate syntactically valid PHP code.
- Never copy placeholder class names, descriptions, or behavior from the blueprint into the final implementation.
- The generated implementation MUST fulfill the user’s requirements.

Generated Code Quality Rules

- Generate complete implementations of the requested behavior.
- Apply all supported framework features that can be determined from the user’s requirements.
- Do not omit supported framework behavior merely because unrelated application-specific details are unavailable.
- Do not generate TODO-based implementations for missing essential behavior.
- Never claim an incomplete implementation is fully functional.
- The generated code MUST be syntactically valid.
- The generated class name MUST describe the requested functionality.
- The generated description MUST describe the implemented behavior.
- Before returning PHP code, verify that the class name, description, validation logic, and implementation behavior are consistent with the original request.

Namespace Rules

- The selected blueprint defines the authoritative PHP namespace declaration.

INSTRUCTIONS;

        if ($mode->isCli() === true) {
            $instructions .= <<<'INSTRUCTIONS'
- The %nameSpace% placeholder MUST be replaced using literal string substitution.
- Treat the configured namespace as an opaque string.
- Copy the configured namespace into the placeholder character by character, preserving every namespace segment and its original order.

INSTRUCTIONS;
        }

        $instructions .= <<<'INSTRUCTIONS'
- Do NOT remove namespace segments, even if they appear redundant.
- Do NOT normalize, simplify, shorten, or restructure the resulting namespace.
- Preserve every character outside the %nameSpace% placeholder exactly as defined in the blueprint.
- If no configured namespace is available, choose an appropriate replacement value.
- The final namespace MUST be identical to the original blueprint namespace after literal substitution of %nameSpace%.
- Before returning generated PHP code, verify that the namespace matches this substitution exactly.
INSTRUCTIONS . PHP_EOL .
        $this->scenarioBlueprintInstruction->instruct($mode) . PHP_EOL . PHP_EOL .
        $this->parameterBlueprintInstruction->instruct($mode) . PHP_EOL . PHP_EOL;

        return $instructions;
    }
}
