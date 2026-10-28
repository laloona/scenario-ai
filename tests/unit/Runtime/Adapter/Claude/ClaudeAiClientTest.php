<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Adapter\Claude;

use GuzzleHttp\Psr7\Response;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface as HttpClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiAudit;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\Claude\ClaudeAiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\Claude\ClaudeAiConfig;
use Stateforge\Scenario\Ai\Runtime\Adapter\Claude\ClaudeAiFormat;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseDecoder;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;

#[CoversClass(ClaudeAiClient::class)]
#[UsesClass(ClaudeAiConfig::class)]
#[UsesClass(ClaudeAiFormat::class)]
#[UsesClass(AiAudit::class)]
#[UsesClass(AiRequest::class)]
#[UsesClass(AiResponse::class)]
#[UsesClass(ResponseDecoder::class)]
#[UsesClass(ResponseBuilder::class)]
#[Group('runtime')]
#[Small]
final class ClaudeAiClientTest extends TestCase
{
    #[DataProvider('historyProvider')]
    public function testSendsConfiguredRequestAndDecodesResponse(bool $withHistory): void
    {
        $http = $this->createMock(HttpClient::class);
        $http->expects(self::once())->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request) use ($withHistory): ResponseInterface {
                self::assertSame('POST', $request->getMethod());
                self::assertSame('https://example.test/ai', (string) $request->getUri());
                self::assertSame('application/json', $request->getHeaderLine('content-type'));
                $payload = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
                self::assertIsArray($payload);
                self::assertSame('test-model', $payload['model']);
                self::assertSame('test-key', $request->getHeaderLine('x-api-key'));
                self::assertSame('test-version', $request->getHeaderLine('anthropic-version'));
                self::assertSame('Test instructions', $payload['system']);
                self::assertSame(1024, $payload['max_tokens']);
                self::assertSame(['type' => 'ephemeral'], $payload['cache_control']);
                self::assertSame((new ClaudeAiFormat())->format(), $payload['output_config']);
                $expected = $withHistory ? [
                    ['role' => 'user', 'content' => 'Initial prompt'],
                    ['role' => 'assistant', 'content' => json_encode(['php' => null, 'type' => null, 'question' => 'Which state?', 'classes' => []], JSON_THROW_ON_ERROR)],
                ] : [];
                $expected[] = ['role' => 'user', 'content' => 'Generate a scenario'];
                self::assertSame($expected, $payload['messages']);
                return new Response(200, [], $this->validResponseBody('{"php":"<?php class Demo {}","type":"scenario","question":null,"classes":[]}'));
            },
        );
        $audits = $withHistory ? [new AiAudit(new AiRequest(null, 'Initial prompt', 1), new AiResponse('previous-id', null, null, 'Which state?', []))] : [];
        $response = $this->client($http)->request(new AiRequest($withHistory ? 'previous-id' : null, 'Generate a scenario', 2), $audits);

        self::assertSame('answer-id', $response->id);
        self::assertSame('<?php class Demo {}', $response->php);
        self::assertSame(GenerationType::Scenario, $response->type);
        self::assertNull($response->question);
        self::assertSame([], $response->classes);
    }

    /** @return array<string, array{bool}> */
    public static function historyProvider(): array
    {
        return ['first request' => [false], 'with history' => [true]];
    }

    #[DataProvider('httpErrorProvider')]
    public function testRejectsHttpError(int $status): void
    {
        $http = self::createStub(HttpClient::class);
        $http->method('sendRequest')->willReturn(new Response($status, [], 'API failure'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Claude API returned HTTP ' . $status . ': API failure');

        $this->client($http)->request(new AiRequest(null, 'Generate', 1), []);
    }

    /** @return array<string, array{int}> */
    public static function httpErrorProvider(): array
    {
        return ['client error' => [400], 'server error' => [500]];
    }

    #[DataProvider('invalidResponseProvider')]
    public function testRejectsInvalidProviderResponse(string $body, string $message): void
    {
        $http = self::createStub(HttpClient::class);
        $http->method('sendRequest')->willReturn(new Response(200, [], $body));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->client($http)->request(new AiRequest(null, 'Generate', 1), []);
    }

    /** @return array<string, array{string, string}> */
    public static function invalidResponseProvider(): array
    {
        return [
            'non object' => ['null', 'Invalid Claude response.'],
            'missing id' => ['{}', 'Claude response has no valid id.'],
            'invalid id' => ['{"id":42}', 'Claude response has no valid id.'],
            'missing stop reason' => ['{"id":"id"}', 'Claude generation stopped unexpectedly: unknown'],
            'invalid stop reason' => ['{"id":"id","stop_reason":42}', 'Claude generation stopped unexpectedly: unknown'],
            'token limit' => ['{"id":"id","stop_reason":"max_tokens"}', 'Claude generation stopped unexpectedly: max_tokens'],
            'missing content' => ['{"id":"id","stop_reason":"end_turn"}', 'Claude response has no valid content.'],
            'invalid content' => ['{"id":"id","stop_reason":"end_turn","content":"wrong"}', 'Claude response has no valid content.'],
            'empty content' => ['{"id":"id","stop_reason":"end_turn","content":[]}', 'No output text found in Claude response.'],
            'unusable content' => ['{"id":"id","stop_reason":"end_turn","content":[null,{"type":"tool_use"},{"type":"text","text":42}]}', 'No output text found in Claude response.'],
        ];
    }

    public function testRejectsMalformedResponseJson(): void
    {
        $http = self::createStub(HttpClient::class);
        $http->method('sendRequest')->willReturn(new Response(200, [], '{invalid'));
        $this->expectException(JsonException::class);

        $this->client($http)->request(new AiRequest(null, 'Generate', 1), []);
    }

    public function testRejectsMalformedStructuredOutputJson(): void
    {
        $http = self::createStub(HttpClient::class);
        $http->method('sendRequest')->willReturn(new Response(200, [], $this->validResponseBody('{invalid')));
        $this->expectException(JsonException::class);

        $this->client($http)->request(new AiRequest(null, 'Generate', 1), []);
    }

    public function testPropagatesTransportFailure(): void
    {
        $failure = new RuntimeException('Transport failed');
        $http = $this->createMock(HttpClient::class);
        $http->expects(self::once())->method('sendRequest')->willThrowException($failure);
        $this->expectExceptionObject($failure);

        $this->client($http)->request(new AiRequest(null, 'Generate', 1), []);
    }

    private function validResponseBody(string $text): string
    {
        return json_encode(['id' => 'answer-id', 'stop_reason' => 'end_turn', 'content' => [null, ['type' => 'tool_use'], ['type' => 'text', 'text' => 123], ['type' => 'text', 'text' => $text]]], JSON_THROW_ON_ERROR);
    }

    private function client(HttpClient $http): ClaudeAiClient
    {
        $instruction = self::createStub(Instruction::class);
        $instruction->method('instruct')->willReturn('Test instructions');

        return new ClaudeAiClient(new ClaudeAiConfig('test-key', 'https://example.test/ai', 'test-model', 'test-version', 1024), $http, $instruction, new ResponseDecoder(new ResponseBuilder()));
    }
}
