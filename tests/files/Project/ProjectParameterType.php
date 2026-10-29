<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Files\Project;

use Stateforge\Scenario\Core\ParameterTypeDefinition;
use Stateforge\Scenario\Core\Runtime\Metadata\ValueType\StringType;
use function is_string;

final class ProjectParameterType extends ParameterTypeDefinition
{
    public function cast(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    protected function valueType(mixed $value): StringType
    {
        return new StringType($value);
    }
}
