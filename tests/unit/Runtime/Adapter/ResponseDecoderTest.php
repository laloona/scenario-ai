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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseDecoder;
use Stateforge\Scenario\Ai\Runtime\Exception\InvalidJsonResponseException;
use Stateforge\Scenario\Ai\Runtime\Exception\MissingResponseElementException;
use Stateforge\Scenario\Ai\Runtime\Exception\WrongResponseStructureException;

#[CoversClass(ResponseDecoder::class)]
#[UsesClass(ResponseBuilder::class)]
#[UsesClass(AiResponse::class)]
#[UsesClass(InvalidJsonResponseException::class)]
#[UsesClass(MissingResponseElementException::class)]
#[UsesClass(WrongResponseStructureException::class)]
#[Group('runtime')]
#[Small]
final class ResponseDecoderTest extends TestCase
{
    public function testDecodesJsonObjectAndUsesProvidedId(): void
    {
        $response = (new ResponseDecoder(new ResponseBuilder()))->decode(
            'conversation-id',
            '{"id":"ignored","type":"parameter","php":"<?php class Demo {}","question":null,"classes":[],"match":null}',
        );

        self::assertSame('conversation-id', $response->id);
        self::assertSame(GenerationType::Parameter, $response->type);
        self::assertSame('<?php class Demo {}', $response->php);
        self::assertNull($response->question);
        self::assertSame([], $response->classes);
        self::assertNull($response->match);
    }

    public function testAcceptsExplicitNullFieldsAndEmptyClasses(): void
    {
        $response = (new ResponseDecoder(new ResponseBuilder()))->decode('id', '{"type":null,"php":null,"question":null,"classes":[],"match":null}');

        self::assertSame('id', $response->id);
        self::assertNull($response->php);
        self::assertNull($response->type);
        self::assertNull($response->question);
        self::assertSame([], $response->classes);
        self::assertNull($response->match);
    }

    public function testRejectsEmptyObjectWithMissingRequiredFields(): void
    {
        $this->expectException(MissingResponseElementException::class);
        $this->expectExceptionMessage('element type is missing in response.');

        (new ResponseDecoder(new ResponseBuilder()))->decode('id', '{}');
    }

    public function testRejectsResponseWithoutMatch(): void
    {
        $this->expectException(MissingResponseElementException::class);
        $this->expectExceptionMessage('element match is missing in response.');

        (new ResponseDecoder(new ResponseBuilder()))->decode(
            'id',
            '{"type":null,"php":null,"question":null,"classes":[]}',
        );
    }

    public function testRejectsMalformedJson(): void
    {
        $this->expectException(InvalidJsonResponseException::class);
        $this->expectExceptionMessage('AI output is not a JSON object.');

        (new ResponseDecoder(new ResponseBuilder()))->decode('id', '{invalid');
    }

    #[DataProvider('nonObjectProvider')]
    public function testRejectsNonObjectJson(string $json): void
    {
        $this->expectException(InvalidJsonResponseException::class);
        $this->expectExceptionMessage('AI output is not a JSON object.');

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
        $this->expectException(WrongResponseStructureException::class);
        $this->expectExceptionMessage('Invalid AI output structure.');

        (new ResponseDecoder(new ResponseBuilder()))->decode('id', $json);
    }

    /** @return array<string, array{string}> */
    public static function numericKeyProvider(): array
    {
        return ['list' => ['["value"]'], 'numeric object key' => ['{"0":"value"}']];
    }
}
