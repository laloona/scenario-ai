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
use Stateforge\Scenario\Ai\Runtime\Exception\ClassDoesNotExistException;
use Stateforge\Scenario\Ai\Runtime\Exception\ResponseException;

#[CoversClass(ClassDoesNotExistException::class)]
#[Group('runtime')]
#[Small]
final class ClassDoesNotExistExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new ClassDoesNotExistException('Missing\\RequestedClass');

        self::assertSame('class "Missing\\RequestedClass" does not exist.', $exception->getMessage());
        self::assertInstanceOf(ResponseException::class, $exception);
    }
}
