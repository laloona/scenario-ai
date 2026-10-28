<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Adapter\Claude;

use GuzzleHttp\Client as GuzzleClient;
use Stateforge\Scenario\Ai\Contract\AiAdapter;
use Stateforge\Scenario\Ai\Contract\AiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\ConfigBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseDecoder;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;

final class ClaudeAiAdapter implements AiAdapter
{
    public function client(
        ConfigBuilder $configBuilder,
        AdapterConfiguration $adapterConfiguration,
        Instruction $instruction,
    ): AiClient {
        return new ClaudeAiClient(
            $configBuilder->build(ClaudeAiConfig::class, $adapterConfiguration->vars),
            new GuzzleClient(),
            $instruction,
            new ResponseDecoder(new ResponseBuilder()),
        );
    }
}
