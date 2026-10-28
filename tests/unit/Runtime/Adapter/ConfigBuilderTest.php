<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Adapter\ConfigBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiConfig;
use Stateforge\Scenario\Ai\Runtime\Exception\ConfigBuilderException;

#[CoversClass(ConfigBuilder::class)]
#[UsesClass(OpenAiConfig::class)]
#[UsesClass(ConfigBuilderException::class)]
#[Group('runtime')]
#[Small]
final class ConfigBuilderTest extends TestCase
{
    public function testBuildUsesNamedArgumentsRegardlessOfOrder(): void
    {
        $config = (new ConfigBuilder())->build(OpenAiConfig::class, [
            'model' => 'test-model',
            'apiEndpoint' => 'https://example.test/ai',
            'apiKey' => 'test-key',
        ]);

        self::assertSame('test-key', $config->apiKey);
        self::assertSame('https://example.test/ai', $config->apiEndpoint);
        self::assertSame('test-model', $config->model);
    }

    public function testBuildPreservesConstructorDefaults(): void
    {
        $config = (new ConfigBuilder())->build(OpenAiConfig::class, ['apiKey' => 'test-key']);
        $default = new OpenAiConfig('test-key');

        self::assertSame($default->apiEndpoint, $config->apiEndpoint);
        self::assertSame($default->model, $config->model);
    }

    public function testMissingRequiredArgumentIsWrapped(): void
    {
        $this->expectException(ConfigBuilderException::class);
        $this->expectExceptionMessage('Invalid configuration for "' . OpenAiConfig::class . '":');

        (new ConfigBuilder())->build(OpenAiConfig::class, []);
    }

    public function testUnknownNamedArgumentIsWrapped(): void
    {
        $this->expectException(ConfigBuilderException::class);
        $this->expectExceptionMessage('Unknown named parameter $unknown');

        (new ConfigBuilder())->build(OpenAiConfig::class, ['apiKey' => 'test-key', 'unknown' => 'value']);
    }
}
