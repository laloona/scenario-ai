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
use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
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

    public function instruct(AdapterMode $mode): string
    {
        $instruction = <<<INSTRUCTION
Existing Parameter Types

- Follow the mandatory pre-generation decision and response rules when evaluating existing Parameter Types.
- Before generating a ParameterType, examine the available Parameter Types.
- Prefer existing Parameter Types over duplicate implementations.

INSTRUCTION;

        if ($mode->isCli() === true) {
            $instruction .= <<<INSTRUCTION
- Request source code only when necessary to verify compatibility.

INSTRUCTION;
        }

        $instruction .= <<<INSTRUCTION
- Do not assume undocumented validation rules, conversion behavior, or supported value types.
- When generating a Scenario, reuse suitable existing Parameter Types if their compatibility and usage are documented.
- If no suitable ParameterType exists, generate a new implementation according to the selected blueprint.


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

            if ($mode->isCli()
                && $this->useProjectTypes === true
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
