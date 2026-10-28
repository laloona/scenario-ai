<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Files;

use Stateforge\Scenario\Ai\Contract\AiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiAudit;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\ConfigBuilder;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;

final class CustomTestClient implements AiClient
{
    public function __construct(
        public readonly ConfigBuilder $configBuilder,
        public readonly AdapterConfiguration $configuration,
        public readonly Instruction $instruction,
    ) {
    }

    /** @param list<AiAudit> $audits */
    public function request(AiRequest $request, array $audits): AiResponse
    {
        return new AiResponse($request->id, null, null, $request->prompt, []);
    }
}
