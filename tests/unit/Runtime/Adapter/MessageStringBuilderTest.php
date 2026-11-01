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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Adapter\MessageStringBuilder;

#[CoversClass(MessageStringBuilder::class)]
#[Group('runtime')]
#[Small]
final class MessageStringBuilderTest extends TestCase
{
    public function testPreservesTextIncludingEmptyAndMultilineValues(): void
    {
        foreach (['', 'Generate', "Zeile 1\nGrüße"] as $content) {
            self::assertSame($content, (new MessageStringBuilder())->buildContent($content));
        }
    }
}
