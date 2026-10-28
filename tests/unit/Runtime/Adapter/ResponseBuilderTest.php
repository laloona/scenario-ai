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
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use stdClass;
use ValueError;

#[CoversClass(ResponseBuilder::class)]
#[CoversClass(AiResponse::class)]
#[Group('runtime')]
#[Small]
final class ResponseBuilderTest extends TestCase
{
    public function testBuildMapsFieldsAndOverridesResponseId(): void
    {
        $response = (new ResponseBuilder())->build('actual-id', [
            'id' => 'ignored-id', 'type' => 'scenario', 'php' => '<?php class Demo {}',
            'question' => 'Which state?', 'classes' => [stdClass::class],
        ]);

        self::assertSame('actual-id', $response->id);
        self::assertSame(GenerationType::Scenario, $response->type);
        self::assertSame('<?php class Demo {}', $response->php);
        self::assertSame('Which state?', $response->question);
        self::assertSame([stdClass::class], $response->classes);
    }

    public function testBuildRecognizesParameterGeneration(): void
    {
        self::assertSame(GenerationType::Parameter, (new ResponseBuilder())->build('id', ['type' => 'parameter'])->type);
    }

    /** @param array<string, mixed> $fields */
    #[DataProvider('invalidFieldsProvider')]
    public function testMissingNullOrInvalidFieldsUseDefaults(array $fields): void
    {
        $response = (new ResponseBuilder())->build('id', $fields);

        self::assertSame('id', $response->id);
        self::assertNull($response->type);
        self::assertNull($response->php);
        self::assertNull($response->question);
        self::assertSame([], $response->classes);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function invalidFieldsProvider(): array
    {
        return [
            'missing' => [[]],
            'null' => [['type' => null, 'php' => null, 'question' => null, 'classes' => null]],
            'invalid types' => [['type' => 1, 'php' => [], 'question' => false, 'classes' => 'invalid']],
        ];
    }

    public function testFiltersInvalidClassNamesAndReindexesList(): void
    {
        $response = (new ResponseBuilder())->build('id', ['classes' => [
            3 => 'MissingScenarioAiClass', 8 => stdClass::class, 12 => 42,
            20 => self::class, 25 => null, 30 => new stdClass(),
        ]]);

        self::assertSame([stdClass::class, self::class], $response->classes);
    }

    public function testRejectsUnknownGenerationType(): void
    {
        $this->expectException(ValueError::class);

        (new ResponseBuilder())->build('id', ['type' => 'unknown']);
    }
}
