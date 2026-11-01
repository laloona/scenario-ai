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

use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Exception\ClassDoesNotExistException;
use function class_exists;

final readonly class ClassValidator implements Validator
{
    public function validate(AiResponse $response): void
    {
        foreach ($response->classes as $class) {
            if (class_exists($class) === false) {
                throw new ClassDoesNotExistException($class);
            }
        }

        if ($response->match !== null
            && class_exists($response->match) === false) {
            throw new ClassDoesNotExistException($response->match);
        }
    }
}
