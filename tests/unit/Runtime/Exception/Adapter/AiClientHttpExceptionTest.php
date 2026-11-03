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
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\AiClientException;
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\AiClientHttpException;

#[CoversClass(AiClientHttpException::class)]
#[Group('runtime')]
#[Small]
final class AiClientHttpExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new AiClientHttpException('Test API', 503, 'Service unavailable');

        self::assertSame('Test API returned HTTP 503: Service unavailable', $exception->getMessage());
        self::assertInstanceOf(AiClientException::class, $exception);
    }
}
