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

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Ai\Runtime\AstBuilder;
use Stateforge\Scenario\Ai\Runtime\Configuration;
use Stateforge\Scenario\Ai\Runtime\Context;
use Stateforge\Scenario\Ai\Runtime\Exception\AttributeValidatorException;
use Stateforge\Scenario\Ai\Runtime\Exception\ClassDoesNotExistException;
use Stateforge\Scenario\Ai\Runtime\Instruction\AttributeInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\BaseInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\BlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ClassInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\DocumentationFinder;
use Stateforge\Scenario\Ai\Runtime\Instruction\DocumentationInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\FinalValidationInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\GeneralInstructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterBlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterImplementationInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterTypeListInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\QuestionLimitInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ScenarioBlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ScenarioListInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ValueTypeInstruction;
use Stateforge\Scenario\Ai\Runtime\ScenarioAst;
use Stateforge\Scenario\Ai\Runtime\SourceCodeReader;
use Stateforge\Scenario\Ai\Runtime\SourceCodeRegistry;
use Stateforge\Scenario\Ai\Runtime\Validation\AttributeFinder;
use Stateforge\Scenario\Ai\Runtime\Validation\AttributeValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\ClassValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\CodeValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\InheritanceValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\SyntaxValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\Validators;
use Stateforge\Scenario\Ai\Tests\Files\ListedScenario;
use Stateforge\Scenario\Ai\Tests\Unit\SourceCodeRegistryMock;
use Stateforge\Scenario\Core\Attribute\ApplyScenario;
use Stateforge\Scenario\Core\Attribute\AsParameterType;
use Stateforge\Scenario\Core\Attribute\AsScenario;
use Stateforge\Scenario\Core\Attribute\Parameter;
use Stateforge\Scenario\Core\Attribute\ParameterTypeCondition;
use Stateforge\Scenario\Core\Attribute\RefreshDatabase;
use Stateforge\Scenario\Core\Contract\ScenarioInterface;
use Stateforge\Scenario\Core\ParameterType;
use Stateforge\Scenario\Core\ParameterTypeDefinition;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration as CoreConfiguration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Value\SuiteValue;
use Stateforge\Scenario\Core\Runtime\Metadata\Parameter\ParameterTypeRegistry;
use Stateforge\Scenario\Core\Runtime\Metadata\ValueType\BooleanType;
use Stateforge\Scenario\Core\Runtime\Metadata\ValueType\FloatType;
use Stateforge\Scenario\Core\Runtime\Metadata\ValueType\IntegerType;
use Stateforge\Scenario\Core\Runtime\Metadata\ValueType\StringType;
use Stateforge\Scenario\Core\Runtime\ScenarioDefinition;
use Stateforge\Scenario\Core\Runtime\ScenarioRegistry;
use Stateforge\Scenario\Core\Scenario;
use function file_put_contents;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(Context::class)]
#[UsesClass(AdapterMode::class)]
#[UsesClass(Ai::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(AdapterConfiguration::class)]
#[UsesClass(AiRequest::class)]
#[UsesClass(AiResponse::class)]
#[UsesClass(AstBuilder::class)]
#[UsesClass(ScenarioAst::class)]
#[UsesClass(SourceCodeReader::class)]
#[UsesClass(SourceCodeRegistry::class)]
#[UsesClass(Instructions::class)]
#[UsesClass(GeneralInstructions::class)]
#[UsesClass(ScenarioListInstruction::class)]
#[UsesClass(ParameterTypeListInstruction::class)]
#[UsesClass(BaseInstruction::class)]
#[UsesClass(AttributeInstruction::class)]
#[UsesClass(DocumentationInstruction::class)]
#[UsesClass(DocumentationFinder::class)]
#[UsesClass(QuestionLimitInstruction::class)]
#[UsesClass(ValueTypeInstruction::class)]
#[UsesClass(ClassInstruction::class)]
#[UsesClass(FinalValidationInstruction::class)]
#[UsesClass(ParameterImplementationInstruction::class)]
#[UsesClass(BlueprintInstruction::class)]
#[UsesClass(ScenarioBlueprintInstruction::class)]
#[UsesClass(ParameterBlueprintInstruction::class)]
#[UsesClass(Validators::class)]
#[UsesClass(ClassValidator::class)]
#[UsesClass(ClassDoesNotExistException::class)]
#[UsesClass(ResponseBuilder::class)]
#[UsesClass(CodeValidator::class)]
#[UsesClass(SyntaxValidator::class)]
#[UsesClass(InheritanceValidator::class)]
#[UsesClass(AttributeValidator::class)]
#[UsesClass(AttributeFinder::class)]
#[UsesClass(AttributeValidatorException::class)]
#[Group('runtime')]
#[Small]
final class ContextTest extends TestCase
{
    use SourceCodeRegistryMock;

    private string $rootDir;

    protected function setUp(): void
    {
        $this->resetSourceCodeRegistry();
        ScenarioRegistry::getInstance()->clear();
        (new ReflectionClass(ParameterTypeRegistry::class))->getProperty('instance')->setValue(null, null);
        $core = new ReflectionClass(Application::class);
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, null);
        $this->rootDir = sys_get_temp_dir() . '/scenario-ai-context-' . uniqid();
        mkdir($this->rootDir . '/vendor/stateforge/scenario-core/docs', 0777, true);
        mkdir($this->rootDir . '/vendor/stateforge/scenario-core/blueprint', 0777, true);
        mkdir($this->rootDir . '/project-docs');
        file_put_contents($this->rootDir . '/vendor/stateforge/scenario-core/docs/framework.md', 'Framework documentation marker');
        file_put_contents($this->rootDir . '/vendor/stateforge/scenario-core/blueprint/scenario.blueprint', 'Scenario blueprint marker');
        file_put_contents($this->rootDir . '/vendor/stateforge/scenario-core/blueprint/parameter.blueprint', 'Parameter blueprint marker');
        file_put_contents($this->rootDir . '/project-docs/project.md', 'Project documentation marker');
        $core->getProperty('rootDir')->setValue(null, $this->rootDir);
        $core->getProperty('configuration')->setValue(null, null);
    }

    protected function tearDown(): void
    {
        $this->resetSourceCodeRegistry();
        ScenarioRegistry::getInstance()->clear();
        (new ReflectionClass(ParameterTypeRegistry::class))->getProperty('instance')->setValue(null, null);
        $core = new ReflectionClass(Application::class);
        $core->getProperty('rootDir')->setValue(null, null);
        $core->getProperty('configuration')->setValue(null, null);
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, null);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
        }
        rmdir($this->rootDir);
    }

    public function testInstructionRequiresInitializedCoreConfiguration(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Scenario configuration is not initialized.');

        (new Context())->instruction(new AiRequest(null, 'Generate a scenario', 2));
    }

    #[DataProvider('projectDocumentationProvider')]
    public function testInstructionIncludesConfiguredDocumentationAndBlueprints(?string $projectDoc, bool $allowed, AdapterMode $mode = AdapterMode::Cli, ?int $counter = 2): void
    {
        $configuration = self::createStub(CoreConfiguration::class);
        $configuration->method('getSuites')->willReturn([
            'main' => new SuiteValue('main', 'Main scenario suite', 'scenario/main'),
        ]);
        $configuration->method('getParameterDirectory')->willReturn('custom/parameters');
        (new ReflectionClass(Application::class))->getProperty('configuration')->setValue(null, $configuration);
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, new Configuration(
            $projectDoc,
            $allowed,
            7,
            new AdapterConfiguration(AdapterMode::All, 'test', null, null, []),
        ));

        ScenarioRegistry::getInstance()->register(new ScenarioDefinition('main', ListedScenario::class, new AsScenario('listed', 'Project scenario marker'), []));
        $instruction = (new Context())->instruction($counter === null ? null : new AiRequest(null, 'Generate a scenario', $counter))->instruct($mode);

        $registry = new SourceCodeRegistry();
        foreach ([
            Scenario::class,
            ScenarioInterface::class,
            ParameterType::class,
            ParameterTypeDefinition::class,
            AsScenario::class,
            AsParameterType::class,
            ApplyScenario::class,
            Parameter::class,
            ParameterTypeCondition::class,
            RefreshDatabase::class,
            BooleanType::class,
            FloatType::class,
            IntegerType::class,
            StringType::class,
        ] as $class) {
            self::assertTrue($registry->hasClass($class), 'Instruction source must be registered: ' . $class);
        }
        self::assertFalse($registry->hasClass(ListedScenario::class), 'Scenario metadata does not provide its source code.');

        self::assertStringContainsString('enum ParameterType: string', $instruction);
        self::assertStringContainsString('Framework documentation marker', $instruction);
        self::assertStringContainsString('Scenario blueprint marker', $instruction);
        if ($mode !== AdapterMode::Skill) {
            self::assertStringContainsString('name: main, description: Main scenario suite, directory: scenario/main, namespace: Scenario\\Main', $instruction);
        } else {
            self::assertStringNotContainsString('Available Scenario Suites:', $instruction);
        }
        self::assertStringContainsString('Parameter blueprint marker', $instruction);
        self::assertMatchesRegularExpression('/Scenario blueprint marker[\s\S]*Parameter blueprint marker[\s\S]*ParameterType implementation rules:[\s\S]*Final Validation/', $instruction);
        self::assertStringContainsString('The response contains exactly the required five JSON fields.', $instruction);
        self::assertStringContainsString('directory: custom/parameters, namespace: Custom\\Parameters', $instruction);
        if ($mode === AdapterMode::Cli) {
            self::assertStringContainsString('Maximum clarification questions: 7', $instruction);
            self::assertStringContainsString('Clarification questions already asked: ' . ($counter ?? 0), $instruction);
        } else {
            self::assertStringNotContainsString('Conversation rules:', $instruction);
        }
        if ($allowed) {
            self::assertStringContainsString('Project scenario marker', $instruction);
        } else {
            self::assertStringNotContainsString('Project scenario marker', $instruction);
        }
        if ($projectDoc === null) {
            self::assertStringNotContainsString('Project documentation marker', $instruction);
        } else {
            self::assertStringContainsString('Project documentation marker', $instruction);
        }
    }

    /** @return array<string, array{?string, bool, 2?: AdapterMode, 3?: ?int}> */
    public static function projectDocumentationProvider(): array
    {
        return ['without project docs' => [null, false], 'with project docs' => ['project-docs', false], 'allow project information' => [null, true], 'allow with docs' => ['project-docs', true], 'skill without request' => [null, false, AdapterMode::Skill, null], 'cli without request' => [null, false, AdapterMode::Cli, null], 'all without request' => [null, false, AdapterMode::All, null]];
    }

    #[DataProvider('generationProvider')]
    public function testValidatorAcceptsMatchingBaseClassAndAttribute(string $type, string $parent, string $attribute): void
    {
        $response = new AiResponse(null, '<?php #[\\' . $attribute . '] class Generated extends \\' . $parent . ' {}', GenerationType::from($type), null, [], null);

        (new Context())->validator()->validate($response);

        self::assertNotNull($response->php);
    }

    #[DataProvider('generationProvider')]
    public function testValidatorRejectsMissingRequiredAttribute(string $type, string $parent, string $attribute): void
    {
        $response = new AiResponse(null, '<?php class Generated extends \\' . $parent . ' {}', GenerationType::from($type), null, [], null);
        $this->expectException(AttributeValidatorException::class);
        $this->expectExceptionMessage('Missing required attribute "' . $attribute . '".');

        (new Context())->validator()->validate($response);
    }

    public function testValidatorChecksClassReferencesBeforeGeneratedCode(): void
    {
        $response = (new ResponseBuilder())->build('id', [
            'type' => 'scenario', 'php' => '<?php class {', 'question' => null,
            'classes' => ['Missing\\RequestedClass'], 'match' => null,
        ]);
        $this->expectException(ClassDoesNotExistException::class);
        $this->expectExceptionMessage('class "Missing\\RequestedClass" does not exist.');

        (new Context())->validator()->validate($response);
    }

    public function testValidatorChecksMatchWithoutGeneratedCode(): void
    {
        $response = (new ResponseBuilder())->build('id', [
            'type' => 'scenario', 'php' => null, 'question' => null,
            'classes' => [], 'match' => 'Missing\\MatchingClass',
        ]);
        $this->expectException(ClassDoesNotExistException::class);

        (new Context())->validator()->validate($response);
    }

    /** @return array<string, array{string, string, string}> */
    public static function generationProvider(): array
    {
        return [
            'scenario' => ['scenario', 'Stateforge\\Scenario\\Core\\Scenario', 'Stateforge\\Scenario\\Core\\Attribute\\AsScenario'],
            'parameter' => ['parameter', 'Stateforge\\Scenario\\Core\\ParameterTypeDefinition', 'Stateforge\\Scenario\\Core\\Attribute\\AsParameterType'],
        ];
    }
}
