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

use PhpParser\Node\AttributeGroup;
use function ltrim;
use function strcasecmp;

final class AttributeFinder
{
    /**
     * @param AttributeGroup[] $groups
     */
    public function hasAttribute(
        array $groups,
        string $attributeClass,
    ): bool {
        $attributeClass = ltrim($attributeClass, '\\');

        foreach ($groups as $group) {
            foreach ($group->attrs as $attribute) {
                $name = ltrim($attribute->name->toString(), '\\');

                if (strcasecmp($name, $attributeClass) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
