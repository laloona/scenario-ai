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

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiAudit;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiRequest;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\Gemini\MessagePartsBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\Adapter\MessageBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\MessageStringBuilder;
use function json_decode;
use const JSON_THROW_ON_ERROR;

#[CoversClass(MessageBuilder::class)]
#[UsesClass(MessageStringBuilder::class)]
#[UsesClass(MessagePartsBuilder::class)]
#[UsesClass(AiAudit::class)]
#[UsesClass(AiRequest::class)]
#[UsesClass(AiResponse::class)]
#[Group('runtime')]
#[Small]
final class MessageBuilderTest extends TestCase
{
    public function testWithoutHistoryReturnsOnlyTheCurrentUserPrompt(): void
    {
        self::assertSame(
            [['role' => 'user', 'content' => 'Generate a scenario']],
            (new MessageBuilder(new MessageStringBuilder()))->build(new AiRequest(null, 'Generate a scenario', 1), []),
        );
    }

    public function testPreservesAuditOrderAndSerializesResponseFieldsBeforeCurrentPrompt(): void
    {
        $audits = [
            new AiAudit(
                new AiRequest(null, 'Create a scenario', 1),
                new AiResponse('first', null, null, 'Which class?', [self::class], null),
            ),
            new AiAudit(
                new AiRequest('first', 'Generate a parameter instead', 2),
                new AiResponse('second', '<?php class Demo {}', GenerationType::Parameter, null, [], null),
            ),
        ];

        $messages = (new MessageBuilder(new MessageStringBuilder()))->build(new AiRequest('second', 'Change the conversion', 3), $audits);

        self::assertCount(5, $messages);
        self::assertSame(['role' => 'user', 'content' => 'Create a scenario'], $messages[0]);
        self::assertSame('assistant', $messages[1]['role']);
        self::assertIsString($messages[1]['content']);
        self::assertSame([
            'php' => null, 'type' => null, 'question' => 'Which class?', 'classes' => [self::class], 'match' => null,
        ], json_decode($messages[1]['content'], true, 512, JSON_THROW_ON_ERROR));
        self::assertSame(['role' => 'user', 'content' => 'Generate a parameter instead'], $messages[2]);
        self::assertSame('assistant', $messages[3]['role']);
        self::assertIsString($messages[3]['content']);
        self::assertSame([
            'php' => '<?php class Demo {}', 'type' => 'parameter', 'question' => null, 'classes' => [], 'match' => null,
        ], json_decode($messages[3]['content'], true, 512, JSON_THROW_ON_ERROR));
        self::assertSame(['role' => 'user', 'content' => 'Change the conversion'], $messages[4]);
    }

    public function testPreservesAMatchingImplementationInHistory(): void
    {
        $audit = new AiAudit(
            new AiRequest(null, 'Find an existing scenario', 1),
            new AiResponse('matched', null, GenerationType::Scenario, null, [], self::class),
        );

        $messages = (new MessageBuilder(new MessageStringBuilder()))->build(new AiRequest('matched', 'Create a new one', 2), [$audit]);

        self::assertIsString($messages[1]['content']);
        self::assertSame([
            'php' => null, 'type' => 'scenario', 'question' => null, 'classes' => [], 'match' => self::class,
        ], json_decode($messages[1]['content'], true, 512, JSON_THROW_ON_ERROR));
    }

    public function testRejectsResponseContentThatCannotBeEncodedAsJson(): void
    {
        $audit = new AiAudit(
            new AiRequest(null, 'Generate', 1),
            new AiResponse('invalid', null, null, "\xB1\x31", [], null),
        );
        $this->expectException(JsonException::class);

        (new MessageBuilder(new MessageStringBuilder()))->build(new AiRequest('invalid', 'Continue', 2), [$audit]);
    }

    public function testSupportsCustomKeysRolesAndPartsContent(): void
    {
        $builder = new MessageBuilder(new MessagePartsBuilder(), 'speaker', 'segments', 'human', 'bot');
        $audit = new AiAudit(new AiRequest(null, 'First', 1), new AiResponse('id', null, null, null, [], null));

        self::assertSame([
            ['speaker' => 'human', 'segments' => [['text' => 'First']]],
            ['speaker' => 'bot', 'segments' => [['text' => '{"php":null,"type":null,"question":null,"classes":[],"match":null}']]],
            ['speaker' => 'human', 'segments' => [['text' => 'Next']]],
        ], $builder->build(new AiRequest('id', 'Next', 2), [$audit]));
    }

}
