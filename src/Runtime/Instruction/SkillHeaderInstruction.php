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

final class SkillHeaderInstruction implements Instruction
{
    public function instruct(AdapterMode $mode): string
    {
        return <<<'INSTRUCTION'
---
name: stateforge-scenario
description: Create and reuse Stateforge Scenario scenarios and parameter types in PHP projects.
---
INSTRUCTION;
    }
}
