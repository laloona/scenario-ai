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
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiConfig;
use Stateforge\Scenario\Ai\Runtime\Exception\ConfigBuilderException;

#[CoversClass(ConfigBuilderException::class)]
#[Group('runtime')]
#[Small]
final class ConfigBuilderExceptionTest extends TestCase
{
    public function testExceptionContainsMessage(): void
    {
        $exception = new ConfigBuilderException(
            OpenAiConfig::class,
            'Missing API key',
        );

        self::assertSame(
            'Invalid configuration for "' . OpenAiConfig::class . '": Missing API key',
            $exception->getMessage(),
        );
    }
}
