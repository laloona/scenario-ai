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
use Stateforge\Scenario\Core\Runtime\Metadata\ValueType\BooleanType;
use Stateforge\Scenario\Core\Runtime\Metadata\ValueType\FloatType;
use Stateforge\Scenario\Core\Runtime\Metadata\ValueType\IntegerType;
use Stateforge\Scenario\Core\Runtime\Metadata\ValueType\StringType;
use const PHP_EOL;

final class ValueTypeInstruction implements Instruction
{
    public function instruct(): string
    {
        return 'Some class definitions: ' . PHP_EOL . PHP_EOL .
            (new SourceCodeReader())->read([
                BooleanType::class,
                FloatType::class,
                IntegerType::class,
                StringType::class,
            ]);
    }
}
