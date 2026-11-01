<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Console;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use Stateforge\Scenario\Ai\Console\CliSkill;
use Stateforge\Scenario\Ai\Contract\AiAdapter;
use Stateforge\Scenario\Ai\Contract\AiContext;
use Stateforge\Scenario\Ai\Contract\AiSkill;
use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Runtime\Adapter\AdapterRegistry;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Ai\Runtime\Configuration;
use Stateforge\Scenario\Ai\Tests\Unit\Console\Command\CommandTestCase;
use Stateforge\Scenario\Core\Contract\CliOutput;

#[CoversClass(CliSkill::class)]
#[UsesClass(Ai::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(AdapterConfiguration::class)]
#[UsesClass(AdapterRegistry::class)]
#[Small]
final class CliSkillTest extends CommandTestCase
{
    #[DataProvider('resultProvider')]
    public function testReportsSkillGenerationResult(?string $path, bool $success): void
    {
        $instruction = self::createStub(Instruction::class);
        $context = $this->createMock(AiContext::class);
        $context->expects(self::once())->method('instruction')->with(null)->willReturn($instruction);
        $context->expects(self::never())->method('validator');
        $skill = $this->createMock(AiSkill::class);
        $skill->expects(self::once())->method('generate')->willReturn($path);
        $adapter = $this->createMock(AiAdapter::class);
        $adapter->expects(self::once())->method('skill')->with(self::isInstanceOf(AdapterConfiguration::class), $instruction)->willReturn($skill);
        $adapter->expects(self::never())->method('client');
        $registry = new AdapterRegistry();
        $registry->register('test', $adapter);
        (new ReflectionClass(Ai::class))->getProperty('registry')->setValue(null, $registry);
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(
            null,
            new Configuration(null, false, 3, new AdapterConfiguration(AdapterMode::Skill, 'test', null, null, [])),
        );
        $output = $this->createMock(CliOutput::class);
        $output->expects($success ? self::once() : self::never())->method('success')->with('Skill was generated successfully: ' . $path);
        $output->expects($success ? self::never() : self::once())->method('error')->with('Skill generation failed.');

        self::assertSame($success, (new CliSkill())->generate($output, $context));
    }

    /** @return iterable<string, array{?string, bool}> */
    public static function resultProvider(): iterable
    {
        yield 'success' => ['.agents/skills/stateforge-scenario/SKILL.md', true];
        yield 'failure' => [null, false];
    }
}
