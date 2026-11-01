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
    public function __construct(
        private readonly MessageContentBuilder $contentBuilder,
        private readonly string $roleKey = 'role',
        private readonly string $contentKey = 'content',
        private readonly string $userValue = 'user',
        private readonly string $assistantValue = 'assistant',
    ) {
    }

    /**
     * @param list<AiAudit> $audits
     * @return list<array<string, mixed>>
     */
    public function build(AiRequest $request, array $audits): array
    {
        $messages = [];
        foreach ($audits as $audit) {
            $messages[] = [
                $this->roleKey => $this->userValue,
                $this->contentKey => $this->contentBuilder->buildContent($audit->request->prompt),
            ];

            $messages[] = [
                $this->roleKey => $this->assistantValue,
                $this->contentKey => $this->contentBuilder->buildContent(
                    json_encode(
                        [
                            'php' => $audit->response->php,
                            'type' => $audit->response->type?->value,
                            'question' => $audit->response->question,
                            'classes' => $audit->response->classes,
                            'match' => $audit->response->match,
                        ],
                        JSON_THROW_ON_ERROR,
                    ),
                ),
            ];
        }

        $messages[] = [
            $this->roleKey => $this->userValue,
            $this->contentKey => $this->contentBuilder->buildContent($request->prompt),
        ];

        return $messages;
    }
}
