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

final class ClaudeAiConfig
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $apiEndpoint = 'https://api.anthropic.com/v1/messages',
        public readonly string $model = 'claude-opus-5-5',
        public readonly string $version = '2026-10-21',
        public readonly int $maxTokens = 8192,
    ) {
    }
}
