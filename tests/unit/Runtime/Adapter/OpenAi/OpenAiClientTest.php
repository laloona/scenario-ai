<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Adapter\OpenAi;

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
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiConfig;
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiFormat;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseDecoder;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;

#[CoversClass(OpenAiClient::class)]
#[UsesClass(OpenAiConfig::class)]
#[UsesClass(OpenAiFormat::class)]
#[UsesClass(AiAudit::class)]
#[UsesClass(AiRequest::class)]
#[UsesClass(AiResponse::class)]
#[UsesClass(ResponseDecoder::class)]
#[UsesClass(ResponseBuilder::class)]
#[Group('runtime')]
#[Small]
final class OpenAiClientTest extends TestCase
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
                self::assertSame('Bearer test-key', $request->getHeaderLine('Authorization'));
                self::assertTrue($payload['store']);
                self::assertSame('Test instructions', $payload['instructions']);
                self::assertSame('Generate a scenario', $payload['input']);
                self::assertSame((new OpenAiFormat())->format(), $payload['text']);
                if ($withHistory) {
                    self::assertSame('previous-id', $payload['previous_response_id']);
                } else {
                    self::assertArrayNotHasKey('previous_response_id', $payload);
                }
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
        $this->expectExceptionMessage('OpenAI API returned HTTP ' . $status . ': API failure');

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
            'non object' => ['null', 'Invalid OpenAI response.'],
            'missing id' => ['{}', 'OpenAI response has no valid id.'],
            'invalid id' => ['{"id":42}', 'OpenAI response has no valid id.'],
            'not completed' => ['{"id":"id","status":"incomplete"}', 'OpenAI response was not completed.'],
            'missing output' => ['{"id":"id","status":"completed"}', 'OpenAI response has no valid output.'],
            'invalid output' => ['{"id":"id","status":"completed","output":42}', 'OpenAI response has no valid output.'],
            'empty output' => ['{"id":"id","status":"completed","output":[]}', 'No output text found in OpenAI response.'],
            'invalid text' => ['{"id":"id","status":"completed","output":[{"type":"message","content":[{"type":"output_text","text":42}]}]}', 'OpenAI output text is invalid.'],
            'refused' => ['{"id":"id","status":"completed","output":[{"type":"message","content":[{"type":"refusal","refusal":"Not allowed"}]}]}', 'OpenAI refused the request: Not allowed'],
            'refusal without reason' => ['{"id":"id","status":"completed","output":[{"type":"message","content":[{"type":"refusal"}]}]}', 'OpenAI refused the request: Unknown reason'],
            'unusable outputs' => ['{"id":"id","status":"completed","output":[null,{"type":"reasoning"},{"type":"message","content":false},{"type":"message","content":[null,{"type":"other"}]}]}', 'No output text found in OpenAI response.'],
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
        return json_encode(['id' => 'answer-id', 'status' => 'completed', 'output' => [null, ['type' => 'reasoning'], ['type' => 'message', 'content' => null], ['type' => 'message', 'content' => [null, ['type' => 'other'], ['type' => 'output_text', 'text' => $text]]]]], JSON_THROW_ON_ERROR);
    }

    private function client(HttpClient $http): OpenAiClient
    {
        $instruction = self::createStub(Instruction::class);
        $instruction->method('instruct')->willReturn('Test instructions');

        return new OpenAiClient(new OpenAiConfig('test-key', 'https://example.test/ai', 'test-model'), $http, $instruction, new ResponseDecoder(new ResponseBuilder()));
    }
}
