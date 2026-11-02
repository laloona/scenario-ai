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
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Runtime\Adapter\ConfigBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\FileBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\Gemini\GeminiAdapter;
use Stateforge\Scenario\Ai\Runtime\Adapter\Gemini\GeminiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\Gemini\GeminiConfig;
use Stateforge\Scenario\Ai\Runtime\Adapter\Gemini\GeminiSkill;
use Stateforge\Scenario\Ai\Runtime\Adapter\Gemini\MessagePartsBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\MessageBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseDecoder;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Exception\ConfigBuilderException;

#[CoversClass(GeminiAdapter::class)]
#[UsesClass(FileBuilder::class)]
#[UsesClass(GeminiSkill::class)]
#[UsesClass(ConfigBuilder::class)]
#[UsesClass(AdapterConfiguration::class)]
#[UsesClass(GeminiConfig::class)]
#[UsesClass(GeminiClient::class)]
#[UsesClass(ResponseDecoder::class)]
#[UsesClass(ResponseBuilder::class)]
#[UsesClass(MessageBuilder::class)]
#[UsesClass(MessagePartsBuilder::class)]
#[UsesClass(ConfigBuilderException::class)]
#[Group('runtime')]
#[Small]
final class GeminiAdapterTest extends TestCase
{
    public function testBuildsClientWithConfiguredOptions(): void
    {
        $instruction = self::createStub(Instruction::class);
        $client = (new GeminiAdapter())->client(
            new ConfigBuilder(),
            new AdapterConfiguration(AdapterMode::All, 'test', null, null, [
                'apiKey' => 'test-key', 'apiEndpoint' => 'https://example.test/ai', 'model' => 'test-model',
            ]),
            $instruction,
        );

        self::assertInstanceOf(GeminiClient::class, $client);
    }

    public function testRejectsMissingRequiredConfiguration(): void
    {
        $this->expectException(ConfigBuilderException::class);

        (new GeminiAdapter())->client(new ConfigBuilder(), new AdapterConfiguration(AdapterMode::All, 'test', null, null, []), self::createStub(Instruction::class));
    }
    public function testBuildsSkillWithoutApiConfiguration(): void
    {
        self::assertInstanceOf(
            GeminiSkill::class,
            (new GeminiAdapter())->skill(new AdapterConfiguration(AdapterMode::Skill, 'test', null, null, []), self::createStub(Instruction::class)),
        );
    }

}
