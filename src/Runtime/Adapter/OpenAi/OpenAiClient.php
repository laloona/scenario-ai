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
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;

final class OpenAiClient implements AiClient
{
    public function __construct(
        private readonly OpenAiConfig $config,
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
            'model' => $this->config->model,
            'store' => false,
            'instructions' => $this->instruction->instruct(AdapterMode::Cli),
            'input' => $this->messageBuilder->build($request, $audits),
            'text' => (new OpenAiFormat())->format(),
        ];

        return $this->buildResponse(
            $this->httpClient->sendRequest(
                new Request(
                    'POST',
                    $this->config->apiEndpoint,
                    [
                        'Authorization' => 'Bearer ' . $this->config->apiKey,
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
                'OpenAI API',
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
            throw new AiClientContentException('Invalid OpenAI API response.');
        }

        $id = $data['id'] ?? null;
        if (is_string($id) === false) {
            throw new AiClientContentException('OpenAI API response has no valid id.');
        }

        if (($data['status'] ?? null) !== 'completed') {
            throw new AiClientContentException('OpenAI API response was not completed.');
        }

        $outputs = $data['output'] ?? null;
        if (is_array($outputs) === false) {
            throw new AiClientContentException('OpenAI API response has no valid output.');
        }

        foreach ($outputs as $output) {
            if (is_array($output) === false) {
                continue;
            }

            if (($output['type'] ?? null) !== 'message') {
                continue;
            }

            $contents = $output['content'] ?? null;
            if (is_array($contents) === false) {
                continue;
            }

            foreach ($contents as $content) {
                if (is_array($content) === false) {
                    continue;
                }

                if (($content['type'] ?? null) === 'refusal') {
                    $reason = $content['refusal'] ?? null;
                    throw new AiClientContentException(
                        'OpenAI refused the request: ' .
                        (is_string($reason) ? $reason : 'Unknown reason'),
                    );
                }

                if (($content['type'] ?? null) !== 'output_text') {
                    continue;
                }

                $text = $content['text'] ?? null;
                if (is_string($text) === false) {
                    throw new AiClientContentException('OpenAI API output text is invalid.');
                }

                return $this->responseDecoder->decode(
                    $id,
                    $text,
                );
            }
        }

        throw new AiClientEmptyContentException('OpenAI API');
    }
}
