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
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Instruction\QuestionLimitInstruction;

#[CoversClass(QuestionLimitInstruction::class)]
#[Group('runtime')]
#[Small]
final class QuestionLimitInstructionTest extends TestCase
{
    #[DataProvider('questionProvider')]
    public function testIncludesConfiguredLimitAndCurrentCount(int $limit, int $asked): void
    {
        $text = (new QuestionLimitInstruction($limit, $asked))->instruct();

        self::assertStringContainsString('Maximum clarification questions: ' . $limit, $text);
        self::assertStringContainsString('Clarification questions already asked: ' . $asked, $text);
        self::assertStringContainsString('Never exceed the configured clarification question limit.', $text);
        self::assertStringContainsString('Requests for class definitions are not clarification questions.', $text);
    }

    /** @return array<string, array{int, int}> */
    public static function questionProvider(): array
    {
        return ['initial' => [5, 0], 'in progress' => [7, 2], 'limit reached' => [3, 3], 'no questions' => [0, 0]];
    }
}
