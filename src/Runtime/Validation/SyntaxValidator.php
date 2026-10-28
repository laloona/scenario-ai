<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Validation;

use PhpParser\Error;
use PhpParser\ParserFactory;
use Stateforge\Scenario\Ai\Runtime\Exception\InvalidGeneratedCodeException;

final class SyntaxValidator
{
    public function validate(string $generatedCode): void
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        try {
            $parser->parse($generatedCode);
        } catch (Error $exception) {
            throw new InvalidGeneratedCodeException(
                $exception->getMessage(),
                previous: $exception,
            );
        }
    }
}
