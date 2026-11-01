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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\SourceCodeRegistry;
use Stateforge\Scenario\Ai\Tests\Unit\SourceCodeRegistryMock;
use stdClass;

#[CoversClass(SourceCodeRegistry::class)]
#[Group('runtime')]
#[Small]
final class SourceCodeRegistryTest extends TestCase
{
    use SourceCodeRegistryMock;

    protected function setUp(): void
    {
        $this->resetSourceCodeRegistry();
    }

    protected function tearDown(): void
    {
        $this->resetSourceCodeRegistry();
    }

    public function testUnknownClassIsNotRegistered(): void
    {
        self::assertFalse((new SourceCodeRegistry())->hasClass(stdClass::class));
    }

    public function testClassesAreSharedAcrossRegistryInstances(): void
    {
        (new SourceCodeRegistry())->addClass(stdClass::class);
        $other = new SourceCodeRegistry();

        self::assertTrue($other->hasClass(stdClass::class));
        self::assertFalse($other->hasClass(self::class));
    }

    public function testAddingTheSameClassTwiceDoesNotCreateDuplicates(): void
    {
        $registry = new SourceCodeRegistry();
        $registry->addClass(stdClass::class);
        $registry->addClass(stdClass::class);

        self::assertSame([stdClass::class], (new ReflectionClass(SourceCodeRegistry::class))->getProperty('classes')->getValue());
    }
}
