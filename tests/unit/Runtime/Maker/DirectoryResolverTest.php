<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Maker;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stateforge\Scenario\Ai\Runtime\Maker\DirectoryResolver;
use const DIRECTORY_SEPARATOR;

#[CoversClass(DirectoryResolver::class)]
#[Group('runtime')]
#[Small]
final class DirectoryResolverTest extends TestCase
{
    /** @param list<string> $directories */
    #[DataProvider('directoryProvider')]
    public function testResolvesMatchingNamespaceOrFallsBackToFirstDirectory(array $directories, string $base, string $namespace, string $expected): void
    {
        self::assertSame($expected, (new DirectoryResolver($directories, $base))->resolve($namespace));
    }

    /** @return array<string, array{list<string>, string, string, string}> */
    public static function directoryProvider(): array
    {
        return [
            'exact' => [['scenario/main'], 'Stateforge\\Suite', 'Stateforge\\Suite\\Scenario\\Main', 'scenario/main'],
            'child' => [['scenario/main'], 'Stateforge\\Suite', 'Stateforge\\Suite\\Scenario\\Main\\Users\\Active', 'scenario/main' . DIRECTORY_SEPARATOR . 'Users' . DIRECTORY_SEPARATOR . 'Active'],
            'second directory' => [['scenario/main', 'scenario/other'], 'Suite', 'Suite\\Scenario\\Other', 'scenario/other'],
            'longest matching namespace' => [['scenario', 'scenario/main'], 'Suite', 'Suite\\Scenario\\Main\\Child', 'scenario/main' . DIRECTORY_SEPARATOR . 'Child'],
            'specific listed first' => [['scenario/main', 'scenario'], 'Suite', 'Suite\\Scenario\\Main', 'scenario/main'],
            'fallback' => [['scenario/main', 'scenario/other'], 'Suite', 'Unknown\\Class', 'scenario/main'],
            'segment boundary' => [['scenario/main'], 'Suite', 'Suite\\Scenario\\MainExtra', 'scenario/main'],
            'trim slashes' => [['scenario/main/'], '\\Suite\\', '\\Suite\\Scenario\\Main\\Child\\', 'scenario/main' . DIRECTORY_SEPARATOR . 'Child'],
        ];
    }

    public function testRejectsEmptyDirectoryList(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No directories configured.');

        (new DirectoryResolver([], 'Suite'))->resolve('Any');
    }
}
