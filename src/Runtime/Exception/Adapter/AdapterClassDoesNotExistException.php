<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Exception\Adapter;

use function sprintf;

final class AdapterClassDoesNotExistException extends AdapterException
{
    public function __construct(string $class)
    {
        parent::__construct(
            sprintf(
                'Adapter class "%s" does not exist',
                $class,
            ),
        );
    }
}
