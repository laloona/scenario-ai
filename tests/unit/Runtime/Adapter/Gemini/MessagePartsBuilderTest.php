<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Adapter\Gemini;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Adapter\Gemini\MessagePartsBuilder;

#[CoversClass(MessagePartsBuilder::class)]
#[Group('runtime')]
#[Small]
final class MessagePartsBuilderTest extends TestCase
{
    public function testWrapsTextInOnePartWithoutChangingIt(): void
    {
        foreach (['', 'Generate', "Zeile 1\nGrüße"] as $content) {
            self::assertSame([['text' => $content]], (new MessagePartsBuilder())->buildContent($content));
        }
    }
}
