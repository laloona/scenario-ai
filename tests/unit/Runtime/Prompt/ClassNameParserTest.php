<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Prompt;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Prompt\ClassNameParser;

#[CoversClass(ClassNameParser::class)]
#[Group('runtime')]
#[Small]
final class ClassNameParserTest extends TestCase
{
    /** @param list<string> $expected */
    #[DataProvider('promptProvider')]
    public function testExtractsUniqueQualifiedClassNames(string $prompt, array $expected): void
    {
        self::assertSame($expected, (new ClassNameParser())->parse($prompt));
    }

    /** @return array<string, array{string, list<string>}> */
    public static function promptProvider(): array
    {
        return [
            'empty' => ['', []],
            'plain words' => ['Create a scenario with User and Order', []],
            'qualified names' => ['Use App\\User and App\\Order.', ['App\\User', 'App\\Order']],
            'leading slash and duplicate' => ['\\App\\User, App\\User, \\App\\Order', ['App\\User', 'App\\Order']],
            'repeated identical names' => ['App\User App\User App\User', ['App\User']],
            'different case and leading slash' => ['\App\User app\user \APP\USER', ['App\User']],
            'first spelling and order preserved' => ['app\user App\Order APP\USER app\order App\Product', ['app\user', 'App\Order', 'App\Product']],
            'same short name in different namespaces' => ['App\User Domain\User App\User', ['App\User', 'Domain\User']],
            'identifiers' => ['Use App_2\\Model3\\_User.', ['App_2\\Model3\\_User']],
            'punctuation' => ['`App\\User` (App\\Order)', ['App\\User', 'App\\Order']],
        ];
    }
}
