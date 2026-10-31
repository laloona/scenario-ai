<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Exception;

use function get_debug_type;
use function is_string;
use function sprintf;

final class WrongResponseTypeException extends ResponseException
{
    public function __construct(mixed $value, string $expected)
    {
        parent::__construct(
            sprintf(
                'wrong response type: got value %s and expected %s.',
                is_string($value) === true ? $value : get_debug_type($value),
                $expected,
            ),
        );
    }
}
