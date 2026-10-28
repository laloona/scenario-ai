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
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stateforge\Scenario\Ai\Runtime\Exception\InvalidGeneratedCodeException;
use Stateforge\Scenario\Ai\Runtime\Exception\ValidatorException;

#[CoversClass(ValidatorException::class)]
#[UsesClass(InvalidGeneratedCodeException::class)]
#[Group('runtime')]
#[Small]
final class ValidatorExceptionTest extends TestCase
{
    public function testConcreteValidatorExceptionPreservesInheritedExceptionContract(): void
    {
        $previous = new RuntimeException(
            'Original failure',
        );
        $exception = new InvalidGeneratedCodeException(
            'Validation failed',
            42,
            $previous,
        );

        self::assertInstanceOf(ValidatorException::class, $exception);
        self::assertSame(
            'Validation failed',
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
