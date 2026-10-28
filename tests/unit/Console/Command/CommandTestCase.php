<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Console\Command;

use ReflectionClass;
use ReflectionProperty;
use Stateforge\Scenario\Ai\Runtime\Adapter\AdapterRegistry;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use Stateforge\Scenario\Core\Contract\CliInput;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\ApplicationState;
use function array_reverse;
use function chdir;
use function getcwd;

abstract class CommandTestCase extends FilesystemTestCase
{
    /** @var list<array{property: ReflectionProperty, value: mixed}> */
    private array $previousState = [];

    private string $previousDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $directory = getcwd();
        self::assertIsString($directory);
        $this->previousDirectory = $directory;
        chdir($this->testDirectory);
        $this->replaceStaticProperty(Application::class, 'rootDir', $this->testDirectory);
        $this->replaceStaticProperty(Application::class, 'configuration', null);
        $this->replaceStaticProperty(ApplicationState::class, 'throwable', null);
        $this->replaceStaticProperty(ApplicationState::class, 'classes', []);
        $this->replaceStaticProperty(Ai::class, 'config', null);
        // An empty registry prevents fallback to any real provider.
        $this->replaceStaticProperty(Ai::class, 'registry', new AdapterRegistry());
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->previousState) as $state) {
            $state['property']->setValue(null, $state['value']);
        }
        $this->previousState = [];
        chdir($this->previousDirectory);
        parent::tearDown();
    }

    /** @param class-string $class */
    private function replaceStaticProperty(string $class, string $name, mixed $value): void
    {
        $property = (new ReflectionClass($class))->getProperty($name);
        $this->previousState[] = ['property' => $property, 'value' => $property->getValue()];
        $property->setValue(null, $value);
    }

    protected function input(): CliInput
    {
        $input = self::createStub(CliInput::class);
        $input->method('option')->willReturnMap([['quiet', true]]);

        return $input;
    }
}
