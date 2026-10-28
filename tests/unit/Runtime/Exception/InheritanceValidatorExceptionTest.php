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

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stateforge\Scenario\Ai\Runtime\Exception\InheritanceValidatorException;

#[CoversClass(InheritanceValidatorException::class)]
#[Group('runtime')]
#[Small]
final class InheritanceValidatorExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new InheritanceValidatorException(
            RuntimeException::class,
            Exception::class,
        );

        self::assertSame(
            'Class "' . RuntimeException::class . '" must extend "' . Exception::class . '" directly or indirectly.',
            $exception->getMessage(),
        );
    }
}
