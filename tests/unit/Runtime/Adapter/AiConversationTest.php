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

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stateforge\Scenario\Ai\Contract\AiAdapter;
use Stateforge\Scenario\Ai\Contract\AiClient;
use Stateforge\Scenario\Ai\Contract\AiContext;
use Stateforge\Scenario\Ai\Runtime\Adapter\AdapterRegistry;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiAudit;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiConversation;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\ConfigBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\Configuration;
use Stateforge\Scenario\Ai\Runtime\Exception\AttributeValidatorException;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;
use Stateforge\Scenario\Ai\Runtime\Validation\Validator;

#[CoversClass(AiConversation::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(AdapterConfiguration::class)]
#[UsesClass(AdapterRegistry::class)]
#[CoversClass(AiRequest::class)]
#[UsesClass(AiResponse::class)]
#[CoversClass(AiAudit::class)]
#[UsesClass(AttributeValidatorException::class)]
#[Group('runtime')]
#[Small]
final class AiConversationTest extends TestCase
{
    public function testResponseRequiresPreviousTalk(): void
    {
        $conversation = $this->conversation(self::createStub(AiClient::class));
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No audit available.');

        $conversation->response();
    }

    public function testTalkPassesPromptInstructionsConfigurationAndAuditHistory(): void
    {
        $first = new AiResponse('first-id', null, null, 'Which state?', [], );
        $second = new AiResponse('second-id', '<?php class Demo {}', GenerationType::Scenario, null, []);
        $instruction = self::createStub(Instruction::class);
        $context = $this->createMock(AiContext::class);
        $context->method('validator')->willReturn(self::createStub(Validator::class));
        $instructionCalls = 0;
        $context->expects(self::exactly(2))->method('instruction')->willReturnCallback(
            function (AiRequest $request) use ($instruction, &$instructionCalls): Instruction {
                ++$instructionCalls;
                self::assertSame($instructionCalls, $request->counter);
                return $instruction;
            },
        );
        $client = $this->createMock(AiClient::class);
        $requests = 0;
        $client->expects(self::exactly(2))->method('request')->willReturnCallback(
            /** @param list<AiAudit> $audits */
            function (AiRequest $request, array $audits) use (&$requests, $first, $second): AiResponse {
                ++$requests;
                self::assertNull($request->id);
                self::assertSame($requests, $request->counter);
                if ($requests === 1) {
                    self::assertSame('Create a scenario', $request->prompt);
                    self::assertSame([], $audits);
                    return $first;
                }
                self::assertSame('An empty database', $request->prompt);
                self::assertCount(1, $audits);
                self::assertInstanceOf(AiAudit::class, $audits[0]);
                self::assertSame($first, $audits[0]->response);
                self::assertSame('Create a scenario', $audits[0]->request->prompt);
                return $second;
            },
        );
        $adapterConfiguration = new AdapterConfiguration('test', null, null, []);
        $adapter = $this->createMock(AiAdapter::class);
        $adapter->expects(self::exactly(2))->method('client')
            ->with(self::isInstanceOf(ConfigBuilder::class), $adapterConfiguration, $instruction)
            ->willReturn($client);
        $registry = new AdapterRegistry();
        $registry->register('test', $adapter);
        $conversation = new AiConversation($context, new Configuration(null, 5, $adapterConfiguration), $registry);

        $conversation->talk('Create a scenario');
        self::assertSame($first, $conversation->response());
        self::assertSame('Which state?', $conversation->question());
        self::assertFalse($conversation->completed());
        $conversation->talk('An empty database');
        self::assertSame($second, $conversation->response());
        self::assertNull($conversation->question());
        self::assertSame([], $conversation->classes());
        self::assertTrue($conversation->completed());
    }

    #[DataProvider('completionProvider')]
    public function testCompletionDependsOnResponseAndQuestionLimit(?string $php, ?string $type, ?string $question, bool $requestClasses, int $limit, bool $completed): void
    {
        $classes = $requestClasses ? [self::class] : [];
        $response = new AiResponse('id', $php, $type === null ? null : GenerationType::from($type), $question, $classes);
        $client = self::createStub(AiClient::class);
        $client->method('request')->willReturn($response);
        $conversation = $this->conversation($client, limit: $limit);

        $conversation->talk('Generate');

        self::assertSame($completed, $conversation->completed());
        self::assertSame($question, $conversation->question());
        self::assertSame($classes, $conversation->classes());
    }

    /** @return array<string, array{?string, ?string, ?string, bool, int, bool}> */
    public static function completionProvider(): array
    {
        return [
            'code ready' => ['<?php', 'scenario', null, false, 5, true],
            'missing code' => [null, 'scenario', null, false, 5, false],
            'missing generation type' => ['<?php', null, null, false, 5, false],
            'question pending' => ['<?php', 'scenario', 'Which state?', false, 5, false],
            'classes requested' => ['<?php', 'scenario', null, true, 5, false],
            'question limit reached' => [null, null, 'Which state?', true, 1, true],
        ];
    }

    public function testValidationFailureRetriesWithResponseIdAndAuditHistory(): void
    {
        $invalid = new AiResponse('invalid-id', '<?php', GenerationType::Scenario, null, []);
        $corrected = new AiResponse('corrected-id', '<?php class Demo {}', GenerationType::Scenario, null, []);
        $validator = $this->createMock(Validator::class);
        $validations = 0;
        $validator->expects(self::exactly(2))->method('validate')->willReturnCallback(
            function (AiResponse $response) use (&$validations, $invalid, $corrected): void {
                if (++$validations === 1) {
                    self::assertSame($invalid, $response);
                    throw new AttributeValidatorException('RequiredAttribute');
                }
                self::assertSame($corrected, $response);
            },
        );
        $client = $this->createMock(AiClient::class);
        $requests = 0;
        $client->expects(self::exactly(2))->method('request')->willReturnCallback(
            /** @param list<AiAudit> $audits */
            function (AiRequest $request, array $audits) use (&$requests, $invalid, $corrected): AiResponse {
                self::assertSame(1, $request->counter);
                if (++$requests === 1) {
                    self::assertSame([], $audits);
                    return $invalid;
                }
                self::assertSame('invalid-id', $request->id);
                self::assertSame('Missing required attribute "RequiredAttribute".', $request->prompt);
                self::assertCount(1, $audits);
                self::assertInstanceOf(AiAudit::class, $audits[0]);
                self::assertSame($invalid, $audits[0]->response);
                return $corrected;
            },
        );
        $conversation = $this->conversation($client, $validator);

        $conversation->talk('Generate');

        self::assertSame($corrected, $conversation->response());
        self::assertTrue($conversation->completed());
    }

    public function testSecondValidationFailurePropagatesWithoutFurtherRetry(): void
    {
        $response = new AiResponse('id', '<?php', GenerationType::Scenario, null, []);
        $client = $this->createMock(AiClient::class);
        $client->expects(self::exactly(2))->method('request')->willReturn($response);
        $validator = $this->createMock(Validator::class);
        $validator->expects(self::exactly(2))->method('validate')
            ->willThrowException(new AttributeValidatorException('RequiredAttribute'));
        $conversation = $this->conversation($client, $validator);
        $this->expectException(AttributeValidatorException::class);

        $conversation->talk('Generate');
    }

    public function testClientFailurePropagates(): void
    {
        $client = $this->createMock(AiClient::class);
        $client->expects(self::once())->method('request')->willThrowException(new RuntimeException('Request failed'));
        $validator = $this->createMock(Validator::class);
        $validator->expects(self::never())->method('validate');
        $conversation = $this->conversation($client, $validator);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request failed');

        $conversation->talk('Generate');
    }

    private function conversation(AiClient $client, ?Validator $validator = null, int $limit = 5): AiConversation
    {
        $context = self::createStub(AiContext::class);
        $context->method('instruction')->willReturn(self::createStub(Instruction::class));
        $context->method('validator')->willReturn($validator ?? self::createStub(Validator::class));
        $adapter = self::createStub(AiAdapter::class);
        $adapter->method('client')->willReturn($client);
        $registry = new AdapterRegistry();
        $registry->register('test', $adapter);

        return new AiConversation($context, new Configuration(
            null,
            $limit,
            new AdapterConfiguration('test', null, null, []),
        ), $registry);
    }
}
