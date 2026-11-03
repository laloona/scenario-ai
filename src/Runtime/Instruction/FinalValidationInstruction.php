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
use function implode;
use const PHP_EOL;

final class FinalValidationInstruction implements Instruction
{
    public function instruct(AdapterMode $mode): string
    {
        $rules = [
                'The response uses the correct generation type.',
                'The response belongs to the current task.',
                'The original requirements have been preserved unless explicitly modified.',
                'The mandatory existing implementation decision has been completed.',
            ];
        if ($mode->isCli() === true) {
            $rules[] = 'The selected response state matches the current conversation state.';
            $rules[] = 'If “match” is set, the existing implementation fully satisfies ALL requirements of the user and the user has not explicitly requested a new implementation.';
            $rules[] = 'Before returning “match”, compare the requested functionality and validation conditions with the existing implementation’s documented behavior.';
            $rules[] = 'An existing implementation MUST NOT be returned in “match” if any behavior contradicts the user’s requirements. Semantic similarity alone is insufficient. For example, past and future date validation are incompatible.';
            $rules[] = 'If compatibility cannot be established from the available information, request the relevant class definition instead of returning “match”.';
            $rules[] = 'If “match” is set, “php” is null.';
        }

        $rules[] = 'If PHP code is generated, generation has been authorized by the mandatory pre-generation decision.';
        $rules[] = 'If PHP code is generated, it fulfills the original or explicitly modified requirements.';
        $rules[] = 'The generated class name and description match the implemented functionality.';
        $rules[] = 'The generated implementation follows the selected blueprint.';
        if ($mode->isCli() === true) {
            $rules[] = 'The response contains exactly the required five JSON fields.';
            $rules[] = 'If class definitions are requested, verify that their complete source code is not already available in the current task context.';
            $rules[] = 'Never include a class in the “classes” response field if its complete definition has already been provided through framework instructions, base class definitions, or previous conversation messages.';
        }

        foreach ($rules as $i => $rule) {
            $rules[$i] = ($i+1) . '. ' . $rule;
        }

        return <<<INSTRUCTION
Final Validation

Before returning a response, verify the following:

INSTRUCTION . implode(PHP_EOL, $rules);
    }
}
