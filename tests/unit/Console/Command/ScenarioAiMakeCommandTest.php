<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Console\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use RuntimeException;
use Stateforge\Scenario\Ai\Console\Command\ScenarioAiMakeCommand;
use Stateforge\Scenario\Ai\Contract\AiAdapter;
use Stateforge\Scenario\Ai\Contract\AiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\AdapterRegistry;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiAudit;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiConversation;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Ai\Runtime\AstBuilder;
use Stateforge\Scenario\Ai\Runtime\Configuration;
use Stateforge\Scenario\Ai\Runtime\Context;
use Stateforge\Scenario\Ai\Runtime\Exception\AttributeValidatorException;
use Stateforge\Scenario\Ai\Runtime\Instruction\BlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\GeneralInstructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterBlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ScenarioBlueprintInstruction;
use Stateforge\Scenario\Ai\Runtime\Maker\CodeMaker;
use Stateforge\Scenario\Ai\Runtime\Maker\CodeWriter;
use Stateforge\Scenario\Ai\Runtime\Maker\DirectoryResolver;
use Stateforge\Scenario\Ai\Runtime\Prompt\ClassNameParser;
use Stateforge\Scenario\Ai\Runtime\ScenarioAst;
use Stateforge\Scenario\Ai\Runtime\SourceCodeReader;
use Stateforge\Scenario\Ai\Runtime\Validation\AttributeFinder;
use Stateforge\Scenario\Ai\Runtime\Validation\AttributeValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\CodeValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\InheritanceValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\SyntaxValidator;
use Stateforge\Scenario\Ai\Runtime\Validation\Validators;
use Stateforge\Scenario\Ai\Tests\Files\SharedProjectClass;
use Stateforge\Scenario\Core\Console\Command\Command;
use Stateforge\Scenario\Core\Contract\CliInput;
use Stateforge\Scenario\Core\Contract\CliOutput;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration as CoreConfiguration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Value\SuiteValue;
use function count;
use function file_get_contents;
use function file_put_contents;
use function mkdir;
use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

#[CoversClass(ScenarioAiMakeCommand::class)]
#[UsesClass(Ai::class)]
#[UsesClass(Context::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(AdapterConfiguration::class)]
#[UsesClass(AdapterRegistry::class)]
#[UsesClass(AiConversation::class)]
#[UsesClass(AiAudit::class)]
#[UsesClass(AiRequest::class)]
#[UsesClass(AiResponse::class)]
#[UsesClass(AstBuilder::class)]
#[UsesClass(ScenarioAst::class)]
#[UsesClass(Instructions::class)]
#[UsesClass(GeneralInstructions::class)]
#[UsesClass(BlueprintInstruction::class)]
#[UsesClass(ScenarioBlueprintInstruction::class)]
#[UsesClass(ParameterBlueprintInstruction::class)]
#[UsesClass(CodeMaker::class)]
#[UsesClass(CodeWriter::class)]
#[UsesClass(DirectoryResolver::class)]
#[UsesClass(ClassNameParser::class)]
#[UsesClass(SourceCodeReader::class)]
#[UsesClass(Validators::class)]
#[UsesClass(CodeValidator::class)]
#[UsesClass(SyntaxValidator::class)]
#[UsesClass(InheritanceValidator::class)]
#[UsesClass(AttributeValidator::class)]
#[UsesClass(AttributeFinder::class)]
#[UsesClass(AttributeValidatorException::class)]
#[Group('runtime')]
#[Small]
final class ScenarioAiMakeCommandTest extends CommandTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $configuration = self::createStub(CoreConfiguration::class);
        $configuration->method('getSuites')->willReturn(['main' => new SuiteValue('main', 'Main suite', 'scenario/main')]);
        $configuration->method('getParameterDirectory')->willReturn('scenario/parameter');
        (new ReflectionClass(Application::class))->getProperty('configuration')->setValue(null, $configuration);
    }

    public function testDescription(): void
    {
        self::assertSame('Make a scenario or parameter type with AI.', (new ScenarioAiMakeCommand())->description());
    }

    #[DataProvider('generationProvider')]
    public function testGeneratesValidatedCodeWithMockClient(string $type, bool $existingDraft): void
    {
        $response = $this->generatedResponse($type);
        self::assertNotNull($response->php);
        $directory = $type === 'scenario' ? 'scenario/main' : 'scenario/parameter';
        if ($existingDraft) {
            mkdir($directory, 0777, true);
            file_put_contents($directory . DIRECTORY_SEPARATOR . 'Generated.php.ai', 'Existing draft');
        }
        $client = $this->createMock(AiClient::class);
        $client->expects(self::once())->method('request')
            ->with(self::callback(static fn (AiRequest $request): bool => $request->prompt === 'Generate code' . PHP_EOL), [])
            ->willReturn($response);
        $this->configureAi($client);
        $output = $this->createMock(CliOutput::class);
        $output->expects(self::once())->method('note')->with(self::stringContains('Welcome to Scenario AI!'));
        $output->expects(self::once())->method('warn')->with(self::stringContains('configure your project doc'));
        $output->expects(self::once())->method('ask')->with('Please describe the scenario or parameter type.')->willReturn('Generate code');
        $output->expects(self::never())->method('confirm');
        $output->expects(self::never())->method('error');
        $path = $directory . DIRECTORY_SEPARATOR . 'Generated.php' . ($existingDraft ? '.1.ai' : '.ai');
        $output->expects(self::once())->method('success')->with(self::logicalAnd(
            self::stringContains('Code "' . $path . '" generated successfully.'),
            self::stringContains('rename the file to "' . $directory . DIRECTORY_SEPARATOR . 'Generated.php"'),
        ));

        self::assertSame(Command::Success, (new ScenarioAiMakeCommand())->run($this->input(), $output));
        self::assertSame($response->php, file_get_contents($path));
        self::assertFileDoesNotExist($directory . DIRECTORY_SEPARATOR . 'Generated.php');
        if ($existingDraft) {
            self::assertSame('Existing draft', file_get_contents($directory . DIRECTORY_SEPARATOR . 'Generated.php.ai'));
        }
    }

    /** @return array<string, array{string, bool}> */
    public static function generationProvider(): array
    {
        return ['scenario' => ['scenario', false], 'parameter' => ['parameter', false], 'preserve draft' => ['scenario', true]];
    }

    public function testAsksFollowUpQuestionAndSendsAnswerWithHistory(): void
    {
        $client = $this->createMock(AiClient::class);
        $response = $this->generatedResponse();
        $calls = 0;
        $client->expects(self::exactly(2))->method('request')->willReturnCallback(
            /** @param list<AiAudit> $audits */
            function (AiRequest $request, array $audits) use (&$calls, $response): AiResponse {
                if (++$calls === 1) {
                    self::assertSame('Generate code' . PHP_EOL, $request->prompt);
                    return new AiResponse('question-id', null, GenerationType::Scenario, 'Which state?', []);
                }
                self::assertSame(2, $request->counter);
                self::assertSame('Empty database' . PHP_EOL . PHP_EOL, $request->prompt);
                self::assertCount(1, $audits);
                return $response;
            },
        );
        $this->configureAi($client, 'project-docs');
        $output = $this->createMock(CliOutput::class);
        $output->expects(self::never())->method('warn');
        $output->expects(self::never())->method('error');
        $output->expects(self::once())->method('success');
        $questions = [];
        $output->expects(self::exactly(2))->method('ask')->willReturnCallback(
            function (string $question) use (&$questions): string {
                $questions[] = $question;
                return count($questions) === 1 ? 'Generate code' : 'Empty database';
            },
        );

        self::assertSame(Command::Success, (new ScenarioAiMakeCommand())->run($this->input(), $output));
        self::assertSame(['Please describe the scenario or parameter type.', 'AI: Which state?'], $questions);
    }

    #[DataProvider('consentProvider')]
    public function testPromptSharesExistingClassOnlyWithConsent(bool $consent): void
    {
        $class = SharedProjectClass::class;
        $client = $this->createMock(AiClient::class);
        $client->expects(self::once())->method('request')->with(
            self::callback(static function (AiRequest $request) use ($consent, $class): bool {
                self::assertStringContainsString('Use ' . $class, $request->prompt);
                if ($consent) {
                    self::assertStringContainsString('Shared project class source marker', $request->prompt);
                } else {
                    self::assertStringNotContainsString('Shared project class source marker', $request->prompt);
                }
                return true;
            }),
            [],
        )->willReturn($this->generatedResponse());
        $this->configureAi($client);
        $output = $this->createMock(CliOutput::class);
        $output->method('ask')->willReturn('Use ' . $class . ' and MissingProject\\UnknownClass');
        $output->expects(self::once())->method('confirm')
            ->with('Can the source code for class "' . $class . '" be sent to AI?', false)->willReturn($consent);
        $output->expects(self::never())->method('error');

        self::assertSame(Command::Success, (new ScenarioAiMakeCommand())->run($this->input(), $output));
    }

    #[DataProvider('consentProvider')]
    public function testRespondsToRequestedClassDefinitionsWithConsent(bool $consent): void
    {
        $client = $this->createMock(AiClient::class);
        $response = $this->generatedResponse();
        $calls = 0;
        $client->expects(self::exactly(2))->method('request')->willReturnCallback(
            function (AiRequest $request) use (&$calls, $response, $consent): AiResponse {
                if (++$calls === 1) {
                    return new AiResponse('class-request', null, GenerationType::Scenario, null, [SharedProjectClass::class]);
                }
                if ($consent) {
                    self::assertStringContainsString('Shared project class source marker', $request->prompt);
                } else {
                    self::assertStringNotContainsString('Shared project class source marker', $request->prompt);
                }
                return $response;
            },
        );
        $this->configureAi($client);
        $output = $this->createMock(CliOutput::class);
        $output->expects(self::once())->method('ask')->willReturn('Generate code');
        $output->expects(self::once())->method('confirm')
            ->with('Can the source code for class "' . SharedProjectClass::class . '" be sent to AI?', false)->willReturn($consent);
        $output->expects(self::never())->method('error');

        self::assertSame(Command::Success, (new ScenarioAiMakeCommand())->run($this->input(), $output));
    }

    /** @return array<string, array{bool}> */
    public static function consentProvider(): array
    {
        return ['allowed' => [true], 'declined' => [false]];
    }

    #[DataProvider('missingCodeProvider')]
    public function testReportsNoCodeWhenQuestionLimitReached(?string $php, ?string $type): void
    {
        $client = $this->createMock(AiClient::class);
        $client->expects(self::once())->method('request')->willReturn(new AiResponse(
            'incomplete',
            $php,
            $type === null ? null : GenerationType::from($type),
            'Need details',
            [],
        ));
        $this->configureAi($client, limit: 1);
        $output = $this->createMock(CliOutput::class);
        $output->expects(self::once())->method('ask')->willReturn(null);
        $output->expects(self::once())->method('error')->with('No code was generated by AI, please try again.');
        $output->expects(self::never())->method('success');

        self::assertSame(Command::Error, (new ScenarioAiMakeCommand())->run($this->input(), $output));
        self::assertDirectoryDoesNotExist($this->testDirectory . '/scenario');
    }

    /** @return array<string, array{?string, ?string}> */
    public static function missingCodeProvider(): array
    {
        return ['no code' => [null, 'scenario'], 'no type' => ['<?php class Demo {}', null]];
    }

    public function testClientFailureIsReportedWithoutWritingCode(): void
    {
        $client = $this->createMock(AiClient::class);
        $client->expects(self::once())->method('request')->willThrowException(new RuntimeException('Local test failure'));
        $this->configureAi($client);
        $output = $this->createMock(CliOutput::class);
        $output->method('ask')->willReturn('Generate code');
        $output->expects(self::once())->method('error')->with(self::stringContains('Local test failure'));
        $output->expects(self::never())->method('success');

        self::assertSame(Command::Error, (new ScenarioAiMakeCommand())->run($this->input(), $output));
        self::assertDirectoryDoesNotExist($this->testDirectory . '/scenario');
    }

    public function testDecliningCommandConfirmationNeverCallsClient(): void
    {
        $client = $this->createMock(AiClient::class);
        $client->expects(self::never())->method('request');
        $this->configureAi($client);
        $input = self::createStub(CliInput::class);
        $input->method('option')->willReturn(false);
        $output = $this->createMock(CliOutput::class);
        $output->expects(self::once())->method('confirm')->with('Do you want to continue?', false)->willReturn(false);
        $output->expects(self::never())->method('ask');
        $output->expects(self::never())->method('note');

        self::assertSame(Command::Error, (new ScenarioAiMakeCommand())->run($input, $output));
    }

    private function configureAi(AiClient $client, ?string $projectDoc = null, int $limit = 3): void
    {
        $adapter = self::createStub(AiAdapter::class);
        $adapter->method('client')->willReturn($client);
        $registry = new AdapterRegistry();
        $registry->register('local-test', $adapter);
        $reflection = new ReflectionClass(Ai::class);
        $reflection->getProperty('registry')->setValue(null, $registry);
        $reflection->getProperty('config')->setValue(null, new Configuration(
            $projectDoc,
            $limit,
            new AdapterConfiguration('local-test', null, null, []),
        ));
    }

    private function generatedResponse(string $type = 'scenario'): AiResponse
    {
        $namespace = $type === 'scenario' ? 'Stateforge\\Suite\\Scenario\\Main' : 'Stateforge\\Suite\\Scenario\\Parameter';
        $attribute = $type === 'scenario' ? 'AsScenario' : 'AsParameterType';
        $parent = $type === 'scenario' ? 'Scenario' : 'ParameterTypeDefinition';
        $code = '<?php namespace ' . $namespace . '; #[\\Stateforge\\Scenario\\Core\\Attribute\\' . $attribute . '] final class Generated extends \\Stateforge\\Scenario\\Core\\' . $parent . ' {}';

        return new AiResponse('generated-id', $code, GenerationType::from($type), null, []);
    }
}
