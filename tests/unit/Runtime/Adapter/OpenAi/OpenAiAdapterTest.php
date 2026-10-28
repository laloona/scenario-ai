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
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\Adapter\ConfigBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiAdapter;
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiConfig;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseDecoder;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\Exception\ConfigBuilderException;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;

#[CoversClass(OpenAiAdapter::class)]
#[UsesClass(ConfigBuilder::class)]
#[UsesClass(AdapterConfiguration::class)]
#[UsesClass(OpenAiConfig::class)]
#[UsesClass(OpenAiClient::class)]
#[UsesClass(ResponseDecoder::class)]
#[UsesClass(ResponseBuilder::class)]
#[UsesClass(ConfigBuilderException::class)]
#[Group('runtime')]
#[Small]
final class OpenAiAdapterTest extends TestCase
{
    public function testBuildsClientWithConfiguredOptions(): void
    {
        $instruction = self::createStub(Instruction::class);
        $client = (new OpenAiAdapter())->client(
            new ConfigBuilder(),
            new AdapterConfiguration('test', null, null, [
                'apiKey' => 'test-key', 'apiEndpoint' => 'https://example.test/ai', 'model' => 'test-model',
            ]),
            $instruction,
        );

        self::assertInstanceOf(OpenAiClient::class, $client);
        $reflection = new ReflectionClass($client);
        $config = $reflection->getProperty('config')->getValue($client);
        self::assertInstanceOf(OpenAiConfig::class, $config);
        self::assertSame('test-key', $config->apiKey);
        self::assertSame('https://example.test/ai', $config->apiEndpoint);
        self::assertSame('test-model', $config->model);
        self::assertSame($instruction, $reflection->getProperty('instruction')->getValue($client));
    }

    public function testRejectsMissingRequiredConfiguration(): void
    {
        $this->expectException(ConfigBuilderException::class);

        (new OpenAiAdapter())->client(new ConfigBuilder(), new AdapterConfiguration('test', null, null, []), self::createStub(Instruction::class));
    }
}
