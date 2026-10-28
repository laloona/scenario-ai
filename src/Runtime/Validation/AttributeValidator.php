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

use Stateforge\Scenario\Ai\Runtime\Exception\AttributeValidatorException;
use Stateforge\Scenario\Ai\Runtime\ScenarioAst;

final readonly class AttributeValidator
{
    public function __construct(
        private AttributeFinder $finder,
        private string $attribute,
    ) {
    }

    public function validate(ScenarioAst $ast): void
    {
        if ($this->finder->hasAttribute($ast->getClass()->attrGroups, $this->attribute) === false) {
            throw new AttributeValidatorException($this->attribute);
        }
    }
}
