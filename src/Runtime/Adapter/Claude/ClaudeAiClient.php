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

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface as HttpClient;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Stateforge\Scenario\Ai\Contract\AiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiAudit;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseDecoder;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function sprintf;
use const JSON_THROW_ON_ERROR;

final class ClaudeAiClient implements AiClient
{
    public function __construct(
        private readonly ClaudeAiConfig $config,
        private readonly HttpClient $httpClient,
        private readonly Instruction $instruction,
        private readonly ResponseDecoder $responseDecoder,
    ) {
    }

    /**
     * @param list<AiAudit> $audits
     */
    public function request(AiRequest $request, array $audits): AiResponse
    {
        $payload = [
            'model' => $this->config->model,
            'max_tokens' => $this->config->maxTokens,
            'cache_control' => [
                'type' => 'ephemeral',
            ],
            'system' => $this->instruction->instruct(),
            'messages' => $this->buildMessages($request, $audits),
            'output_config' => (new ClaudeAiFormat())->format(),
        ];

        return $this->buildResponse(
            $this->httpClient->sendRequest(
                new Request(
                    'POST',
                    $this->config->apiEndpoint,
                    [
                        'x-api-key' => $this->config->apiKey,
                        'anthropic-version' => $this->config->version,
                        'content-type' => 'application/json',
                    ],
                    json_encode($payload, JSON_THROW_ON_ERROR),
                ),
            ),
        );
    }

    /**
     * @param list<AiAudit> $audits
     * @return list<array{role: string, content: string}>
     */
    private function buildMessages(AiRequest $request, array $audits): array
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

    private function buildResponse(ResponseInterface $response): AiResponse
    {
        if ($response->getStatusCode() >= 400) {
            throw new RuntimeException(sprintf(
                'Claude API returned HTTP %d: %s',
                $response->getStatusCode(),
                $response->getBody(),
            ));
        }

        $data = json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        if (is_array($data) === false) {
            throw new RuntimeException('Invalid Claude response.');
        }

        $id = $data['id'] ?? null;
        if (is_string($id) === false) {
            throw new RuntimeException('Claude response has no valid id.');
        }

        if (($data['stop_reason'] ?? null) !== 'end_turn') {
            throw new RuntimeException(sprintf(
                'Claude generation stopped unexpectedly: %s',
                is_string($data['stop_reason'] ?? null)
                    ? $data['stop_reason']
                    : 'unknown',
            ));
        }

        $outputs = $data['content'] ?? null;
        if (is_array($outputs) === false) {
            throw new RuntimeException('Claude response has no valid content.');
        }

        foreach ($outputs as $output) {
            if (is_array($output) === false) {
                continue;
            }

            if (($output['type'] ?? null) !== 'text'
                || is_string($output['text'] ?? null) === false) {
                continue;
            }

            return $this->responseDecoder->decode($id, $output['text']);
        }

        throw new RuntimeException('No output text found in Claude response.');
    }
}
