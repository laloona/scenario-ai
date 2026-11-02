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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Instruction\AttributeInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\BaseInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ClassInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\DocumentationFinder;
use Stateforge\Scenario\Ai\Runtime\Instruction\DocumentationInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\GeneralInstructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterTypeListInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\QuestionLimitInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ScenarioListInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ValueTypeInstruction;
use Stateforge\Scenario\Ai\Runtime\SourceCodeReader;
use Stateforge\Scenario\Ai\Runtime\SourceCodeRegistry;
use Stateforge\Scenario\Ai\Tests\Files\ListedScenario;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use Stateforge\Scenario\Ai\Tests\Unit\SourceCodeRegistryMock;
use Stateforge\Scenario\Core\Attribute\AsScenario;
use Stateforge\Scenario\Core\Runtime\Metadata\Parameter\ParameterTypeRegistry;
use Stateforge\Scenario\Core\Runtime\ScenarioDefinition;
use Stateforge\Scenario\Core\Runtime\ScenarioRegistry;
use function file_put_contents;
use function mkdir;

#[CoversClass(GeneralInstructions::class)]
#[UsesClass(AdapterMode::class)]
#[UsesClass(Instructions::class)]
#[UsesClass(ScenarioListInstruction::class)]
#[UsesClass(ParameterTypeListInstruction::class)]
#[UsesClass(BaseInstruction::class)]
#[UsesClass(AttributeInstruction::class)]
#[UsesClass(DocumentationInstruction::class)]
#[UsesClass(DocumentationFinder::class)]
#[UsesClass(QuestionLimitInstruction::class)]
#[UsesClass(ValueTypeInstruction::class)]
#[UsesClass(ClassInstruction::class)]
#[UsesClass(SourceCodeReader::class)]
#[UsesClass(SourceCodeRegistry::class)]
#[Group('runtime')]
#[Small]
final class GeneralInstructionsTest extends FilesystemTestCase
{
    use SourceCodeRegistryMock;

    protected function setUp(): void
    {
        $this->resetSourceCodeRegistry();
        parent::setUp();
        ScenarioRegistry::getInstance()->clear();
        (new ReflectionClass(ParameterTypeRegistry::class))->getProperty('instance')->setValue(null, null);
    }

    protected function tearDown(): void
    {
        $this->resetSourceCodeRegistry();
        ScenarioRegistry::getInstance()->clear();
        (new ReflectionClass(ParameterTypeRegistry::class))->getProperty('instance')->setValue(null, null);
        parent::tearDown();
    }

    #[DataProvider('projectDocProvider')]
    public function testIncludesSectionHeadingsForConfiguredMode(bool $withProjectDoc, bool $allowProjectInformation, AdapterMode $mode, bool $withCounts, bool $withProjectScenarios, bool $withDocs): void
    {
        mkdir($this->testDirectory . '/framework');
        mkdir($this->testDirectory . '/project');
        file_put_contents($this->testDirectory . '/framework/framework.md', 'FRAMEWORK_MARKER');
        file_put_contents($this->testDirectory . '/project/project.md', 'PROJECT_MARKER');
        ScenarioRegistry::getInstance()->register(new ScenarioDefinition('main', ListedScenario::class, new AsScenario('listed', 'Project scenario marker'), []));
        $text = (new GeneralInstructions(
            $this->testDirectory . '/framework',
            7,
            3,
            $withProjectDoc ? $this->testDirectory . '/project' : null,
            null,
            $allowProjectInformation,
        ))->instruct($mode);

        self::assertStringContainsString('Introduction', $text);
        self::assertStringContainsString('Framework Attribute Rules', $text);
        self::assertStringContainsString('Available attributes:', $text);
        self::assertStringContainsString('Documentation', $text);
        self::assertStringContainsString('[framework.md]', $text);
        if ($withCounts) {
            self::assertStringContainsString('Conversation rules:', $text);
        } else {
            self::assertStringNotContainsString('Conversation rules:', $text);
        }
        self::assertStringContainsString('Some class definitions:', $text);
        self::assertStringContainsString('Existing Parameter Types', $text);
        self::assertStringContainsString('Built-in parameter types', $text);
        if ($allowProjectInformation || $mode !== AdapterMode::Cli) {
            self::assertStringContainsString('Existing Scenarios', $text);
        } else {
            self::assertStringNotContainsString('Existing Scenarios', $text);
        }
        if ($withProjectScenarios) {
            self::assertStringContainsString('Registered Scenarios', $text);
        } else {
            self::assertStringNotContainsString('Registered Scenarios', $text);
        }
        if ($withDocs) {
            self::assertStringContainsString('[project.md]', $text);
        } else {
            self::assertStringNotContainsString('[project.md]', $text);
        }
    }

    /** @return iterable<string, array{bool, bool, AdapterMode, bool, bool, bool}> */
    public static function projectDocProvider(): iterable
    {
        foreach ([false, true] as $withDoc) {
            foreach ([false, true] as $permission) {
                $label = (int) $withDoc . '-' . (int) $permission;
                yield 'cli-' . $label => [$withDoc, $permission, AdapterMode::Cli, true, $permission, $withDoc];
                yield 'skill-' . $label => [$withDoc, $permission, AdapterMode::Skill, false, false, false];
                yield 'all-' . $label => [$withDoc, $permission, AdapterMode::All, false, false, $withDoc];
            }
        }
    }
}
