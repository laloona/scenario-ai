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
use Stateforge\Scenario\Ai\Runtime\Instruction\AttributeInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\BaseInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\DocumentationFinder;
use Stateforge\Scenario\Ai\Runtime\Instruction\DocumentationInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\GeneralInstructions;
use Stateforge\Scenario\Ai\Runtime\Instruction\QuestionLimitInstruction;
use Stateforge\Scenario\Ai\Runtime\Instruction\ValueTypeInstruction;
use Stateforge\Scenario\Ai\Runtime\SourceCodeReader;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use function file_put_contents;
use function mkdir;

#[CoversClass(GeneralInstructions::class)]
#[UsesClass(BaseInstruction::class)]
#[UsesClass(AttributeInstruction::class)]
#[UsesClass(DocumentationInstruction::class)]
#[UsesClass(DocumentationFinder::class)]
#[UsesClass(QuestionLimitInstruction::class)]
#[UsesClass(ValueTypeInstruction::class)]
#[UsesClass(SourceCodeReader::class)]
#[Group('runtime')]
#[Small]
final class GeneralInstructionsTest extends FilesystemTestCase
{
    #[DataProvider('projectDocProvider')]
    public function testCombinesRulesDocumentationAndQuestionCounts(bool $withProjectDoc): void
    {
        mkdir($this->testDirectory . '/framework');
        mkdir($this->testDirectory . '/project');
        file_put_contents($this->testDirectory . '/framework/framework.md', 'FRAMEWORK_MARKER');
        file_put_contents($this->testDirectory . '/project/project.md', 'PROJECT_MARKER');
        $text = (new GeneralInstructions(
            $this->testDirectory . '/framework',
            7,
            3,
            $withProjectDoc ? $this->testDirectory . '/project' : null,
        ))->instruct();

        self::assertStringContainsString('Generate exactly one PHP class.', $text);
        self::assertStringContainsString('Available attributes:', $text);
        self::assertStringContainsString('FRAMEWORK_MARKER', $text);
        self::assertStringContainsString('Maximum clarification questions: 7', $text);
        self::assertStringContainsString('Clarification questions already asked: 3', $text);
        self::assertStringContainsString('Some class definitions:', $text);
        if ($withProjectDoc) {
            self::assertStringContainsString('PROJECT_MARKER', $text);
        } else {
            self::assertStringNotContainsString('PROJECT_MARKER', $text);
        }
    }

    /** @return array<string, array{bool}> */
    public static function projectDocProvider(): array
    {
        return ['with project docs' => [true], 'without project docs' => [false]];
    }
}
