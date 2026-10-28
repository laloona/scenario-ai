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
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\Adapter\Claude\ClaudeAiAdapter;
use Stateforge\Scenario\Ai\Runtime\Adapter\Claude\ClaudeAiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\Claude\ClaudeAiConfig;
use Stateforge\Scenario\Ai\Runtime\Adapter\ConfigBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseDecoder;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\Exception\ConfigBuilderException;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;

#[CoversClass(ClaudeAiAdapter::class)]
#[UsesClass(ConfigBuilder::class)]
#[UsesClass(AdapterConfiguration::class)]
#[UsesClass(ClaudeAiConfig::class)]
#[UsesClass(ClaudeAiClient::class)]
#[UsesClass(ResponseDecoder::class)]
#[UsesClass(ResponseBuilder::class)]
#[UsesClass(ConfigBuilderException::class)]
#[Group('runtime')]
#[Small]
final class ClaudeAiAdapterTest extends TestCase
{
    public function testBuildsClientWithConfiguredOptions(): void
    {
        $instruction = self::createStub(Instruction::class);
        $client = (new ClaudeAiAdapter())->client(
            new ConfigBuilder(),
            new AdapterConfiguration('test', null, null, [
                'apiKey' => 'test-key', 'apiEndpoint' => 'https://example.test/ai', 'model' => 'test-model',
            ]),
            $instruction,
        );

        self::assertInstanceOf(ClaudeAiClient::class, $client);
        $reflection = new ReflectionClass($client);
        $config = $reflection->getProperty('config')->getValue($client);
        self::assertInstanceOf(ClaudeAiConfig::class, $config);
        self::assertSame('test-key', $config->apiKey);
        self::assertSame('https://example.test/ai', $config->apiEndpoint);
        self::assertSame('test-model', $config->model);
        self::assertSame($instruction, $reflection->getProperty('instruction')->getValue($client));
    }

    public function testRejectsMissingRequiredConfiguration(): void
    {
        $this->expectException(ConfigBuilderException::class);

        (new ClaudeAiAdapter())->client(new ConfigBuilder(), new AdapterConfiguration('test', null, null, []), self::createStub(Instruction::class));
    }
}
