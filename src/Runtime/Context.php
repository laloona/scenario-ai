<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime;

use Stateforge\Scenario\Ai\Contract\AiContext;
use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Contract\Validator;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Exception\ApplicationConfigNotInitializedException;
use Stateforge\Scenario\Ai\Runtime\Instruction\BlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ClassInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\FinalValidationInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\GeneralInstructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterBlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterImplementationInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ScenarioBlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Validation\ClassValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\CodeValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\Validators;
use Stateforge\Scenario\Core\Attribute\AsParameterType;
use Stateforge\Scenario\Core\Attribute\AsScenario;
use Stateforge\Scenario\Core\Contract\ScenarioInterface;
use Stateforge\Scenario\Core\ParameterType;
use Stateforge\Scenario\Core\ParameterTypeDefinition;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Scenario;
use const DIRECTORY_SEPARATOR;

final class Context implements AiContext
{
    public function instruction(?AiRequest $request): Instruction
    {
        $config = Application::config();
        if ($config === null) {
            throw new ApplicationConfigNotInitializedException();
        }

        $aiConfig = Ai::config();
        $path = Application::getRootDir() . DIRECTORY_SEPARATOR .
            'vendor' . DIRECTORY_SEPARATOR .
            'stateforge' . DIRECTORY_SEPARATOR .
            'scenario-core' . DIRECTORY_SEPARATOR;

        $instructions = new Instructions();
        $instructions->add(
            new GeneralInstructions(
                $path . 'docs',
                $aiConfig->maxQuestions,
                $request->counter ?? 0,
                $aiConfig->projectDoc === null
                    ? null
                    : Application::getRootDir() . DIRECTORY_SEPARATOR . $aiConfig->projectDoc,
                Application::getRootDir() . DIRECTORY_SEPARATOR . $config->getParameterDirectory(),
                $aiConfig->allowProjectInformation,
            ),
        );
        $instructions->add(new ClassInstruction('Base class definitions', [
            Scenario::class,
            ScenarioInterface::class,
            ParameterType::class,
            ParameterTypeDefinition::class,
        ]));
        $instructions->add(
            new BlueprintInstruction(
                new ScenarioBlueprintInstruction(
                    $path . 'blueprint' . DIRECTORY_SEPARATOR . 'scenario.blueprint',
                    $config->getSuites(),
                ),
                new ParameterBlueprintInstruction(
                    $path . 'blueprint' . DIRECTORY_SEPARATOR . 'parameter.blueprint',
                    $config->getParameterDirectory(),
                ),
            ),
        );
        $instructions->add(new ParameterImplementationInstruction());
        $instructions->add(new FinalValidationInstruction());

        return $instructions;
    }

    public function validator(): Validator
    {
        $validators = new Validators();
        $validators->add(new ClassValidator());
        $validators->add(new CodeValidator(
            Scenario::class,
            AsScenario::class,
            ParameterTypeDefinition::class,
            AsParameterType::class,
        ));

        return $validators;
    }
}
