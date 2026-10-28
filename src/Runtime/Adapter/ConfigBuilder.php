<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Adapter;

use Stateforge\Scenario\Ai\Runtime\Exception\ConfigBuilderException;
use Throwable;

final class ConfigBuilder
{
    /**
     * @template T of object
     * @param class-string<T> $configClass
     * @param array<string, string> $values
     * @return T
     */
    public function build(
        string $configClass,
        array $values,
    ): object {
        try {
            return new $configClass(...$values);
        } catch (Throwable $exception) {
            throw new ConfigBuilderException($configClass, $exception->getMessage());
        }
    }
}
