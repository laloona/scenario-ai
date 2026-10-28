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

final class Instructions implements Instruction
{
    /**
     * @var list<Instruction>
     */
    private array $instructions = [];

    public function add(Instruction $instruction): void
    {
        $this->instructions[] = $instruction;
    }

    public function instruct(): string
    {
        $result = '';
        foreach ($this->instructions as $instruction) {
            $result .= $instruction->instruct() . PHP_EOL . PHP_EOL;
        }
        return $result;
    }
}
