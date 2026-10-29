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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\Configuration;
use Stateforge\Scenario\Ai\Runtime\ConfigurationBuilder;
use Stateforge\Scenario\Ai\Runtime\Exception\NoConfigException;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\ConfigurationFinder;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\XMLParser;
use Stateforge\Scenario\Core\Runtime\Exception\Application\XMLParserException;
use function file_put_contents;

#[CoversClass(ConfigurationBuilder::class)]
#[CoversClass(AdapterConfiguration::class)]
#[CoversClass(Configuration::class)]
#[UsesClass(NoConfigException::class)]
#[Group('runtime')]
#[Small]
final class ConfigurationBuilderTest extends FilesystemTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $property = (new ReflectionClass(Application::class))->getProperty('rootDir');
        $property->setValue(null, $this->testDirectory);
    }

    protected function tearDown(): void
    {
        (new ReflectionClass(Application::class))->getProperty('rootDir')->setValue(null, null);
        parent::tearDown();
    }

    #[DataProvider('filenameProvider')]
    public function testBuildLoadsSupportedConfigurationFile(string $filename): void
    {
        file_put_contents($this->testDirectory . '/' . $filename, <<<'XML'
<scenario-ai projectDoc="docs/project" maxQuestions="7">
    <adapter type="custom" class="App\CustomAdapter" path="src/Adapter">
        <var name="apiKey">test-key</var>
        <var name="endpoint">https://example.test/?a=1&amp;b=2</var>
    </adapter>
</scenario-ai>
XML);

        $configuration = $this->builder()->build();

        self::assertSame('docs/project', $configuration->projectDoc);
        self::assertSame(7, $configuration->maxQuestions);
        self::assertSame('custom', $configuration->adapter->adapter);
        self::assertSame('App\CustomAdapter', $configuration->adapter->class);
        self::assertSame('src/Adapter', $configuration->adapter->path);
        self::assertSame([
            'apiKey' => 'test-key',
            'endpoint' => 'https://example.test/?a=1&b=2',
        ], $configuration->adapter->vars);
    }

    /** @return array<string, array{string}> */
    public static function filenameProvider(): array
    {
        return ['local' => ['scenario-ai.xml'], 'distributed' => ['scenario-ai.dist.xml']];
    }

    public function testBuildUsesDefaultsForMissingOptionalValues(): void
    {
        $this->writeConfiguration('<scenario-ai><adapter type="openai" /></scenario-ai>');

        $configuration = $this->builder()->build();

        self::assertNull($configuration->projectDoc);
        self::assertFalse($configuration->allowProjectInformation);
        self::assertSame(3, $configuration->maxQuestions);
        self::assertSame('openai', $configuration->adapter->adapter);
        self::assertNull($configuration->adapter->class);
        self::assertNull($configuration->adapter->path);
        self::assertSame([], $configuration->adapter->vars);
    }

    #[DataProvider('projectPermissionProvider')]
    public function testBuildParsesProjectInformationPermission(string $value, bool $expected): void
    {
        $this->writeConfiguration('<scenario-ai allowProjectInformation="' . $value . '"><adapter type="openai" /></scenario-ai>');

        self::assertSame($expected, $this->builder()->build()->allowProjectInformation);
    }

    /** @return array<string, array{string, bool}> */
    public static function projectPermissionProvider(): array
    {
        return ['true' => ['true', true], 'one' => ['1', true], 'false' => ['false', false], 'zero' => ['0', false]];
    }

    #[DataProvider('questionLimitProvider')]
    public function testBuildHandlesQuestionLimits(string $configured, int $expected): void
    {
        $this->writeConfiguration('<scenario-ai maxQuestions="' . $configured . '"><adapter type="openai" /></scenario-ai>');

        self::assertSame($expected, $this->builder()->build()->maxQuestions);
    }

    /** @return array<string, array{string, int}> */
    public static function questionLimitProvider(): array
    {
        return ['zero' => ['0', 0], 'positive' => ['12', 12], 'negative uses default' => ['-1', 3]];
    }

    public function testBuildRejectsMissingConfiguration(): void
    {
        $this->expectException(NoConfigException::class);
        $this->expectExceptionMessage('No configuration found.');

        $this->builder()->build();
    }

    public function testBuildIgnoresCoreConfigurationFile(): void
    {
        file_put_contents($this->testDirectory . '/scenario.xml', '<scenario />');
        $this->expectException(NoConfigException::class);

        $this->builder()->build();
    }

    #[DataProvider('invalidXmlProvider')]
    public function testBuildPropagatesSchemaValidationErrors(string $xml): void
    {
        $this->writeConfiguration($xml);
        $this->expectException(XMLParserException::class);
        $this->expectExceptionMessage('configuration xml does not validate');

        $this->builder()->build();
    }

    /** @return array<string, array{string}> */
    public static function invalidXmlProvider(): array
    {
        return [
            'invalid project permission' => ['<scenario-ai allowProjectInformation="invalid"><adapter type="openai" /></scenario-ai>'],
            'missing adapter' => ['<scenario-ai />'],
            'missing adapter type' => ['<scenario-ai><adapter /></scenario-ai>'],
            'invalid question limit' => ['<scenario-ai maxQuestions="invalid"><adapter type="openai" /></scenario-ai>'],
            'duplicate variable names' => ['<scenario-ai><adapter type="openai"><var name="key">first</var><var name="key">second</var></adapter></scenario-ai>'],
        ];
    }

    public function testBuildPropagatesMalformedXmlError(): void
    {
        $this->writeConfiguration('<scenario-ai>');
        $this->expectException(XMLParserException::class);
        $this->expectExceptionMessage('unable to load configuration xml');

        $this->builder()->build();
    }

    private function writeConfiguration(string $xml): void
    {
        file_put_contents($this->testDirectory . '/scenario-ai.xml', $xml);
    }

    private function builder(): ConfigurationBuilder
    {
        return new ConfigurationBuilder(
            new ConfigurationFinder(),
            new XMLParser(__DIR__ . '/../../../xsd/scenario-ai.xsd'),
        );
    }
}
