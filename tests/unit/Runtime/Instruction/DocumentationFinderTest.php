<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Instruction;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use Stateforge\Scenario\Ai\Runtime\Exception\Instruction\DirectoryNotFoundException;
use Stateforge\Scenario\Ai\Runtime\Instruction\DocumentationFinder;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use function file_put_contents;
use function mkdir;
use const DIRECTORY_SEPARATOR;

#[CoversClass(DocumentationFinder::class)]
#[UsesClass(DirectoryNotFoundException::class)]
#[Group('runtime')]
#[Small]
final class DocumentationFinderTest extends FilesystemTestCase
{
    public function testFindsOnlyMarkdownFilesRecursivelyInSortedOrder(): void
    {
        mkdir($this->testDirectory . '/nested');
        mkdir($this->testDirectory . '/directory.md');
        file_put_contents($this->testDirectory . '/z.md', 'Last');
        file_put_contents($this->testDirectory . '/a.MD', 'First');
        file_put_contents($this->testDirectory . '/nested/b.md', 'Nested');
        file_put_contents($this->testDirectory . '/ignored.txt', 'Ignored');

        self::assertSame([
            $this->testDirectory . DIRECTORY_SEPARATOR . 'a.MD',
            $this->testDirectory . DIRECTORY_SEPARATOR . 'nested' . DIRECTORY_SEPARATOR . 'b.md',
            $this->testDirectory . DIRECTORY_SEPARATOR . 'z.md',
        ], (new DocumentationFinder())->find($this->testDirectory));
    }

    public function testEmptyDirectoryReturnsEmptyList(): void
    {
        self::assertSame([], (new DocumentationFinder())->find($this->testDirectory));
    }

    public function testMissingDirectoryThrows(): void
    {
        $this->expectException(DirectoryNotFoundException::class);
        $this->expectExceptionMessage('Documentation directory does not exist: ' . $this->testDirectory . '/missing');

        (new DocumentationFinder())->find($this->testDirectory . '/missing');
    }
}
