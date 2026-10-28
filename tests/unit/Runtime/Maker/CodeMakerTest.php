<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Maker;

use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\AstBuilder;
use Stateforge\Scenario\Ai\Runtime\Maker\CodeMaker;
use Stateforge\Scenario\Ai\Runtime\Maker\CodeWriter;
use Stateforge\Scenario\Ai\Runtime\Maker\DirectoryResolver;
use Stateforge\Scenario\Ai\Runtime\ScenarioAst;
use Stateforge\Scenario\Ai\Tests\Unit\FilesystemTestCase;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Configuration;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Value\SuiteValue;
use function chdir;
use function dirname;
use function file_get_contents;
use const DIRECTORY_SEPARATOR;

#[CoversClass(CodeMaker::class)]
#[UsesClass(AstBuilder::class)]
#[UsesClass(ScenarioAst::class)]
#[UsesClass(DirectoryResolver::class)]
#[UsesClass(CodeWriter::class)]
#[Group('runtime')]
#[Small]
final class CodeMakerTest extends FilesystemTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        chdir($this->testDirectory);
        $property = (new ReflectionClass(Application::class))->getProperty('configuration');
        $property->setValue(null, null);
    }

    protected function tearDown(): void
    {
        (new ReflectionClass(Application::class))->getProperty('configuration')->setValue(null, null);
        chdir(dirname(__DIR__, 4));
        parent::tearDown();
    }

    public function testRequiresInitializedConfiguration(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Scenario configuration is not initialized.');

        (new CodeMaker())->make(GenerationType::Scenario, '<?php class Demo {}');
    }

    #[DataProvider('generationProvider')]
    public function testWritesCodeToConfiguredDirectory(string $type, string $namespace, string $expectedPath): void
    {
        $configuration = self::createStub(Configuration::class);
        $configuration->method('getSuites')->willReturn([
            'main' => new SuiteValue('main', 'Main suite', 'scenario/main'),
            'other' => new SuiteValue('other', 'Other suite', 'scenario/other'),
        ]);
        $configuration->method('getParameterDirectory')->willReturn('scenario/parameter');
        (new ReflectionClass(Application::class))->getProperty('configuration')->setValue(null, $configuration);
        $code = '<?php namespace ' . $namespace . '; class Demo {}';

        $path = (new CodeMaker())->make(GenerationType::from($type), $code);

        self::assertSame($expectedPath . DIRECTORY_SEPARATOR . 'Demo.php.ai', $path);
        self::assertSame($code, file_get_contents($path));
    }

    /** @return array<string, array{string, string, string}> */
    public static function generationProvider(): array
    {
        return [
            'scenario' => ['scenario', 'Stateforge\\Suite\\Scenario\\Other', 'scenario/other'],
            'nested scenario' => ['scenario', 'Stateforge\\Suite\\Scenario\\Main\\Users', 'scenario/main' . DIRECTORY_SEPARATOR . 'Users'],
            'parameter' => ['parameter', 'Stateforge\\Suite\\Scenario\\Parameter', 'scenario/parameter'],
        ];
    }
}
