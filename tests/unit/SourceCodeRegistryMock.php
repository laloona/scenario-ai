<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit;

use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\SourceCodeRegistry;

trait SourceCodeRegistryMock
{
    private function resetSourceCodeRegistry(): void
    {
        (new ReflectionClass(SourceCodeRegistry::class))->getProperty('classes')->setValue(null, []);
    }
}
