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
use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Runtime\Adapter\ConfigBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\FileBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\MessageBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\MessageStringBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiAdapter;
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiConfig;
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiSkill;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseDecoder;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Exception\ConfigBuilderException;

#[CoversClass(OpenAiAdapter::class)]
#[UsesClass(FileBuilder::class)]
#[UsesClass(OpenAiSkill::class)]
#[UsesClass(ConfigBuilder::class)]
#[UsesClass(AdapterConfiguration::class)]
#[UsesClass(OpenAiConfig::class)]
#[UsesClass(OpenAiClient::class)]
#[UsesClass(ResponseDecoder::class)]
#[UsesClass(ResponseBuilder::class)]
#[UsesClass(MessageBuilder::class)]
#[UsesClass(MessageStringBuilder::class)]
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
            new AdapterConfiguration(AdapterMode::All, 'test', null, null, [
                'apiKey' => 'test-key', 'apiEndpoint' => 'https://example.test/ai', 'model' => 'test-model',
            ]),
            $instruction,
        );

        self::assertInstanceOf(OpenAiClient::class, $client);
    }

    public function testRejectsMissingRequiredConfiguration(): void
    {
        $this->expectException(ConfigBuilderException::class);

        (new OpenAiAdapter())->client(new ConfigBuilder(), new AdapterConfiguration(AdapterMode::All, 'test', null, null, []), self::createStub(Instruction::class));
    }
    public function testBuildsSkillWithoutApiConfiguration(): void
    {
        self::assertInstanceOf(
            OpenAiSkill::class,
            (new OpenAiAdapter())->skill(new AdapterConfiguration(AdapterMode::Skill, 'test', null, null, []), self::createStub(Instruction::class)),
        );
    }

}
