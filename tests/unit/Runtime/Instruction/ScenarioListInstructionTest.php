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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\Instruction\ScenarioListInstruction;
use Stateforge\Scenario\Ai\Tests\Files\ListedScenario;
use Stateforge\Scenario\Ai\Tests\Files\Project\ProjectParameterType;
use Stateforge\Scenario\Core\Attribute\AsParameterType;
use Stateforge\Scenario\Core\Attribute\AsScenario;
use Stateforge\Scenario\Core\Attribute\Parameter;
use Stateforge\Scenario\Core\ParameterType;
use Stateforge\Scenario\Core\Runtime\Metadata\Parameter\ParameterTypeRegistry;
use Stateforge\Scenario\Core\Runtime\ScenarioDefinition;
use Stateforge\Scenario\Core\Runtime\ScenarioRegistry;
use function substr_count;
use const PHP_EOL;

#[CoversClass(ScenarioListInstruction::class)]
#[Group('runtime')]
#[Small]
final class ScenarioListInstructionTest extends TestCase
{
    protected function setUp(): void
    {
        ScenarioRegistry::getInstance()->clear();
        (new ReflectionClass(ParameterTypeRegistry::class))->getProperty('instance')->setValue(null, null);
    }

    protected function tearDown(): void
    {
        ScenarioRegistry::getInstance()->clear();
        (new ReflectionClass(ParameterTypeRegistry::class))->getProperty('instance')->setValue(null, null);
    }

    public function testEmptyRegistryReturnsEmptyString(): void
    {
        self::assertSame('', (new ScenarioListInstruction())->instruct());
    }

    public function testListsScenarioMetadataOnceDespiteNameAlias(): void
    {
        ScenarioRegistry::getInstance()->register(new ScenarioDefinition(
            'main',
            ListedScenario::class,
            new AsScenario('listed', 'Listed scenario description'),
            [],
        ));

        $text = (new ScenarioListInstruction())->instruct();

        self::assertStringStartsWith('Registered Scenarios' . PHP_EOL . PHP_EOL, $text);
        self::assertStringContainsString(ListedScenario::class . ': name: listed, description: Listed scenario description, suites: main, parameters: ', $text);
        self::assertSame(1, substr_count($text, ListedScenario::class . ':'));
    }

    public function testListsAllParametersAndFormatsTheirDefaults(): void
    {
        ParameterTypeRegistry::getInstance()->register(ProjectParameterType::class, new AsParameterType('Project type'));
        ScenarioRegistry::getInstance()->register(new ScenarioDefinition(
            'main',
            ListedScenario::class,
            new AsScenario(),
            [
                new Parameter('enabled', ParameterType::Boolean, 'Enable feature', true, false, true),
                new Parameter('labels', ParameterType::String, 'Labels', false, true, ['first', 'second']),
                new Parameter('custom', ProjectParameterType::class, 'Custom value'),
            ],
        ));

        $text = (new ScenarioListInstruction())->instruct();

        self::assertStringContainsString('name: enabled, description: Enable feature, type: Boolean, required: yes, repeatable: no, default: true', $text);
        self::assertStringContainsString('name: labels, description: Labels, type: String, required: no, repeatable: yes, default: ["first","second"]', $text);
        self::assertStringContainsString('name: custom, description: Custom value, type: ProjectParameterType, required: no, repeatable: no, default: null', $text);
    }
}
