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

final class QuestionLimitInstruction implements Instruction
{
    public function __construct(
        private readonly int $maxQuestions,
        private readonly int $questionsAsked,
    ) {
    }

    public function instruct(): string
    {
        return <<<INSTRUCTIONS
Conversation rules:

- Maximum clarification questions: {$this->maxQuestions}
- Clarification questions already asked: {$this->questionsAsked}
- Ask clarification questions only when essential information cannot be obtained from the available project classes or documentation.
- Prefer requesting relevant class definitions over asking the user to describe an existing implementation.
- Ask only one specific clarification question per response.
- Never exceed the configured clarification question limit.
- Requests for class definitions are not clarification questions.
- If the question limit has been reached, do not ask further clarification questions.
- Reaching the question limit does not permit inventing missing project-specific behavior.
- If the question limit has been reached, generate the most complete implementation possible using the available information.
- If application-specific behavior cannot be implemented safely, generate an implementation draft with actionable TODO comments.
- Never invent missing project-specific APIs to produce executable code.
INSTRUCTIONS;
    }
}
