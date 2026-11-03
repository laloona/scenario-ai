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
use Stateforge\Scenario\Ai\Runtime\Exception\ConfigurationException;
use Stateforge\Scenario\Ai\Runtime\Exception\InvalidAdapterVarsXPathException;

#[CoversClass(InvalidAdapterVarsXPathException::class)]
#[Group('runtime')]
#[Small]
final class InvalidAdapterVarsXPathExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new InvalidAdapterVarsXPathException();

        self::assertSame(
            'Invalid adapter variables XPath expression.',
            $exception->getMessage(),
        );
        self::assertInstanceOf(ConfigurationException::class, $exception);
    }
}
