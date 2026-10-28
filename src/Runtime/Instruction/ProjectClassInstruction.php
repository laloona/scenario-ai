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

final class ProjectClassInstruction
{
    public function instruct(): string
    {
        return <<<'INSTRUCTIONS'
Project class rules:

- Distinguish between framework classes and application-specific classes.
- Classes within the Stateforge\Scenario namespace belong to the Scenario framework and its adapters.
- The provided framework documentation and class definitions are authoritative for framework APIs.
- Use documented framework classes without requesting their source code when the documented API is sufficient.
- Request framework class definitions only when essential API information is missing and the class is explicitly listed as available.
- The available project class list contains class names, not necessarily their implementations.
- Only request class definitions explicitly listed as available.
- Request class definitions only when their implementation details are essential to generate correct code.
- Prefer requesting application-specific class definitions when their behavior, methods or constructor signatures are required.
- Request only the minimum number of class definitions necessary for the current task.
- Add requested fully qualified class names to the classes array.
- Never request a class definition that has already been provided.
- Never request a class definition that the user has declined to share.
- Treat declined class definitions as unavailable.
- Never infer undocumented methods, properties, constructor signatures or behavior from class names alone.
- If a required class definition is unavailable, do not invent its implementation or API.
INSTRUCTIONS;
    }
}
