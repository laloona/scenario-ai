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

use const PHP_EOL;

final class BlueprintInstruction implements Instruction
{
    public function __construct(
        private readonly ScenarioBlueprintInstruction $scenarioBlueprintInstruction,
        private readonly ParameterBlueprintInstruction $parameterBlueprintInstruction,
    ) {
    }

    public function instruct(): string
    {
        return <<<'INSTRUCTIONS'
Blueprint rules:

- Use only the blueprint corresponding to the selected generation type.
- The selected blueprint is authoritative for class structure, inheritance, attributes and method signatures.
- Documentation examples illustrate framework usage but MUST NOT override the selected blueprint.
- Preserve the required inheritance, attributes and method signatures.
- Replace all blueprint placeholders with valid PHP identifiers.
- Do not combine different blueprints.
- Generate syntactically valid PHP code.

Generated code quality rules:
- Generate the most complete implementation possible.
- Apply all supported framework features that can be determined from the user's request.
- Do not omit supported framework behavior merely because unrelated application-specific implementation details are missing.
- If executable application logic cannot be completed, generate an implementation draft with precise TODO comments.
- TODO comments must describe how to complete the implementation,not merely repeat the user's request.
- Never claim that an implementation draft is fully functional.
- The generated code must always remain syntactically valid.

Namespace rules:

Namespace rules:

- The selected blueprint defines the authoritative PHP namespace declaration.
- The %nameSpace% placeholder MUST be replaced using literal string substitution.
- Treat the configured namespace as an opaque string, not as a namespace to be interpreted or optimized.
- Copy the configured namespace into the placeholder character by character, preserving every namespace segment and its original order.
- Do NOT remove namespace segments, even if they appear redundant or already exist elsewhere in the namespace.
- Do NOT normalize, simplify, shorten or restructure the resulting namespace.
- Preserve every character outside the %nameSpace% placeholder exactly as defined in the blueprint.
- If no configured namespace is available, choose an appropriate replacement value.
- The final namespace MUST be identical to the original blueprint namespace after literal substitution of %nameSpace%.
- Before returning the generated PHP code, verify that the namespace matches this substitution exactly.

INSTRUCTIONS . PHP_EOL . PHP_EOL .
        $this->scenarioBlueprintInstruction->instruct() . PHP_EOL . PHP_EOL .
        $this->parameterBlueprintInstruction->instruct() . PHP_EOL . PHP_EOL;
    }
}
