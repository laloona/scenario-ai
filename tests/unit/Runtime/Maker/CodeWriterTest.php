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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use RuntimeException;
use Stateforge\Scenario\Ai\Runtime\Maker\CodeWriter;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use function file_get_contents;
use function file_put_contents;

#[CoversClass(CodeWriter::class)]
#[Group('runtime')]
#[Small]
final class CodeWriterTest extends FilesystemTestCase
{
    public function testCreatesParentDirectoriesAndWritesAiFile(): void
    {
        $target = $this->testDirectory . '/nested/scenarios/Demo.php';
        $code = '<?php class Demo {}';
        $path = (new CodeWriter())->write($target, $code);

        self::assertSame($target . '.ai', $path);
        self::assertSame($code, file_get_contents($path));
        self::assertFileDoesNotExist($target);
    }

    public function testRejectsParentPathThatIsAlreadyAFile(): void
    {
        $parent = $this->testDirectory . '/existing-file';
        file_put_contents($parent, 'Original');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not create directory "' . $parent . '/nested".');

        // mkdir emits a warning as well as returning false for this expected failure.
        @(new CodeWriter())->write($parent . '/nested/Demo.php', '<?php');
    }

    public function testPreservesExistingFilesAndChoosesFirstAvailableSuffix(): void
    {
        $target = $this->testDirectory . '/Demo.php';
        file_put_contents($target, 'Original');
        file_put_contents($target . '.ai', 'First draft');
        file_put_contents($target . '.1.ai', 'Second draft');
        file_put_contents($target . '.3.ai', 'Later draft');
        $path = (new CodeWriter())->write($target, 'New draft');

        self::assertSame($target . '.2.ai', $path);
        self::assertSame('New draft', file_get_contents($path));
        self::assertSame('Original', file_get_contents($target));
        self::assertSame('First draft', file_get_contents($target . '.ai'));
        self::assertSame('Second draft', file_get_contents($target . '.1.ai'));
        self::assertSame('Later draft', file_get_contents($target . '.3.ai'));
    }
}
