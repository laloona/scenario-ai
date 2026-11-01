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

final class ParameterImplementationInstruction implements Instruction
{
    public function instruct(AdapterMode $mode): string
    {
        return <<<Instruction
ParameterType implementation rules:

- The cast() method MUST implement the requested validation and conversion behavior.
- The cast() method MUST return a value compatible with its declared return type.
- Invalid or unsupported input values MUST be handled explicitly.
- The valueType() method MUST return an instance of the appropriate ValueType class.
- NEVER return a class name string from valueType().
- Pass the required constructor arguments when creating ValueType instances.
- The selected ValueType MUST be consistent with the values returned by cast().
- Both cast() and valueType() MUST contain complete implementations.
- Never replace required validation or conversion logic with placeholder comments.
- If implementation requires unavailable project-specific dependencies, request the necessary information.
- If meaningful implementation is impossible, return the "Unable to generate" response state.
Instruction;
    }
}
