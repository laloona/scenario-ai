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
use Stateforge\Scenario\Ai\Runtime\Exception\MissingResponseElementException;
use Stateforge\Scenario\Ai\Runtime\Exception\WrongResponseTypeException;
use stdClass;

#[CoversClass(ResponseBuilder::class)]
#[UsesClass(AiResponse::class)]
#[UsesClass(MissingResponseElementException::class)]
#[UsesClass(WrongResponseTypeException::class)]
#[Group('runtime')]
#[Small]
final class ResponseBuilderTest extends TestCase
{
    public function testBuildMapsAllFieldsAndOverridesResponseId(): void
    {
        $response = (new ResponseBuilder())->build('actual-id', [
            'id' => 'ignored-id', 'type' => 'scenario', 'php' => '<?php class Demo {}',
            'question' => 'Which state?', 'classes' => [3 => stdClass::class], 'match' => self::class,
        ]);

        self::assertSame('actual-id', $response->id);
        self::assertSame(GenerationType::Scenario, $response->type);
        self::assertSame('<?php class Demo {}', $response->php);
        self::assertSame('Which state?', $response->question);
        self::assertSame([stdClass::class], $response->classes);
        self::assertSame(self::class, $response->match);
    }

    public function testBuildAcceptsNullValuesAndAnEmptyClassList(): void
    {
        $response = (new ResponseBuilder())->build('id', self::validResponse());

        self::assertSame('id', $response->id);
        self::assertNull($response->type);
        self::assertNull($response->php);
        self::assertNull($response->question);
        self::assertNull($response->match);
        self::assertSame([], $response->classes);
    }

    public function testBuildRecognizesParameterGeneration(): void
    {
        $fields = self::validResponse();
        $fields['type'] = 'parameter';

        self::assertSame(GenerationType::Parameter, (new ResponseBuilder())->build('id', $fields)->type);
    }

    #[DataProvider('requiredFields')]
    public function testRejectsMissingRequiredFields(string $field): void
    {
        $fields = self::validResponse();
        unset($fields[$field]);

        $this->expectException(MissingResponseElementException::class);
        $this->expectExceptionMessage('element ' . $field . ' is missing in response.');

        (new ResponseBuilder())->build('id', $fields);
    }

    /** @return iterable<string, array{string}> */
    public static function requiredFields(): iterable
    {
        foreach (['type', 'php', 'question', 'classes', 'match'] as $field) {
            yield $field => [$field];
        }
    }

    #[DataProvider('invalidFields')]
    public function testRejectsInvalidFieldTypes(string $field, mixed $value, string $expected): void
    {
        $fields = self::validResponse();
        $fields[$field] = $value;

        $this->expectException(WrongResponseTypeException::class);
        $this->expectExceptionMessage('expected ' . $expected . '.');

        (new ResponseBuilder())->build('id', $fields);
    }

    /** @return iterable<string, array{string, mixed, string}> */
    public static function invalidFields(): iterable
    {
        yield 'unknown generation type' => ['type', 'unknown', GenerationType::class . ' for "type"'];
        yield 'integer type' => ['type', 42, 'string for "type"'];
        yield 'array php' => ['php', [], 'string for "php"'];
        yield 'boolean question' => ['question', false, 'string for "question"'];
        yield 'integer match' => ['match', 42, 'string for "match"'];
        yield 'array match' => ['match', [], 'string for "match"'];
        yield 'null classes' => ['classes', null, 'array for "classes"'];
        yield 'string classes' => ['classes', 'invalid', 'array for "classes"'];
        yield 'null class entry' => ['classes', [null], 'string in [classes]'];
        yield 'array class entry' => ['classes', [[]], 'string in [classes]'];
        yield 'invalid class entry' => ['classes', [stdClass::class, 42], 'string in [classes]'];
    }

    #[DataProvider('missingClasses')]
    public function testPreservesClassNamesForLaterExistenceValidation(string $field, mixed $value): void
    {
        $fields = self::validResponse();
        $fields[$field] = $value;

        $response = (new ResponseBuilder())->build('id', $fields);

        self::assertSame($fields['classes'], $response->classes);
        self::assertSame($fields['match'], $response->match);
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function missingClasses(): iterable
    {
        yield 'requested class' => ['classes', [stdClass::class, 'Missing\\RequestedClass']];
        yield 'matching class' => ['match', 'Missing\\RequestedClass'];
    }

    public function testDeduplicatesClassNamesAndReturnsAContiguousListInFirstSeenOrder(): void
    {
        $fields = self::validResponse();
        $fields['classes'] = [3 => self::class, 7 => self::class, 12 => stdClass::class, 20 => self::class];

        $response = (new ResponseBuilder())->build('id', $fields);

        self::assertSame([self::class, stdClass::class], $response->classes);
    }

    /** @return array<string, mixed> */
    private static function validResponse(): array
    {
        return ['type' => null, 'php' => null, 'question' => null, 'classes' => [], 'match' => null];
    }
}
