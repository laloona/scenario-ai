<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Adapter\OpenAi;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiConfig;

#[CoversClass(OpenAiConfig::class)]
#[Group('runtime')]
#[Small]
final class OpenAiConfigTest extends TestCase
{
    public function testStoresApiKeyAndDefaultOptions(): void
    {
        $config = new OpenAiConfig('test-key');

        self::assertSame('test-key', $config->apiKey);
        self::assertSame('https://api.openai.com/v1/responses', $config->apiEndpoint);
        self::assertSame('gpt-5.4-mini', $config->model);
    }

    public function testAllowsCustomOptions(): void
    {
        $config = new OpenAiConfig('test-key', 'https://example.test/ai', 'test-model');

        self::assertSame('test-key', $config->apiKey);
        self::assertSame('https://example.test/ai', $config->apiEndpoint);
        self::assertSame('test-model', $config->model);
    }
}
