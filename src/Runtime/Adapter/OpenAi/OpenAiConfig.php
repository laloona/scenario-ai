<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi;

final class OpenAiConfig
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $apiEndpoint = 'https://api.openai.com/v1/responses',
        public readonly string $model = 'gpt-5.4-mini',
    ) {
    }
}
