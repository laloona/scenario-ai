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

use Stateforge\Scenario\Ai\Contract\AiAdapter;
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\AdapterAlreadyRegisteredException;
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\UnknownAdapterException;

final class AdapterRegistry
{
    /** @var array<string, AiAdapter> */
    private array $adapters = [];

    public function register(string $name, AiAdapter $adapter): void
    {
        if (isset($this->adapters[$name])) {
            throw new AdapterAlreadyRegisteredException($name);
        }

        $this->adapters[$name] = $adapter;
    }

    public function get(string $name): AiAdapter
    {
        return $this->adapters[$name]
            ?? throw new UnknownAdapterException($name);
    }
}
