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
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Exception\ClassFileDoesNotExistException;
use Stateforge\Scenario\Ai\Runtime\Instruction\ClassInstruction;
use Stateforge\Scenario\Ai\Runtime\SourceCodeReader;
use Stateforge\Scenario\Ai\Tests\Files\PackageParameterType;
use Stateforge\Scenario\Ai\Tests\Files\Project\ProjectParameterType;
use stdClass;
use function dirname;
use function file_get_contents;
use const PHP_EOL;

#[CoversClass(ClassInstruction::class)]
#[UsesClass(SourceCodeReader::class)]
#[UsesClass(ClassFileDoesNotExistException::class)]
#[Group('runtime')]
#[Small]
final class ClassInstructionTest extends TestCase
{
    public function testIncludesHeadlineAndSourceFilesInRequestedOrder(): void
    {
        $packageSource = file_get_contents(dirname(__DIR__, 3) . '/files/PackageParameterType.php');
        $projectSource = file_get_contents(dirname(__DIR__, 3) . '/files/Project/ProjectParameterType.php');
        self::assertIsString($packageSource);
        self::assertIsString($projectSource);

        self::assertSame(
            'Available classes: ' . PHP_EOL . PHP_EOL . PHP_EOL . $projectSource . PHP_EOL . $packageSource . PHP_EOL,
            (new ClassInstruction('Available classes', [ProjectParameterType::class, PackageParameterType::class]))->instruct(),
        );
    }

    public function testEmptyClassListReturnsHeadlineWithoutSourceCode(): void
    {
        self::assertSame(
            'Class definitions: ' . PHP_EOL . PHP_EOL . PHP_EOL,
            (new ClassInstruction('Class definitions', []))->instruct(),
        );
    }

    public function testRejectsInternalClassWithoutSourceFile(): void
    {
        $this->expectException(ClassFileDoesNotExistException::class);
        $this->expectExceptionMessage('class file for class "stdClass" does not exist.');

        (new ClassInstruction('Class definitions', [stdClass::class]))->instruct();
    }
}
