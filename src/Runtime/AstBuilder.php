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

use PhpParser\ParserFactory;
use RuntimeException;

final class AstBuilder
{
    public function build(string $phpCode): ScenarioAst
    {
        $ast = (new ParserFactory())
            ->createForNewestSupportedVersion()
            ->parse($phpCode);

        if ($ast === null) {
            throw new RuntimeException('Unable to parse PHP code.');
        }

        return new ScenarioAst($ast);
    }
}
