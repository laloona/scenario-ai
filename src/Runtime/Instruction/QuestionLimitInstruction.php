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

final class QuestionLimitInstruction implements Instruction
{
    public function __construct(
        private readonly int $maxQuestions,
        private readonly int $questionsAsked,
    ) {
    }

    public function instruct(AdapterMode $mode): string
    {
        if ($mode->isSkill()) {
            return '';
        }

        return <<<INSTRUCTIONS
Conversation rules:

- Maximum clarification questions: {$this->maxQuestions}
- Clarification questions already asked: {$this->questionsAsked}
- Respect the provided clarification question limit and current question count.
- Ask clarification questions only when essential information cannot be obtained from the available project classes or documentation.
- Prefer requesting relevant class definitions over asking the user to describe an existing implementation.
- Ask only one specific clarification question per response.
- Never exceed the configured clarification question limit.
- Requests for existing class definitions are not clarification questions.
- If the question limit has been reached, do not ask further clarification questions.
- Reaching the question limit does not permit inventing missing project-specific behavior.
- If sufficient information is available, generate a complete implementation.
- If essential information is missing and no further clarification is permitted, return a response with all result fields set to null, except "type", which must preserve the requested generation type, and "classes", which must be an empty array.
- Never generate incomplete or speculative implementations to compensate for missing information.
- Never invent missing project-specific APIs to produce executable code.
INSTRUCTIONS;
    }
}
