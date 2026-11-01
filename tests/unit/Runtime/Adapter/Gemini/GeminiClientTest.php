<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Adapter\Gemini;

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
use Stateforge\Scenario\Ai\Runtime\Adapter\Gemini\GeminiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\Gemini\GeminiConfig;
use Stateforge\Scenario\Ai\Runtime\Adapter\Gemini\GeminiFormat;
use Stateforge\Scenario\Ai\Runtime\Adapter\Gemini\MessagePartsBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\Adapter\MessageBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseDecoder;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Exception\InvalidJsonResponseException;
use function hash;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;

#[CoversClass(GeminiClient::class)]
#[UsesClass(GeminiConfig::class)]
#[UsesClass(GeminiFormat::class)]
#[UsesClass(MessagePartsBuilder::class)]
#[UsesClass(MessageBuilder::class)]
#[UsesClass(AiAudit::class)]
#[UsesClass(AiRequest::class)]
#[UsesClass(AiResponse::class)]
#[UsesClass(ResponseBuilder::class)]
#[UsesClass(ResponseDecoder::class)]
#[UsesClass(InvalidJsonResponseException::class)]
#[Group('runtime')]
#[Small]
final class GeminiClientTest extends TestCase
{
    #[DataProvider('history')]
    public function testSendsConfiguredRequestWithGeminiPartsAndDecodesResponse(bool $withHistory): void
    {
        $answer = '{"php":"<?php class Demo {}","type":"scenario","question":null,"classes":[],"match":null}';
        $http = $this->createMock(HttpClient::class);
        $http->expects(self::once())->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request) use ($withHistory, $answer): ResponseInterface {
                self::assertSame('POST', $request->getMethod());
                self::assertSame('https://example.test/models/model%20name:generateContent', (string) $request->getUri());
                self::assertSame('test-key', $request->getHeaderLine('x-goog-api-key'));
                self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
                $payload = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
                self::assertIsArray($payload);
                self::assertSame(['parts' => [['text' => 'Test instructions']]], $payload['systemInstruction']);
                self::assertSame((new GeminiFormat())->format(), $payload['generationConfig']);
                $expected = $withHistory ? [
                    ['role' => 'user', 'parts' => [['text' => 'Initial prompt']]],
                    ['role' => 'model', 'parts' => [['text' => '{"php":null,"type":null,"question":"Which state?","classes":[],"match":null}']]],
                ] : [];
                $expected[] = ['role' => 'user', 'parts' => [['text' => 'Generate a scenario']]];
                self::assertSame($expected, $payload['contents']);
                return new Response(200, [], $this->responseBody($answer));
            },
        );
        $audits = $withHistory ? [new AiAudit(
            new AiRequest(null, 'Initial prompt', 1),
            new AiResponse('previous', null, null, 'Which state?', [], null),
        )] : [];

        $response = $this->client($http)->request(new AiRequest(null, 'Generate a scenario', 2), $audits);

        self::assertSame('gemini-' . hash('sha256', $answer), $response->id);
        self::assertSame('<?php class Demo {}', $response->php);
        self::assertSame(GenerationType::Scenario, $response->type);
        self::assertNull($response->question);
        self::assertSame([], $response->classes);
        self::assertNull($response->match);
    }

    /** @return iterable<string, array{bool}> */
    public static function history(): iterable
    {
        yield 'first request' => [false];
        yield 'with history' => [true];
    }

    public function testJoinsTextPartsAndSkipsUnusableParts(): void
    {
        $body = json_encode(['candidates' => [[
            'finishReason' => 'STOP',
            'content' => ['parts' => [null, ['text' => 42], ['other' => 'ignored'],
                ['text' => '{"php":null,"type":"parameter",'],
                ['text' => '"question":null,"classes":[],"match":null}'],
            ]],
        ]]], JSON_THROW_ON_ERROR);
        $http = self::createStub(HttpClient::class);
        $http->method('sendRequest')->willReturn(new Response(200, [], $body));

        $response = $this->client($http)->request(new AiRequest(null, 'Generate', 1), []);

        self::assertSame(GenerationType::Parameter, $response->type);
    }

    #[DataProvider('httpErrors')]
    public function testRejectsHttpErrors(int $status): void
    {
        $http = self::createStub(HttpClient::class);
        $http->method('sendRequest')->willReturn(new Response($status, [], 'API failure'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Gemini API returned HTTP ' . $status . ': API failure');

        $this->client($http)->request(new AiRequest(null, 'Generate', 1), []);
    }

    /** @return iterable<string, array{int}> */
    public static function httpErrors(): iterable
    {
        yield 'client error' => [400];
        yield 'server error' => [500];
    }

    #[DataProvider('invalidResponses')]
    public function testRejectsInvalidProviderResponses(string $body, string $message): void
    {
        $http = self::createStub(HttpClient::class);
        $http->method('sendRequest')->willReturn(new Response(200, [], $body));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->client($http)->request(new AiRequest(null, 'Generate', 1), []);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidResponses(): iterable
    {
        yield 'non object' => ['null', 'Invalid Gemini response.'];
        yield 'missing candidates' => ['{}', 'Gemini returned no candidates'];
        yield 'invalid candidates' => ['{"candidates":false}', 'Gemini returned no candidates'];
        yield 'empty candidates' => ['{"candidates":[]}', 'Gemini returned no candidates'];
        yield 'invalid candidate' => ['{"candidates":[null]}', 'Gemini returned no candidates'];
        yield 'unfinished' => ['{"candidates":[{"finishReason":"MAX_TOKENS"}]}', 'Gemini response was not completed: MAX_TOKENS'];
        yield 'missing finish reason' => ['{"candidates":[{}]}', 'Gemini response was not completed: unknown'];
        yield 'invalid finish reason' => ['{"candidates":[{"finishReason":42}]}', 'Gemini response was not completed: unknown'];
        yield 'missing parts' => ['{"candidates":[{"finishReason":"STOP"}]}', 'Gemini response has no content parts.'];
        yield 'invalid content' => ['{"candidates":[{"finishReason":"STOP","content":42}]}', 'Gemini response has no content parts.'];
        yield 'invalid parts' => ['{"candidates":[{"finishReason":"STOP","content":{"parts":42}}]}', 'Gemini response has no content parts.'];
        yield 'empty parts' => ['{"candidates":[{"finishReason":"STOP","content":{"parts":[]}}]}', 'No output text found in Gemini response.'];
        yield 'invalid text' => ['{"candidates":[{"finishReason":"STOP","content":{"parts":[{"text":42}]}}]}', 'No output text found in Gemini response.'];
    }

    public function testRejectsMalformedProviderJson(): void
    {
        $http = self::createStub(HttpClient::class);
        $http->method('sendRequest')->willReturn(new Response(200, [], '{invalid'));
        $this->expectException(JsonException::class);

        $this->client($http)->request(new AiRequest(null, 'Generate', 1), []);
    }

    public function testRejectsMalformedStructuredOutput(): void
    {
        $http = self::createStub(HttpClient::class);
        $http->method('sendRequest')->willReturn(new Response(200, [], $this->responseBody('{invalid')));
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

    private function responseBody(string $text): string
    {
        return json_encode(['candidates' => [[
            'finishReason' => 'STOP', 'content' => ['parts' => [['text' => $text]]],
        ]]], JSON_THROW_ON_ERROR);
    }

    private function client(HttpClient $http): GeminiClient
    {
        $instruction = $this->createMock(Instruction::class);
        $instruction->expects(self::once())->method('instruct')->with(AdapterMode::Cli)->willReturn('Test instructions');

        return new GeminiClient(
            new GeminiConfig('test-key', 'https://example.test/models/', 'model name'),
            $http,
            new MessageBuilder(new MessagePartsBuilder(), 'role', 'parts', 'user', 'model'),
            $instruction,
            new ResponseDecoder(new ResponseBuilder()),
        );
    }
}
