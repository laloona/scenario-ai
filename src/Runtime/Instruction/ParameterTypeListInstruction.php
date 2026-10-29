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

use ReflectionClass;
use Stateforge\Scenario\Core\ParameterType;
use Stateforge\Scenario\Core\Runtime\Metadata\Parameter\ParameterTypeRegistry;
use function count;
use function realpath;
use function rtrim;
use function str_starts_with;
use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

final class ParameterTypeListInstruction implements Instruction
{
    public function __construct(
        private readonly ?string $customPath,
        private readonly bool $useProjectTypes,
    ) {
    }

    public function instruct(): string
    {
        $instruction = <<<INSTRUCTION
Existing Parameter Types

- The project may provide a list of existing Parameter Types, including their fully qualified class names and descriptions.
- Before generating a new Parameter Type or using a Parameter Type in a Scenario, examine the available Parameter Types to determine whether an existing implementation supports the required value type or conversion.
- Prefer using existing Parameter Types over creating duplicate implementations.
- If an existing Parameter Type appears suitable but its description does not provide sufficient information about its behavior or usage, request access to its source code.
- Only request source code when necessary to verify compatibility or determine how the Parameter Type should be used.
- Do not assume supported value types, conversion behavior, validation rules, methods, or constructor arguments that have not been documented or verified.
- When generating a Scenario, use an existing Parameter Type if it provides the required functionality and is compatible with the Scenario framework's parameter handling mechanism.
- If an existing Parameter Type already fulfills the user's requirements, inform the user instead of generating a duplicate implementation.
- If no suitable Parameter Type exists, generate a new implementation according to the framework instructions.


INSTRUCTION;

        $instruction .= 'Built-in parameter types' . PHP_EOL . PHP_EOL;
        $instruction .= ParameterType::class . '::Boolean: ' . ParameterType::Boolean->value . PHP_EOL;
        $instruction .= ParameterType::class . '::Float: ' . ParameterType::Float->value . PHP_EOL;
        $instruction .= ParameterType::class . '::Integer: ' . ParameterType::Integer->value . PHP_EOL;
        $instruction .= ParameterType::class . '::String: ' . ParameterType::String->value . PHP_EOL;

        $parameterTypes = ParameterTypeRegistry::getInstance()->all();
        if (count($parameterTypes) > 0) {
            $packageTypes = [];
            $projectTypes = [];
            $customPath = $this->customPath === null ? false : realpath($this->customPath);
            if ($customPath === false) {
                $packageTypes = $parameterTypes;
            } else {
                foreach ($parameterTypes as $class => $parameterType) {
                    /** @var class-string $class Registered keys are validated by ParameterTypeRegistry::register(). */
                    $reflection = new ReflectionClass($class);
                    $fileName = $reflection->getFileName();

                    if ($fileName === false) {
                        continue;
                    }

                    if (str_starts_with($fileName, rtrim($customPath, '/\\') . DIRECTORY_SEPARATOR) === true) {
                        $projectTypes[$class] = $parameterType;
                        continue;
                    }

                    $packageTypes[$class] = $parameterType;
                }
            }

            if (count($packageTypes) > 0) {
                $instruction .= PHP_EOL . 'Package parameter types' . PHP_EOL . PHP_EOL;
                foreach ($packageTypes as $class => $parameterType) {
                    $instruction .= $class . ': ' . $parameterType->description . PHP_EOL;
                }
            }

            if ($this->useProjectTypes === true
                && count($projectTypes) > 0) {
                $instruction .= PHP_EOL . 'Project parameter types' . PHP_EOL . PHP_EOL;
                foreach ($projectTypes as $class => $parameterType) {
                    $instruction .= $class . ': ' . $parameterType->description . PHP_EOL;
                }
            }
        }

        return $instruction;
    }
}
