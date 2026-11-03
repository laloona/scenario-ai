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

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface as HttpClient;
use Psr\Http\Message\ResponseInterface;
use Stateforge\Scenario\Ai\Contract\AiClient;
use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiAudit;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\MessageBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseDecoder;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\AiClientContentException;
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\AiClientEmptyContentException;
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\AiClientHttpException;
use function hash;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function rawurlencode;
use function rtrim;
use function sprintf;
use const JSON_THROW_ON_ERROR;

final class GeminiClient implements AiClient
{
    public function __construct(
        private readonly GeminiConfig $config,
        private readonly HttpClient $httpClient,
        private readonly MessageBuilder $messageBuilder,
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
            'systemInstruction' => [
                'parts' => [['text' => $this->instruction->instruct(AdapterMode::Cli)]],
            ],
            'contents' => $this->messageBuilder->build($request, $audits),
            'generationConfig' => (new GeminiFormat())->format(),
        ];

        $endpoint = rtrim($this->config->apiEndpoint, '/') . '/'
            . rawurlencode($this->config->model) . ':generateContent';

        return $this->buildResponse(
            $this->httpClient->sendRequest(
                new Request(
                    'POST',
                    $endpoint,
                    [
                        'x-goog-api-key' => $this->config->apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    json_encode($payload, JSON_THROW_ON_ERROR),
                ),
            ),
        );
    }

    private function buildResponse(ResponseInterface $response): AiResponse
    {
        if ($response->getStatusCode() >= 400) {
            throw new AiClientHttpException(
                'Gemini API',
                $response->getStatusCode(),
                (string) $response->getBody(),
            );
        }

        $data = json_decode(
            (string) $response->getBody(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        if (is_array($data) === false) {
            throw new AiClientContentException('Invalid Gemini API response.');
        }

        $candidates = $data['candidates'] ?? null;
        if (is_array($candidates) === false
            || isset($candidates[0]) === false
            || is_array($candidates[0]) === false) {
            throw new AiClientContentException('Gemini API returned no candidates (possibly blocked).');
        }

        $candidate = $candidates[0];
        if (($candidate['finishReason'] ?? null) !== 'STOP') {
            throw new AiClientContentException(sprintf(
                'Gemini API response was not completed: %s',
                is_string($candidate['finishReason'] ?? null) === true ? $candidate['finishReason'] : 'unknown',
            ));
        }

        $content = $candidate['content'] ?? null;
        $parts = is_array($content) === true
            ? ($content['parts'] ?? null)
            : null;
        if (is_array($parts) === false) {
            throw new AiClientContentException('Gemini response has no content parts.');
        }

        $text = '';
        foreach ($parts as $part) {
            if (is_array($part) === true
                && is_string($part['text'] ?? null) === true) {
                $text .= $part['text'];
            }
        }

        if ($text === '') {
            throw new AiClientEmptyContentException('Gemini API');
        }

        // Gemini generateContent does not guarantee an OpenAI-style response ID.
        // A deterministic local identifier satisfies ResponseDecoder's contract.
        return $this->responseDecoder->decode('gemini-' . hash('sha256', $text), $text);
    }
}
