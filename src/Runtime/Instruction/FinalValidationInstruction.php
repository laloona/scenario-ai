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

final class FinalValidationInstruction implements Instruction
{
    public function instruct(AdapterMode $mode): string
    {
        return <<<Instruction
Final Validation

Before returning a response, verify the following:
1. The response uses the correct generation type.
2. The response belongs to the current task.
3. The original requirements have been preserved unless explicitly modified.
4. The mandatory existing implementation decision has been completed.
5. The selected response state matches the current conversation state.
6. If “match” is set, the existing implementation fully satisfies ALL requirements of the user and the user has not explicitly requested a new implementation.
7. Before returning “match”, compare the requested functionality and validation conditions with the existing implementation’s documented behavior.
8. An existing implementation MUST NOT be returned in “match” if any behavior contradicts the user’s requirements. Semantic similarity alone is insufficient. For example, past and future date validation are incompatible.
9. If compatibility cannot be established from the available information, request the relevant class definition instead of returning “match”.
10. If “match” is set, “php” is null.
11. If PHP code is generated, generation has been authorized by the mandatory pre-generation decision.
12. If PHP code is generated, it fulfills the original or explicitly modified requirements.
13. The generated class name and description match the implemented functionality.
14. The generated implementation follows the selected blueprint.
15. The response contains exactly the required five JSON fields.
16. If class definitions are requested, verify that their complete source code is not already available in the current task context.
17. Never include a class in the “classes” response field if its complete definition has already been provided through framework instructions, base class definitions, or previous conversation messages.
Instruction;
    }
}
