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
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Ai\Runtime\AstBuilder;
use Stateforge\Scenario\Ai\Runtime\Configuration;
use Stateforge\Scenario\Ai\Runtime\Context;
use Stateforge\Scenario\Ai\Runtime\Exception\AttributeValidatorException;
use Stateforge\Scenario\Ai\Runtime\Instruction\AttributeInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\BaseInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\BlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\DocumentationFinder;
use Stateforge\Scenario\Ai\Runtime\Instruction\DocumentationInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\GeneralInstructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterBlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\QuestionLimitInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ScenarioBlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ValueTypeInstruction;
use Stateforge\Scenario\Ai\Runtime\ScenarioAst;
use Stateforge\Scenario\Ai\Runtime\SourceCodeReader;
use Stateforge\Scenario\Ai\Runtime\Validation\AttributeFinder;
use Stateforge\Scenario\Ai\Runtime\Validation\AttributeValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\CodeValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\InheritanceValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\SyntaxValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\Validators;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration as CoreConfiguration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Value\SuiteValue;
use function file_put_contents;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[CoversClass(Context::class)]
#[UsesClass(Ai::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(AdapterConfiguration::class)]
#[UsesClass(AiRequest::class)]
#[UsesClass(AiResponse::class)]
#[UsesClass(AstBuilder::class)]
#[UsesClass(ScenarioAst::class)]
#[UsesClass(SourceCodeReader::class)]
#[UsesClass(Instructions::class)]
#[UsesClass(GeneralInstructions::class)]
#[UsesClass(BaseInstruction::class)]
#[UsesClass(AttributeInstruction::class)]
#[UsesClass(DocumentationInstruction::class)]
#[UsesClass(DocumentationFinder::class)]
#[UsesClass(QuestionLimitInstruction::class)]
#[UsesClass(ValueTypeInstruction::class)]
#[UsesClass(BlueprintInstruction::class)]
#[UsesClass(ScenarioBlueprintInstruction::class)]
#[UsesClass(ParameterBlueprintInstruction::class)]
#[UsesClass(Validators::class)]
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
    private string $rootDir;
    private mixed $previousRoot;
    private mixed $previousConfiguration;
    private mixed $previousAiConfiguration;

    protected function setUp(): void
    {
        $core = new ReflectionClass(Application::class);
        $this->previousRoot = $core->getProperty('rootDir')->getValue();
        $this->previousConfiguration = $core->getProperty('configuration')->getValue();
        $this->previousAiConfiguration = (new ReflectionClass(Ai::class))->getProperty('config')->getValue();
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
        $core = new ReflectionClass(Application::class);
        $core->getProperty('rootDir')->setValue(null, $this->previousRoot);
        $core->getProperty('configuration')->setValue(null, $this->previousConfiguration);
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, $this->previousAiConfiguration);
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
    public function testInstructionIncludesConfiguredDocumentationAndBlueprints(?string $projectDoc): void
    {
        $configuration = self::createStub(CoreConfiguration::class);
        $configuration->method('getSuites')->willReturn([
            'main' => new SuiteValue('main', 'Main scenario suite', 'scenario/main'),
        ]);
        $configuration->method('getParameterDirectory')->willReturn('custom/parameters');
        (new ReflectionClass(Application::class))->getProperty('configuration')->setValue(null, $configuration);
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, new Configuration(
            $projectDoc,
            7,
            new AdapterConfiguration('test', null, null, []),
        ));

        $instruction = (new Context())->instruction(new AiRequest(null, 'Generate a scenario', 2))->instruct();

        self::assertStringContainsString('Framework documentation marker', $instruction);
        self::assertStringContainsString('Scenario blueprint marker', $instruction);
        self::assertStringContainsString('name: main, description: Main scenario suite, directory: scenario/main, namespace: Scenario\\Main', $instruction);
        self::assertStringContainsString('Parameter blueprint marker', $instruction);
        self::assertStringContainsString('directory: custom/parameters, namespace: Custom\\Parameters', $instruction);
        self::assertStringContainsString('Maximum clarification questions: 7', $instruction);
        self::assertStringContainsString('Clarification questions already asked: 2', $instruction);
        if ($projectDoc === null) {
            self::assertStringNotContainsString('Project documentation marker', $instruction);
        } else {
            self::assertStringContainsString('Project documentation marker', $instruction);
        }
    }

    /** @return array<string, array{?string}> */
    public static function projectDocumentationProvider(): array
    {
        return ['without project docs' => [null], 'with project docs' => ['project-docs']];
    }

    #[DataProvider('generationProvider')]
    public function testValidatorAcceptsMatchingBaseClassAndAttribute(string $type, string $parent, string $attribute): void
    {
        $response = new AiResponse(null, '<?php #[\\' . $attribute . '] class Generated extends \\' . $parent . ' {}', GenerationType::from($type), null, []);

        (new Context())->validator()->validate($response);

        self::assertNotNull($response->php);
    }

    #[DataProvider('generationProvider')]
    public function testValidatorRejectsMissingRequiredAttribute(string $type, string $parent, string $attribute): void
    {
        $response = new AiResponse(null, '<?php class Generated extends \\' . $parent . ' {}', GenerationType::from($type), null, []);
        $this->expectException(AttributeValidatorException::class);
        $this->expectExceptionMessage('Missing required attribute "' . $attribute . '".');

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
