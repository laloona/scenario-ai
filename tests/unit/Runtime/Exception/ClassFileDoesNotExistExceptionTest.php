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
use Stateforge\Scenario\Ai\Runtime\Exception\ClassFileDoesNotExistException;
use stdClass;

#[CoversClass(ClassFileDoesNotExistException::class)]
#[Group('runtime')]
#[Small]
final class ClassFileDoesNotExistExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new ClassFileDoesNotExistException(
            stdClass::class,
        );

        self::assertSame(
            'class file for class "' . stdClass::class . '" does not exist.',
            $exception->getMessage(),
        );
    }
}
