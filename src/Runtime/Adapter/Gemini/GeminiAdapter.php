<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Adapter\Gemini;

use GuzzleHttp\Client as GuzzleClient;
use Stateforge\Scenario\Ai\Contract\AiAdapter;
use Stateforge\Scenario\Ai\Contract\AiClient;
use Stateforge\Scenario\Ai\Contract\AiSkill;
use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Runtime\Adapter\ConfigBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\FileBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\MessageBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseDecoder;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;

final class GeminiAdapter implements AiAdapter
{
    public function client(
        ConfigBuilder $configBuilder,
        AdapterConfiguration $adapterConfiguration,
        Instruction $instruction,
    ): AiClient {
        return new GeminiClient(
            $configBuilder->build(GeminiConfig::class, $adapterConfiguration->vars),
            new GuzzleClient(),
            new MessageBuilder(
                new MessagePartsBuilder(),
                'role',
                'parts',
                'user',
                'model',
            ),
            $instruction,
            new ResponseDecoder(new ResponseBuilder()),
        );
    }

    public function skill(
        AdapterConfiguration $adapterConfiguration,
        Instruction $instruction,
    ): AiSkill {
        return new GeminiSkill(
            new FileBuilder($instruction),
        );
    }
}
