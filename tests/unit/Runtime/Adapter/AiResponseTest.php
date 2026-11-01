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
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;

#[CoversClass(AiResponse::class)]
#[Small]
final class AiResponseTest extends TestCase
{
    public function testPreservesEmptyResponseFields(): void
    {
        $response = new AiResponse(null, null, null, null, [], null);

        self::assertNull($response->id);
        self::assertNull($response->php);
        self::assertNull($response->type);
        self::assertNull($response->question);
        self::assertSame([], $response->classes);
        self::assertNull($response->match);
    }

    #[DataProvider('typeProvider')]
    public function testPreservesProvidedResponseFields(GenerationType $type): void
    {
        $response = new AiResponse('response-id', '<?php class Demo {}', $type, 'Which state?', ['App\\Service'], 'App\\ExistingImplementation');

        self::assertSame('response-id', $response->id);
        self::assertSame('<?php class Demo {}', $response->php);
        self::assertSame($type, $response->type);
        self::assertSame('Which state?', $response->question);
        self::assertSame(['App\\Service'], $response->classes);
        self::assertSame('App\\ExistingImplementation', $response->match);
    }

    /** @return iterable<string, array{GenerationType}> */
    public static function typeProvider(): iterable
    {
        foreach (GenerationType::cases() as $type) {
            yield $type->value => [$type];
        }
    }
}
