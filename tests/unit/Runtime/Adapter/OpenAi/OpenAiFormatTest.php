<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Adapter\OpenAi;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiFormat;
use function array_keys;

#[CoversClass(OpenAiFormat::class)]
#[Group('runtime')]
#[Small]
final class OpenAiFormatTest extends TestCase
{
    public function testDeclaresStrictStructuredResponseSchema(): void
    {
        $result = (new OpenAiFormat())->format();
        self::assertIsArray($result['format']);
        $format = $result['format'];
        self::assertSame('json_schema', $format['type']);
        self::assertSame('scenario_response', $format['name']);
        self::assertTrue($format['strict']);
        self::assertIsArray($format['schema']);
        $schema = $format['schema'];
        self::assertSame('object', $schema['type']);
        self::assertSame(['php', 'type', 'question', 'classes'], $schema['required']);
        self::assertFalse($schema['additionalProperties']);
        self::assertIsArray($schema['properties']);
        $properties = $schema['properties'];
        self::assertSame(['php', 'type', 'question', 'classes'], array_keys($properties));
        foreach (['php', 'type', 'question'] as $field) {
            self::assertIsArray($properties[$field]);
            self::assertSame(['string', 'null'], $properties[$field]['type']);
        }
        self::assertIsArray($properties['classes']);
        self::assertSame('array', $properties['classes']['type']);
        self::assertSame(['type' => 'string'], $properties['classes']['items']);
    }
}
