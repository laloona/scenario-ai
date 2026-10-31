<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stateforge\Scenario\Ai\Runtime\Exception\ResponseException;
use Stateforge\Scenario\Core\Runtime\Exception\Exception;

#[CoversClass(ResponseException::class)]
#[Group('runtime')]
#[Small]
final class ResponseExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $previous = new RuntimeException('Original failure');
        $exception = new class ('Invalid response', 42, $previous) extends ResponseException {};

        self::assertInstanceOf(Exception::class, $exception);
        self::assertSame('Invalid response', $exception->getMessage());
        self::assertSame(42, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }
}
