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
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\AiClientEmptyContentException;
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\AiClientException;

#[CoversClass(AiClientEmptyContentException::class)]
#[Group('runtime')]
#[Small]
final class AiClientEmptyContentExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new AiClientEmptyContentException('Test API');

        self::assertSame('No output text found in Test API response.', $exception->getMessage());
        self::assertInstanceOf(AiClientException::class, $exception);
    }
}
