<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Adapter\Claude;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Adapter\Claude\ClaudeAiConfig;

#[CoversClass(ClaudeAiConfig::class)]
#[Group('runtime')]
#[Small]
final class ClaudeAiConfigTest extends TestCase
{
    public function testStoresApiKeyAndDefaultOptions(): void
    {
        $config = new ClaudeAiConfig('test-key');

        self::assertSame('test-key', $config->apiKey);
        self::assertSame('https://api.anthropic.com/v1/messages', $config->apiEndpoint);
        self::assertSame('claude-opus-5-5', $config->model);
        self::assertSame('2026-10-21', $config->version);
        self::assertSame(8192, $config->maxTokens);
    }

    public function testAllowsCustomOptions(): void
    {
        $config = new ClaudeAiConfig('test-key', 'https://example.test/ai', 'test-model', 'test-version', 1024);

        self::assertSame('test-key', $config->apiKey);
        self::assertSame('https://example.test/ai', $config->apiEndpoint);
        self::assertSame('test-model', $config->model);
        self::assertSame('test-version', $config->version);
        self::assertSame(1024, $config->maxTokens);
    }
}
