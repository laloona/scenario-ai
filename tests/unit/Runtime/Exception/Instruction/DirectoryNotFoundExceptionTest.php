<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Exception\Instruction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stateforge\Scenario\Ai\Runtime\Exception\Instruction\DirectoryNotFoundException;

#[CoversClass(DirectoryNotFoundException::class)]
#[Group('runtime')]
#[Small]
final class DirectoryNotFoundExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new DirectoryNotFoundException(
            'Something went wrong',
        );

        self::assertSame(
            'Something went wrong',
            $exception->getMessage(),
        );
    }

    public function testPreservesCodeAndPreviousException(): void
    {
        $previous = new RuntimeException(
            'Original failure',
        );
        $exception = new DirectoryNotFoundException(
            'Wrapped failure',
            42,
            $previous,
        );

        self::assertSame(
            'Wrapped failure',
            $exception->getMessage(),
        );
        self::assertSame(
            42,
            $exception->getCode(),
        );
        self::assertSame(
            $previous,
            $exception->getPrevious(),
        );
    }
}
