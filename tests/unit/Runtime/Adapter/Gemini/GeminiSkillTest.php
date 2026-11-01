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
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Runtime\Adapter\FileBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\Gemini\GeminiSkill;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use Stateforge\Scenario\Core\Runtime\Application;
use const DIRECTORY_SEPARATOR;

#[CoversClass(GeminiSkill::class)]
#[UsesClass(FileBuilder::class)]
#[Small]
final class GeminiSkillTest extends FilesystemTestCase
{
    protected function tearDown(): void
    {
        (new ReflectionClass(Application::class))->getProperty('rootDir')->setValue(null, null);
        parent::tearDown();
    }

    public function testWritesSkillInProviderDirectory(): void
    {
        (new ReflectionClass(Application::class))->getProperty('rootDir')->setValue(null, $this->testDirectory);
        $instruction = $this->createMock(Instruction::class);
        $instruction->expects(self::once())->method('instruct')->with(AdapterMode::Skill)->willReturn('Skill content');
        $file = $this->testDirectory . DIRECTORY_SEPARATOR . '.agents' . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . 'stateforge-scenario' . DIRECTORY_SEPARATOR . 'SKILL.md';

        self::assertSame($file, (new GeminiSkill(new FileBuilder($instruction)))->generate());
        self::assertStringEqualsFile($file, 'Skill content');
    }
}
