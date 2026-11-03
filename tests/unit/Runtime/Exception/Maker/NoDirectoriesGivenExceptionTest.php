<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Exception\Maker;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Exception\Maker\NoDirectoriesGivenException;
use Stateforge\Scenario\Core\Runtime\Exception\Exception;

#[CoversClass(NoDirectoriesGivenException::class)]
#[Group('runtime')]
#[Small]
final class NoDirectoriesGivenExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new NoDirectoriesGivenException();

        self::assertSame(
            'No directories given to resolve.',
            $exception->getMessage(),
        );
        self::assertInstanceOf(Exception::class, $exception);
    }
}
