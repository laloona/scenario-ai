<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Adapter\Gemini;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Adapter\Gemini\GeminiConfig;

#[CoversClass(GeminiConfig::class)]
#[Group('runtime')]
#[Small]
final class GeminiConfigTest extends TestCase
{
    public function testProvidesDefaultEndpointAndModel(): void
    {
        $config = new GeminiConfig('test-key');

        self::assertSame('test-key', $config->apiKey);
        self::assertSame('https://generativelanguage.googleapis.com/v1beta/models', $config->apiEndpoint);
        self::assertSame('gemini-2.5-flash', $config->model);
    }

    public function testAcceptsCustomEndpointAndModel(): void
    {
        $config = new GeminiConfig('custom-key', 'https://example.test/models', 'custom-model');

        self::assertSame('custom-key', $config->apiKey);
        self::assertSame('https://example.test/models', $config->apiEndpoint);
        self::assertSame('custom-model', $config->model);
    }
}
