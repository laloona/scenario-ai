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
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Instruction\AttributeInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ClassInstruction;
use Stateforge\Scenario\Ai\Runtime\SourceCodeReader;
use Stateforge\Scenario\Ai\Runtime\SourceCodeRegistry;
use Stateforge\Scenario\Ai\Tests\Unit\SourceCodeRegistryMock;
use Stateforge\Scenario\Core\Attribute\ApplyScenario;
use Stateforge\Scenario\Core\Attribute\AsParameterType;
use Stateforge\Scenario\Core\Attribute\AsScenario;
use Stateforge\Scenario\Core\Attribute\Parameter;
use Stateforge\Scenario\Core\Attribute\ParameterTypeCondition;
use Stateforge\Scenario\Core\Attribute\RefreshDatabase;

#[CoversClass(AttributeInstruction::class)]
#[UsesClass(SourceCodeReader::class)]
#[UsesClass(ClassInstruction::class)]
#[UsesClass(SourceCodeRegistry::class)]
#[Group('runtime')]
#[Small]
final class AttributeInstructionTest extends TestCase
{
    use SourceCodeRegistryMock;

    protected function setUp(): void
    {
        $this->resetSourceCodeRegistry();
    }

    protected function tearDown(): void
    {
        $this->resetSourceCodeRegistry();
    }

    public function testIncludesSectionHeadingsAndSupportedClassNames(): void
    {
        $text = (new AttributeInstruction())->instruct(AdapterMode::Cli);

        self::assertStringContainsString('Framework Attribute Rules', $text);
        self::assertStringContainsString('Available attributes:', $text);
        self::assertStringContainsString('class AsScenario', $text);
        self::assertStringContainsString('class AsParameterType', $text);
        self::assertStringContainsString('class ApplyScenario', $text);
        self::assertStringContainsString('class Parameter', $text);
        self::assertStringContainsString('class ParameterTypeCondition', $text);
        self::assertStringContainsString('class RefreshDatabase', $text);
    }

    public function testRegistersAllProvidedAttributeDefinitions(): void
    {
        $registry = new SourceCodeRegistry();
        self::assertFalse($registry->hasClass(AsScenario::class));

        (new AttributeInstruction())->instruct(AdapterMode::Cli);

        foreach ([AsScenario::class, AsParameterType::class, ApplyScenario::class, Parameter::class, ParameterTypeCondition::class, RefreshDatabase::class] as $class) {
            self::assertTrue($registry->hasClass($class), $class);
        }
        self::assertFalse($registry->hasClass(self::class));
    }
}
