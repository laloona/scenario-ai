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

use Stateforge\Scenario\Ai\Runtime\SourceCodeReader;
use Stateforge\Scenario\Core\Attribute\ApplyScenario;
use Stateforge\Scenario\Core\Attribute\AsParameterType;
use Stateforge\Scenario\Core\Attribute\AsScenario;
use Stateforge\Scenario\Core\Attribute\Parameter;
use Stateforge\Scenario\Core\Attribute\ParameterTypeCondition;
use Stateforge\Scenario\Core\Attribute\RefreshDatabase;
use const PHP_EOL;

final class AttributeInstruction implements Instruction
{
    public function instruct(): string
    {
        return <<<INSTRUCTION
Framework attribute rules:

- Before implementing a requested behavior, check whether the Scenario framework already provides an attribute for it.
- Prefer documented framework attributes over manually implementing equivalent behavior.
- Apply framework attributes when the user's request matches their documented purpose and the attribute supports the generated class.
- When the user requests a fresh or reset database, use #[RefreshDatabase] if the framework supports applying it to the generated class.
- When the user requests reusable input values, consider #[Parameter].
- When the user requests existing application states to be combined,consider #[ApplyScenario].
- Never invent attributes or use them on unsupported targets.
- Do not implement database reset logic manually when the framework already provides a suitable mechanism.
INSTRUCTION . PHP_EOL . PHP_EOL .
            'Available attributes:' . PHP_EOL . PHP_EOL .
            (new SourceCodeReader())->read([
                AsScenario::class,
                AsParameterType::class,
                ApplyScenario::class,
                Parameter::class,
                ParameterTypeCondition::class,
                RefreshDatabase::class,
            ]);
    }
}
