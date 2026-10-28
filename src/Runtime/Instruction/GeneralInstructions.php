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

final class GeneralInstructions implements Instruction
{
    public function __construct(
        private readonly string $docPath,
        private readonly int $maxQuestions,
        private readonly int $questionsAsked,
        private readonly ?string $projectDoc,
    ) {
    }

    public function instruct(): string
    {
        $instruction =
            (new BaseInstruction())->instruct() . PHP_EOL . PHP_EOL .
            (new AttributeInstruction())->instruct() . PHP_EOL . PHP_EOL .
            (new DocumentationInstruction($this->docPath))->instruct() . PHP_EOL . PHP_EOL .
            (new QuestionLimitInstruction($this->maxQuestions, $this->questionsAsked))->instruct() . PHP_EOL . PHP_EOL .
            (new ValueTypeInstruction())->instruct() . PHP_EOL . PHP_EOL;

        if ($this->projectDoc !== null) {
            $instruction .= (new DocumentationInstruction($this->projectDoc))->instruct() . PHP_EOL . PHP_EOL;
        }

        return $instruction;
    }
}
