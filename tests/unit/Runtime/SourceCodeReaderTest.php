<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Exception\ClassFileDoesNotExistException;
use Stateforge\Scenario\Ai\Runtime\SourceCodeReader;
use stdClass;
use function file_get_contents;
use const PHP_EOL;

#[CoversClass(SourceCodeReader::class)]
#[UsesClass(ClassFileDoesNotExistException::class)]
#[Group('runtime')]
#[Small]
final class SourceCodeReaderTest extends TestCase
{
    public function testEmptyListReturnsLineBreak(): void
    {
        self::assertSame(PHP_EOL, (new SourceCodeReader())->read([]));
    }

    public function testReadsSourceFilesInRequestedOrder(): void
    {
        $first = file_get_contents(__FILE__);
        $second = file_get_contents(__DIR__ . '/../../../src/Runtime/SourceCodeReader.php');
        self::assertIsString($first);
        self::assertIsString($second);

        self::assertSame(
            PHP_EOL . $second . PHP_EOL . $first . PHP_EOL,
            (new SourceCodeReader())->read([SourceCodeReader::class, self::class]),
        );
    }

    public function testRejectsInternalClassWithoutSourceFile(): void
    {
        $this->expectException(ClassFileDoesNotExistException::class);
        $this->expectExceptionMessage('class file for class "stdClass" does not exist.');

        (new SourceCodeReader())->read([stdClass::class]);
    }
}
