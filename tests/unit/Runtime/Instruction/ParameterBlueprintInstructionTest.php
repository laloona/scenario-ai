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
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterBlueprintInstruction;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use function file_put_contents;

#[CoversClass(ParameterBlueprintInstruction::class)]
#[Group('runtime')]
#[Small]
final class ParameterBlueprintInstructionTest extends FilesystemTestCase
{
    public function testIncludesSectionHeadingsWithConfiguredDirectory(): void
    {
        $path = $this->testDirectory . '/parameter.blueprint';
        file_put_contents($path, 'Parameter blueprint contents');
        $text = (new ParameterBlueprintInstruction($path, 'scenario/parameter'))->instruct(AdapterMode::Cli);

        self::assertStringContainsString('ParameterType Rules', $text);
        self::assertStringContainsString('Configured Parameter Types:', $text);
        self::assertStringContainsString('ParameterType namespace substitution example:', $text);
        self::assertStringContainsString('Parameter Type Blueprint:', $text);

    }

    public function testIncludesSectionHeadingsWithoutConfiguredDirectory(): void
    {
        $path = $this->testDirectory . '/parameter.blueprint';
        file_put_contents($path, 'Parameter blueprint contents');
        $text = (new ParameterBlueprintInstruction($path, null))->instruct(AdapterMode::Cli);

        self::assertStringContainsString('ParameterType Rules', $text);
        self::assertStringNotContainsString('Configured Parameter Types:', $text);
        self::assertStringContainsString('ParameterType namespace substitution example:', $text);
        self::assertStringContainsString('Parameter Type Blueprint:', $text);

    }
}
