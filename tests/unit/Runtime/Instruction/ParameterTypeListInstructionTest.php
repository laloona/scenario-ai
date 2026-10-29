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
use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\Instruction\ParameterTypeListInstruction;
use Stateforge\Scenario\Ai\Tests\Files\PackageParameterType;
use Stateforge\Scenario\Ai\Tests\Files\Project\ProjectParameterType;
use Stateforge\Scenario\Core\Attribute\AsParameterType;
use Stateforge\Scenario\Core\ParameterType;
use Stateforge\Scenario\Core\Runtime\Metadata\Parameter\ParameterTypeRegistry;
use function dirname;
use const PHP_EOL;

#[CoversClass(ParameterTypeListInstruction::class)]
#[Group('runtime')]
#[Small]
final class ParameterTypeListInstructionTest extends TestCase
{
    protected function setUp(): void
    {
        (new ReflectionClass(ParameterTypeRegistry::class))->getProperty('instance')->setValue(null, null);
    }

    protected function tearDown(): void
    {
        (new ReflectionClass(ParameterTypeRegistry::class))->getProperty('instance')->setValue(null, null);
    }

    public function testAlwaysListsBuiltInTypes(): void
    {
        $text = (new ParameterTypeListInstruction(null, false))->instruct();

        self::assertStringStartsWith('Existing Parameter Types', $text);
        self::assertStringContainsString('Built-in parameter types', $text);
        foreach (ParameterType::cases() as $type) {
            self::assertStringContainsString(ParameterType::class . '::' . $type->name . ': ' . $type->value, $text);
        }
        self::assertStringNotContainsString('Package parameter types', $text);
        self::assertStringNotContainsString('Project parameter types', $text);
    }

    #[DataProvider('projectPermissionProvider')]
    public function testSeparatesPackageAndProjectTypesAndHonorsPermission(bool $allowed): void
    {
        $this->registerTypes();
        $text = (new ParameterTypeListInstruction(dirname(__DIR__, 3) . '/files/Project', $allowed))->instruct();

        self::assertStringContainsString(PHP_EOL . PHP_EOL . 'Package parameter types' . PHP_EOL . PHP_EOL, $text);
        self::assertStringContainsString(PackageParameterType::class . ': Package type description', $text);
        if ($allowed) {
            self::assertStringContainsString(PHP_EOL . PHP_EOL . 'Project parameter types' . PHP_EOL . PHP_EOL, $text);
            self::assertStringContainsString(ProjectParameterType::class . ': Project type description', $text);
        } else {
            self::assertStringNotContainsString('Project parameter types', $text);
            self::assertStringNotContainsString(ProjectParameterType::class, $text);
        }
    }

    /** @return array<string, array{bool}> */
    public static function projectPermissionProvider(): array
    {
        return ['allowed' => [true], 'not allowed' => [false]];
    }

    public function testNoProjectDirectoryTreatsRegisteredTypesAsPackageTypes(): void
    {
        $this->registerTypes();
        $text = (new ParameterTypeListInstruction(null, false))->instruct();

        self::assertStringContainsString(PackageParameterType::class . ': Package type description', $text);
        self::assertStringContainsString(ProjectParameterType::class . ': Project type description', $text);
        self::assertStringNotContainsString('Project parameter types', $text);
    }

    public function testMissingProjectDirectoryDoesNotFail(): void
    {
        $this->registerTypes();
        $text = (new ParameterTypeListInstruction(__DIR__ . '/missing-project-directory', false))->instruct();

        self::assertStringContainsString(PackageParameterType::class . ': Package type description', $text);
        self::assertStringNotContainsString('Project parameter types', $text);
    }

    public function testIncludesParameterTypeReuseAndCompatibilityRules(): void
    {
        $text = (new ParameterTypeListInstruction(null, false))->instruct();

        self::assertStringContainsString('Prefer using existing Parameter Types over creating duplicate implementations.', $text);
        self::assertStringContainsString('Only request source code when necessary to verify compatibility or determine how the Parameter Type should be used.', $text);
        self::assertStringContainsString('Do not assume supported value types, conversion behavior, validation rules, methods, or constructor arguments that have not been documented or verified.', $text);
        self::assertStringContainsString("If an existing Parameter Type already fulfills the user's requirements, inform the user instead of generating a duplicate implementation.", $text);
        self::assertStringContainsString('If no suitable Parameter Type exists, generate a new implementation according to the framework instructions.', $text);
    }

    private function registerTypes(): void
    {
        $registry = ParameterTypeRegistry::getInstance();
        $registry->register(PackageParameterType::class, new AsParameterType('Package type description'));
        $registry->register(ProjectParameterType::class, new AsParameterType('Project type description'));
    }
}
