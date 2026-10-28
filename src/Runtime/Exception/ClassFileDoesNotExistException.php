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

use Stateforge\Scenario\Core\Runtime\Exception\Exception;
use function sprintf;

final class ClassFileDoesNotExistException extends Exception
{
    public function __construct(string $class)
    {
        parent::__construct(
            sprintf(
                'class file for class "%s" does not exist.',
                $class,
            ),
        );
    }
}
