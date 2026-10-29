<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Ai\Runtime\Configuration;
use Stateforge\Scenario\Ai\Runtime\ConfigurationBuilder;
use Stateforge\Scenario\Ai\Runtime\Exception\NoConfigException;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use Stateforge\Scenario\Core\Runtime\Application;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function mkdir;
use function str_replace;
use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

#[CoversClass(Ai::class)]
#[UsesClass(ConfigurationBuilder::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(AdapterConfiguration::class)]
#[UsesClass(NoConfigException::class)]
#[Group('runtime')]
#[Small]
final class AiTest extends FilesystemTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new ReflectionClass(Application::class))->getProperty('rootDir')->setValue(null, $this->testDirectory);
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, null);
    }

    protected function tearDown(): void
    {
        (new ReflectionClass(Ai::class))->getProperty('config')->setValue(null, null);
        (new ReflectionClass(Application::class))->getProperty('rootDir')->setValue(null, null);
        parent::tearDown();
    }

    #[DataProvider('filenameProvider')]
    public function testLoadsConfigurationUsingSchemaFromProjectVendorDirectory(string $filename): void
    {
        $this->writeVendorSchema();
        file_put_contents($this->testDirectory . DIRECTORY_SEPARATOR . $filename, $this->configurationXml(7));

        $configuration = Ai::config();

        self::assertSame(7, $configuration->maxQuestions);
        self::assertFalse($configuration->allowProjectInformation);
        self::assertSame('docs/project', $configuration->projectDoc);
        self::assertSame('openai', $configuration->adapter->adapter);
        self::assertSame(['apiKey' => 'test-key'], $configuration->adapter->vars);
        self::assertDirectoryDoesNotExist($this->testDirectory . DIRECTORY_SEPARATOR . 'xsd');
    }

    /** @return array<string, array{string}> */
    public static function filenameProvider(): array
    {
        return ['local' => ['scenario-ai.xml'], 'distributed' => ['scenario-ai.dist.xml']];
    }

    public function testReturnsCachedConfigurationWithoutReadingFileAgain(): void
    {
        $this->writeVendorSchema();
        $path = $this->testDirectory . DIRECTORY_SEPARATOR . 'scenario-ai.xml';
        file_put_contents($path, $this->configurationXml(7));
        $configuration = Ai::config();
        file_put_contents($path, $this->configurationXml(12));

        self::assertSame($configuration, Ai::config());
        self::assertSame(7, Ai::config()->maxQuestions);
    }

    public function testReportsMissingVendorSchema(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unable to find xsd file: ' . $this->schemaPath());

        Ai::config();
    }

    public function testReportsMissingConfiguration(): void
    {
        $this->writeVendorSchema();
        $this->expectException(NoConfigException::class);

        Ai::config();
    }

    private function writeVendorSchema(): void
    {
        $schema = file_get_contents(__DIR__ . '/../../../xsd/scenario-ai.xsd');
        self::assertIsString($schema);
        // Only the vendor schema accepts this attribute, proving which XSD Ai::config() uses.
        $schema = str_replace(
            '<xsd:attribute name="projectDoc"',
            '<xsd:attribute name="vendorSchemaMarker" type="xsd:string"/>' . PHP_EOL . '<xsd:attribute name="projectDoc"',
            $schema,
        );
        mkdir(dirname($this->schemaPath()), 0777, true);
        file_put_contents($this->schemaPath(), $schema);
    }

    private function schemaPath(): string
    {
        return $this->testDirectory . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'stateforge' . DIRECTORY_SEPARATOR . 'scenario-ai' . DIRECTORY_SEPARATOR . 'xsd' . DIRECTORY_SEPARATOR . 'scenario-ai.xsd';
    }

    private function configurationXml(int $questions): string
    {
        return '<scenario-ai vendorSchemaMarker="vendor" projectDoc="docs/project" maxQuestions="' . $questions . '"><adapter type="openai"><var name="apiKey">test-key</var></adapter></scenario-ai>';
    }
}
