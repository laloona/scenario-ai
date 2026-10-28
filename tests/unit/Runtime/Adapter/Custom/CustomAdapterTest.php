<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Adapter\Custom;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\Adapter\ConfigBuilder;
use Stateforge\Scenario\Ai\Runtime\Adapter\Custom\CustomAdapter;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\Exception\AdapterClassDoesNotExistException;
use Stateforge\Scenario\Ai\Runtime\Exception\MissingAdapterClassException;
use Stateforge\Scenario\Ai\Runtime\Exception\MissingAdapterPathException;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;
use Stateforge\Scenario\Ai\Tests\Files\CustomTestAdapter;
use Stateforge\Scenario\Ai\Tests\Files\CustomTestClient;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use Stateforge\Scenario\Core\Runtime\Application;
use function dirname;
use function file_put_contents;
use function mkdir;
use function var_export;

#[CoversClass(CustomAdapter::class)]
#[UsesClass(ConfigBuilder::class)]
#[UsesClass(AdapterConfiguration::class)]
#[UsesClass(MissingAdapterPathException::class)]
#[UsesClass(MissingAdapterClassException::class)]
#[UsesClass(AdapterClassDoesNotExistException::class)]
#[Group('runtime')]
#[Small]
final class CustomAdapterTest extends FilesystemTestCase
{
    private mixed $previousRoot;

    protected function setUp(): void
    {
        parent::setUp();
        mkdir($this->testDirectory . '/adapters');
        $property = (new ReflectionClass(Application::class))->getProperty('rootDir');
        $this->previousRoot = $property->getValue();
        $property->setValue(null, $this->testDirectory);
    }

    protected function tearDown(): void
    {
        (new ReflectionClass(Application::class))->getProperty('rootDir')->setValue(null, $this->previousRoot);
        parent::tearDown();
    }

    public function testLoadsCustomAdapterAndForwardsAllClientArguments(): void
    {
        $fixture = dirname(__DIR__, 4) . '/files/CustomTestAdapter.php';
        file_put_contents($this->testDirectory . '/adapters/adapter.php', '<?php require_once ' . var_export($fixture, true) . ';');
        $builder = new ConfigBuilder();
        $configuration = new AdapterConfiguration('custom', CustomTestAdapter::class, 'adapters', ['key' => 'value']);
        $instruction = self::createStub(Instruction::class);

        $client = (new CustomAdapter())->client($builder, $configuration, $instruction);

        self::assertInstanceOf(CustomTestClient::class, $client);
        self::assertSame($builder, $client->configBuilder);
        self::assertSame($configuration, $client->configuration);
        self::assertSame($instruction, $client->instruction);
    }

    public function testRejectsMissingAdapterPath(): void
    {
        $this->expectException(MissingAdapterPathException::class);

        (new CustomAdapter())->client(new ConfigBuilder(), new AdapterConfiguration('custom', null, null, []), self::createStub(Instruction::class));
    }

    public function testRejectsMissingAdapterClass(): void
    {
        $this->expectException(MissingAdapterClassException::class);

        (new CustomAdapter())->client(new ConfigBuilder(), new AdapterConfiguration('custom', null, 'adapters', []), self::createStub(Instruction::class));
    }

    public function testRejectsUnknownAdapterClass(): void
    {
        $this->expectException(AdapterClassDoesNotExistException::class);
        $this->expectExceptionMessage('Adapter class "MissingCustomAiAdapter" does not exist');

        (new CustomAdapter())->client(new ConfigBuilder(), new AdapterConfiguration('custom', 'MissingCustomAiAdapter', 'adapters', []), self::createStub(Instruction::class));
    }

    public function testRejectsClassWithoutAdapterInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class "stdClass" must implement AiAdapter.');

        (new CustomAdapter())->client(new ConfigBuilder(), new AdapterConfiguration('custom', 'stdClass', 'adapters', []), self::createStub(Instruction::class));
    }
}
