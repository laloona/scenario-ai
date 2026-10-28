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

final class AdapterConfiguration
{
    /**
     * @param array<string, string> $vars
     */
    public function __construct(
        public readonly string $adapter,
        public readonly ?string $class,
        public readonly ?string $path,
        public readonly array $vars,
    ) {
    }
}
