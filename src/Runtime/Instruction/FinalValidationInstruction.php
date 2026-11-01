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

final class FinalValidationInstruction implements Instruction
{
    public function instruct(): string
    {
        return <<<Instruction
Final Validation

Before returning a response, verify the following:

1. The response uses the correct generation type.
2. The response belongs to the current task.
3. The original requirements have been preserved unless explicitly modified.
4. The mandatory existing implementation decision has been completed.
5. The selected response state matches the current conversation state.
6. If "match" is set, the existing implementation fully satisfies the user's requirements and the user has not explicitly requested a new implementation.
7. If “match” is set, “php” is null.
8. If PHP code is generated, the user has authorized generation.
9. If PHP code is generated, it fulfills the original or explicitly modified requirements.
10. The generated class name and description match the implemented functionality.
11. The generated implementation follows the selected blueprint.
12. The response contains exactly the required JSON fields.
Instruction;
    }
}
