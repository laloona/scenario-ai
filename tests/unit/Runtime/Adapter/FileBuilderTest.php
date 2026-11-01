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
use PHPUnit\Framework\Attributes\Small;
use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Runtime\Adapter\FileBuilder;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use function implode;
use function restore_error_handler;
use function set_error_handler;
use const DIRECTORY_SEPARATOR;

#[CoversClass(FileBuilder::class)]
#[Small]
final class FileBuilderTest extends FilesystemTestCase
{
    public function testEmptyPathDoesNotRenderInstruction(): void
    {
        $instruction = $this->createMock(Instruction::class);
        $instruction->expects(self::never())->method('instruct');

        self::assertNull((new FileBuilder($instruction))->build([]));
    }

    public function testCreatesNestedDirectoriesAndOverwritesExistingSkill(): void
    {
        $instruction = $this->createMock(Instruction::class);
        $instruction->expects(self::exactly(2))->method('instruct')->with(AdapterMode::Skill)->willReturnOnConsecutiveCalls('Original', 'Updated');
        $builder = new FileBuilder($instruction);
        $path = [$this->testDirectory, 'nested', 'skills', 'SKILL.md'];
        $file = implode(DIRECTORY_SEPARATOR, $path);

        self::assertSame($file, $builder->build($path));
        self::assertStringEqualsFile($file, 'Original');
        self::assertSame($file, $builder->build($path));
        self::assertStringEqualsFile($file, 'Updated');
    }

    public function testReturnsNullWhenFileCannotBeWritten(): void
    {
        $instruction = $this->createMock(Instruction::class);
        $instruction->expects(self::once())->method('instruct')->with(AdapterMode::Skill)->willReturn('Skill');
        // Writing a file over an existing directory fails consistently on all platforms.
        set_error_handler(static fn (): bool => true);
        try {
            self::assertNull((new FileBuilder($instruction))->build([$this->testDirectory]));
        } finally {
            restore_error_handler();
        }
    }
}
