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
use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiAudit;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\Claude\ClaudeClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\Claude\ClaudeConfig;
use Stateforge\Scenario\Ai\Runtime\Adapter\Claude\ClaudeFormat;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\Adapter\MessageBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\MessageStringBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseDecoder;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\AiClientContentException;
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\AiClientEmptyContentException;
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\AiClientHttpException;
use Stateforge\Scenario\Ai\Runtime\Exception\InvalidJsonResponseException;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;

#[CoversClass(ClaudeClient::class)]
#[UsesClass(AiClientContentException::class)]
#[UsesClass(AiClientEmptyContentException::class)]
#[UsesClass(AiClientHttpException::class)]
#[UsesClass(ClaudeConfig::class)]
#[UsesClass(ClaudeFormat::class)]
#[UsesClass(AiAudit::class)]
#[UsesClass(AiRequest::class)]
#[UsesClass(AiResponse::class)]
#[UsesClass(ResponseDecoder::class)]
#[UsesClass(InvalidJsonResponseException::class)]
#[UsesClass(ResponseBuilder::class)]
#[UsesClass(MessageBuilder::class)]
#[UsesClass(MessageStringBuilder::class)]
#[Group('runtime')]
#[Small]
final class ClaudeClientTest extends TestCase
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
                self::assertSame((new ClaudeFormat())->format(), $payload['output_config']);
                $expected = $withHistory ? [
                    ['role' => 'user', 'content' => 'Initial prompt'],
                    ['role' => 'assistant', 'content' => json_encode(['php' => null, 'type' => null, 'question' => 'Which state?', 'classes' => [], 'match' => null], JSON_THROW_ON_ERROR)],
                ] : [];
                $expected[] = ['role' => 'user', 'content' => 'Generate a scenario'];
                self::assertSame($expected, $payload['messages']);
                return new Response(200, [], $this->validResponseBody('{"php":"<?php class Demo {}","type":"scenario","question":null,"classes":[],"match":null}'));
            },
        );
        $audits = $withHistory ? [new AiAudit(new AiRequest(null, 'Initial prompt', 1), new AiResponse('previous-id', null, null, 'Which state?', [], null))] : [];
        $response = $this->client($http)->request(new AiRequest($withHistory ? 'previous-id' : null, 'Generate a scenario', 2), $audits);

        self::assertSame('answer-id', $response->id);
        self::assertSame('<?php class Demo {}', $response->php);
        self::assertSame(GenerationType::Scenario, $response->type);
        self::assertNull($response->question);
        self::assertSame([], $response->classes);
        self::assertNull($response->match);
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
        $this->expectException(AiClientHttpException::class);
        $this->expectExceptionMessage('Claude API returned HTTP ' . $status . ': API failure');

        $this->client($http)->request(new AiRequest(null, 'Generate', 1), []);
    }

    /** @return array<string, array{int}> */
    public static function httpErrorProvider(): array
    {
        return ['client error' => [400], 'server error' => [500]];
    }

    /** @param class-string<AiClientContentException|AiClientEmptyContentException> $exceptionClass */
    #[DataProvider('invalidResponseProvider')]
    public function testRejectsInvalidProviderResponse(string $body, string $message, string $exceptionClass): void
    {
        $http = self::createStub(HttpClient::class);
        $http->method('sendRequest')->willReturn(new Response(200, [], $body));
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($message);

        $this->client($http)->request(new AiRequest(null, 'Generate', 1), []);
    }

    /** @return array<string, array{string, string, class-string<AiClientContentException|AiClientEmptyContentException>}> */
    public static function invalidResponseProvider(): array
    {
        return [
            'non object' => ['null', 'Invalid Claude API response.', AiClientContentException::class],
            'missing id' => ['{}', 'Claude API response has no valid id.', AiClientContentException::class],
            'invalid id' => ['{"id":42}', 'Claude API response has no valid id.', AiClientContentException::class],
            'missing stop reason' => ['{"id":"id"}', 'Claude API generation stopped unexpectedly: unknown', AiClientContentException::class],
            'invalid stop reason' => ['{"id":"id","stop_reason":42}', 'Claude API generation stopped unexpectedly: unknown', AiClientContentException::class],
            'token limit' => ['{"id":"id","stop_reason":"max_tokens"}', 'Claude API generation stopped unexpectedly: max_tokens', AiClientContentException::class],
            'missing content' => ['{"id":"id","stop_reason":"end_turn"}', 'Claude API response has no valid content.', AiClientContentException::class],
            'invalid content' => ['{"id":"id","stop_reason":"end_turn","content":"wrong"}', 'Claude API response has no valid content.', AiClientContentException::class],
            'empty content' => ['{"id":"id","stop_reason":"end_turn","content":[]}', 'No output text found in Claude API response.', AiClientEmptyContentException::class],
            'unusable content' => ['{"id":"id","stop_reason":"end_turn","content":[null,{"type":"tool_use"},{"type":"text","text":42}]}', 'No output text found in Claude API response.', AiClientEmptyContentException::class],
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
        $this->expectException(InvalidJsonResponseException::class);

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

    private function client(HttpClient $http): ClaudeClient
    {
        $instruction = $this->createMock(Instruction::class);
        $instruction->expects(self::once())->method('instruct')->with(AdapterMode::Cli)->willReturn('Test instructions');

        return new ClaudeClient(new ClaudeConfig('test-key', 'https://example.test/ai', 'test-model', 'test-version', 1024), $http, new MessageBuilder(new MessageStringBuilder()), $instruction, new ResponseDecoder(new ResponseBuilder()));
    }
}
