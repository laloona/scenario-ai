<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Adapter;

use function json_encode;
use const JSON_THROW_ON_ERROR;

final class MessageBuilder
{
    /**
     * @param list<AiAudit> $audits
     * @return list<array{role: string, content: string}>
     */
    public function build(AiRequest $request, array $audits): array
    {
        $messages = [];
        foreach ($audits as $audit) {
            $messages[] = [
                'role' => 'user',
                'content' => $audit->request->prompt,
            ];

            $messages[] = [
                'role' => 'assistant',
                'content' => json_encode(
                    [
                        'php' => $audit->response->php,
                        'type' => $audit->response->type?->value,
                        'question' => $audit->response->question,
                        'classes' => $audit->response->classes,
                        'match' => $audit->response->match,
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $request->prompt,
        ];

        return $messages;
    }
}
