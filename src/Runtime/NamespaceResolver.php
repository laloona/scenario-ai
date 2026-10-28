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

use Stateforge\Scenario\Core\Runtime\NamespaceResolver as CoreNamespaceResolver;
use function array_map;
use function explode;
use function implode;
use function lcfirst;
use function trim;

final class NamespaceResolver
{
    public function toNamespace(string $directory): string
    {
        return (new CoreNamespaceResolver())->toNamespace($directory);
    }

    public function toPath(string $namespace): string
    {
        return implode('/', array_map(
            static fn (string $part): string => lcfirst($part),
            explode('\\', trim($namespace, '\\')),
        ));
    }
}
