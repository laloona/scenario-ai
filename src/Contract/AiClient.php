<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Contract;

use Stateforge\Scenario\Ai\Runtime\Adapter\AiAudit;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;

interface AiClient
{
    /**
     * @param list<AiAudit> $audits
     */
    public function request(AiRequest $request, array $audits): AiResponse;
}
