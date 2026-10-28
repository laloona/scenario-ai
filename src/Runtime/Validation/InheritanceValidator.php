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

use Stateforge\Scenario\Ai\Runtime\Exception\InheritanceValidatorException;
use Stateforge\Scenario\Ai\Runtime\Exception\NoExistingParentClassException;
use Stateforge\Scenario\Ai\Runtime\Exception\NoInheritanceException;
use Stateforge\Scenario\Ai\Runtime\ScenarioAst;
use function class_exists;
use function is_subclass_of;
use function ltrim;
use function strcasecmp;

final readonly class InheritanceValidator
{
    public function __construct(
        private string $expectedBaseClass,
    ) {
    }

    public function validate(ScenarioAst $ast): void
    {
        $class = $ast->getClass();
        if ($class->extends === null) {
            throw new NoInheritanceException();
        }

        $parentClass = ltrim($class->extends->toString(), '\\');
        $expectedBaseClass = ltrim($this->expectedBaseClass, '\\');

        if (class_exists($parentClass) === false) {
            throw new NoExistingParentClassException($parentClass);
        }

        // Direct inheritance
        if (strcasecmp($parentClass, $expectedBaseClass) === 0) {
            return;
        }

        // Indirect inheritance
        if (is_subclass_of($parentClass, $expectedBaseClass) === false) {
            throw new InheritanceValidatorException($parentClass, $expectedBaseClass);
        }
    }
}
