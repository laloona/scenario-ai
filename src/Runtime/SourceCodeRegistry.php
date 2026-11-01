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

use function in_array;

final class SourceCodeRegistry
{
    /**
     * @var list<class-string>
     */
    private static array $classes = [];

    /**
     * @param class-string $class
     */
    public function addClass(string $class): void
    {
        if ($this->hasClass($class)) {
            return;
        }

        self::$classes[] = $class;
    }

    public function hasClass(string $class): bool
    {
        return in_array($class, self::$classes, true);
    }
}
