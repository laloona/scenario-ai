<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Adapter;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseDecoder;

#[CoversClass(ResponseDecoder::class)]
#[UsesClass(ResponseBuilder::class)]
#[UsesClass(AiResponse::class)]
#[Group('runtime')]
#[Small]
final class ResponseDecoderTest extends TestCase
{
    public function testDecodesJsonObjectAndUsesProvidedId(): void
    {
        $response = (new ResponseDecoder(new ResponseBuilder()))->decode(
            'conversation-id',
            '{"id":"ignored","type":"parameter","php":"<?php class Demo {}","question":null,"classes":[]}',
        );

        self::assertSame('conversation-id', $response->id);
        self::assertSame(GenerationType::Parameter, $response->type);
        self::assertSame('<?php class Demo {}', $response->php);
        self::assertNull($response->question);
        self::assertSame([], $response->classes);
    }

    public function testEmptyObjectUsesDefaultResponseFields(): void
    {
        $response = (new ResponseDecoder(new ResponseBuilder()))->decode('id', '{}');

        self::assertSame('id', $response->id);
        self::assertNull($response->php);
        self::assertNull($response->type);
        self::assertNull($response->question);
        self::assertSame([], $response->classes);
    }

    public function testRejectsMalformedJson(): void
    {
        $this->expectException(JsonException::class);

        (new ResponseDecoder(new ResponseBuilder()))->decode('id', '{invalid');
    }

    #[DataProvider('nonObjectProvider')]
    public function testRejectsNonObjectJson(string $json): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Claude output is not a JSON object.');

        (new ResponseDecoder(new ResponseBuilder()))->decode('id', $json);
    }

    /** @return array<string, array{string}> */
    public static function nonObjectProvider(): array
    {
        return ['null' => ['null'], 'boolean' => ['true'], 'number' => ['42'], 'string' => ['"text"']];
    }

    #[DataProvider('numericKeyProvider')]
    public function testRejectsNumericKeys(string $json): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Claude output structure.');

        (new ResponseDecoder(new ResponseBuilder()))->decode('id', $json);
    }

    /** @return array<string, array{string}> */
    public static function numericKeyProvider(): array
    {
        return ['list' => ['["value"]'], 'numeric object key' => ['{"0":"value"}']];
    }
}
