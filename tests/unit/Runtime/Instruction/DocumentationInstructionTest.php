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
use Stateforge\Scenario\Ai\Runtime\Instruction\DocumentationInstruction;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use function file_put_contents;
use const PHP_EOL;

#[CoversClass(DocumentationInstruction::class)]
#[UsesClass(DocumentationFinder::class)]
#[UsesClass(DirectoryNotFoundException::class)]
#[Group('runtime')]
#[Small]
final class DocumentationInstructionTest extends FilesystemTestCase
{
    public function testIncludesFilenamesAndContentsInSortedOrder(): void
    {
        file_put_contents($this->testDirectory . '/b.md', 'Second');
        file_put_contents($this->testDirectory . '/a.md', 'First');
        file_put_contents($this->testDirectory . '/ignored.txt', 'Ignored');

        self::assertSame(
            'Documentation' . PHP_EOL . PHP_EOL . '[a.md]' . PHP_EOL . 'First' . PHP_EOL . PHP_EOL . '[b.md]' . PHP_EOL . 'Second',
            (new DocumentationInstruction($this->testDirectory))->instruct(),
        );
    }

    public function testEmptyDocumentationStillIncludesHeading(): void
    {
        self::assertSame('Documentation' . PHP_EOL . PHP_EOL, (new DocumentationInstruction($this->testDirectory))->instruct());
    }

    public function testMissingDirectoryPropagatesException(): void
    {
        $this->expectException(DirectoryNotFoundException::class);

        (new DocumentationInstruction($this->testDirectory . '/missing'))->instruct();
    }
}
