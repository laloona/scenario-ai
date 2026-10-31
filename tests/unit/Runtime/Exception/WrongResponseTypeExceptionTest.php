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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Exception\ResponseException;
use Stateforge\Scenario\Ai\Runtime\Exception\WrongResponseTypeException;

#[CoversClass(WrongResponseTypeException::class)]
#[Group('runtime')]
#[Small]
final class WrongResponseTypeExceptionTest extends TestCase
{
    #[DataProvider('values')]
    public function testExceptionContainsMessage(mixed $value, string $display): void
    {
        $exception = new WrongResponseTypeException($value, 'string');

        self::assertSame('wrong response type: got value ' . $display . ' and expected string.', $exception->getMessage());
        self::assertInstanceOf(ResponseException::class, $exception);
    }

    /** @return iterable<string, array{mixed, string}> */
    public static function values(): iterable
    {
        yield 'string value' => ['unsupported', 'unsupported'];
        yield 'array type' => [[], 'array'];
        yield 'null type' => [null, 'null'];
        yield 'integer type' => [42, 'int'];
        yield 'boolean type' => [false, 'bool'];
    }

}
