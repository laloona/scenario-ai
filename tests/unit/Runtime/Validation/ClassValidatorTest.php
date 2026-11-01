<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Adapter\ResponseBuilder;
use Stateforge\Scenario\Ai\Runtime\Exception\AllClassesProvidedException;
use Stateforge\Scenario\Ai\Runtime\Exception\ClassDoesNotExistException;
use Stateforge\Scenario\Ai\Runtime\SourceCodeRegistry;
use Stateforge\Scenario\Ai\Runtime\Validation\ClassValidator;
use Stateforge\Scenario\Ai\Tests\Unit\SourceCodeRegistryMock;
use stdClass;

#[CoversClass(ClassValidator::class)]
#[UsesClass(AiResponse::class)]
#[UsesClass(SourceCodeRegistry::class)]
#[UsesClass(AllClassesProvidedException::class)]
#[UsesClass(ResponseBuilder::class)]
#[UsesClass(ClassDoesNotExistException::class)]
#[Group('runtime')]
#[Small]
final class ClassValidatorTest extends TestCase
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

    public function testAcceptsNoReferences(): void
    {
        $response = new AiResponse(null, null, null, null, [], null);

        (new ClassValidator())->validate($response);

        self::assertSame([], $response->classes);
        self::assertNull($response->match);
    }

    public function testAcceptsExistingClassesAndMatch(): void
    {
        $response = new AiResponse(null, null, null, null, [stdClass::class, self::class], stdClass::class);

        (new ClassValidator())->validate($response);

        self::assertSame([stdClass::class, self::class], $response->classes);
        self::assertSame(stdClass::class, $response->match);
    }

    /** @param list<string> $classes */
    #[DataProvider('missingReferences')]
    public function testRejectsTheFirstMissingReference(array $classes, ?string $match, string $missing): void
    {
        $response = (new ResponseBuilder())->build('id', [
            'type' => null, 'php' => null, 'question' => null, 'classes' => $classes, 'match' => $match,
        ]);
        $this->expectException(ClassDoesNotExistException::class);
        $this->expectExceptionMessage('class "' . $missing . '" does not exist.');

        (new ClassValidator())->validate($response);
    }

    /** @return iterable<string, array{list<string>, ?string, string}> */
    public static function missingReferences(): iterable
    {
        yield 'class after existing class' => [[stdClass::class, 'Missing\\RequestedClass'], null, 'Missing\\RequestedClass'];
        yield 'match without classes' => [[], 'Missing\\MatchingClass', 'Missing\\MatchingClass'];
        yield 'match after existing classes' => [[stdClass::class], 'Missing\\MatchingClass', 'Missing\\MatchingClass'];
        yield 'classes are checked before match' => [['Missing\\FirstClass', 'Missing\\SecondClass'], 'Missing\\MatchingClass', 'Missing\\FirstClass'];
    }

    public function testRejectsRequestsContainingOnlyPreviouslyProvidedClasses(): void
    {
        $registry = new SourceCodeRegistry();
        $registry->addClass(stdClass::class);
        $registry->addClass(self::class);
        $response = new AiResponse(null, null, null, null, [stdClass::class, self::class], null);
        $this->expectException(AllClassesProvidedException::class);
        $this->expectExceptionMessage('All requested class definitions have already been provided');

        (new ClassValidator())->validate($response);
    }

    public function testAcceptsRequestsContainingAGenuinelyMissingDefinition(): void
    {
        (new SourceCodeRegistry())->addClass(stdClass::class);
        $response = new AiResponse(null, null, null, null, [stdClass::class, self::class], null);

        (new ClassValidator())->validate($response);

        self::assertSame([stdClass::class, self::class], $response->classes);
    }

    public function testPreviouslyProvidedMatchDoesNotRequireItsSourceAgain(): void
    {
        (new SourceCodeRegistry())->addClass(self::class);
        $response = new AiResponse(null, null, null, null, [], self::class);

        (new ClassValidator())->validate($response);

        self::assertSame(self::class, $response->match);
    }

}
