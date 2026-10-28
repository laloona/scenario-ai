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
use Stateforge\Scenario\Ai\Runtime\Adapter\AdapterRegistry;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use Stateforge\Scenario\Core\Contract\CliInput;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\ApplicationState;
use function chdir;
use function dirname;

abstract class CommandTestCase extends FilesystemTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        chdir($this->testDirectory);
        $this->setStaticProperty(Application::class, 'rootDir', $this->testDirectory);
        $this->setStaticProperty(Application::class, 'configuration', null);
        $this->setStaticProperty(ApplicationState::class, 'throwable', null);
        $this->setStaticProperty(ApplicationState::class, 'classes', []);
        $this->setStaticProperty(Ai::class, 'config', null);
        // An empty registry prevents fallback to any real provider.
        $this->setStaticProperty(Ai::class, 'registry', new AdapterRegistry());
    }

    protected function tearDown(): void
    {
        $this->setStaticProperty(Application::class, 'rootDir', null);
        $this->setStaticProperty(Application::class, 'configuration', null);
        $this->setStaticProperty(ApplicationState::class, 'throwable', null);
        $this->setStaticProperty(ApplicationState::class, 'classes', []);
        $this->setStaticProperty(Ai::class, 'config', null);
        $this->setStaticProperty(Ai::class, 'registry', null);
        chdir(dirname(__DIR__, 4));
        parent::tearDown();
    }

    /** @param class-string $class */
    private function setStaticProperty(string $class, string $name, mixed $value): void
    {
        $property = (new ReflectionClass($class))->getProperty($name);
        $property->setValue(null, $value);
    }

    protected function input(): CliInput
    {
        $input = self::createStub(CliInput::class);
        $input->method('option')->willReturnMap([['quiet', true]]);

        return $input;
    }
}
