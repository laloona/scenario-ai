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

final class GeneralInstructions implements Instruction
{
    public function __construct(
        private readonly string $docPath,
        private readonly int $maxQuestions,
        private readonly int $questionsAsked,
        private readonly ?string $projectDoc,
        private readonly ?string $parameterPath,
        private readonly bool $allowProjectInformation,
    ) {
    }

    public function instruct(AdapterMode $mode): string
    {
        $instruction = new Instructions();
        $instruction->add(new BaseInstruction());
        $instruction->add(new AttributeInstruction());
        $instruction->add(new DocumentationInstruction($this->docPath));
        if ($mode->isCli()) {
            $instruction->add(new QuestionLimitInstruction($this->maxQuestions, $this->questionsAsked));
        }
        $instruction->add(new ValueTypeInstruction());
        $instruction->add(new ParameterTypeListInstruction($this->parameterPath, $this->allowProjectInformation));

        if ($mode->isSkill()
            || $this->allowProjectInformation === true) {
            $instruction->add(new ScenarioListInstruction());
        }

        if ($mode->isCli()
            && $this->projectDoc !== null) {
            $instruction->add(new DocumentationInstruction($this->projectDoc));
        }

        return $instruction->instruct($mode);
    }
}
