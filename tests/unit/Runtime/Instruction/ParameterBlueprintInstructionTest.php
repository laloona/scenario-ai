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
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterBlueprintInstruction;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use function file_put_contents;
use const PHP_EOL;

#[CoversClass(ParameterBlueprintInstruction::class)]
#[Group('runtime')]
#[Small]
final class ParameterBlueprintInstructionTest extends FilesystemTestCase
{
    public function testIncludesConfiguredDirectoryAndDerivedNamespace(): void
    {
        $path = $this->testDirectory . '/parameter.blueprint';
        file_put_contents($path, 'Parameter blueprint contents');
        $text = (new ParameterBlueprintInstruction($path, 'scenario/parameter'))->instruct();

        self::assertStringContainsString('directory: scenario/parameter, namespace: Scenario\\Parameter', $text);
        self::assertStringContainsString('Parameter Type Blueprint: ' . PHP_EOL . 'Parameter blueprint contents', $text);
    }

    public function testNullDirectoryOmitsConfiguredParameterType(): void
    {
        $path = $this->testDirectory . '/parameter.blueprint';
        file_put_contents($path, 'Parameter blueprint contents');
        $text = (new ParameterBlueprintInstruction($path, null))->instruct();

        self::assertStringNotContainsString('Configured Parameter Type:', $text);
        self::assertStringContainsString('Parameter blueprint contents', $text);
    }
}
