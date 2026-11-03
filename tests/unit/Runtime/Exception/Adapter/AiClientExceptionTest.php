<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Exception\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\AiClientException;
use Stateforge\Scenario\Core\Runtime\Exception\Exception;

#[CoversClass(AiClientException::class)]
#[Group('runtime')]
#[Small]
final class AiClientExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $previous = new RuntimeException('Original failure');
        $exception = new class ('Test failure', 42, $previous) extends AiClientException {};

        self::assertInstanceOf(Exception::class, $exception);
        self::assertSame('Test failure', $exception->getMessage());
        self::assertSame(42, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }
}
