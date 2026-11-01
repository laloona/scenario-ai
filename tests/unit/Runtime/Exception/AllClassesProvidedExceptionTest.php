<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Exception\AllClassesProvidedException;
use Stateforge\Scenario\Ai\Runtime\Exception\ValidatorException;

#[CoversClass(AllClassesProvidedException::class)]
#[Group('runtime')]
#[Small]
final class AllClassesProvidedExceptionTest extends TestCase
{
    public function testIsAValidatorExceptionWithActionableRetryInstructions(): void
    {
        $exception = new AllClassesProvidedException();
        $message = $exception->getMessage();

        self::assertInstanceOf(ValidatorException::class, $exception);
        self::assertStringContainsString('All requested class definitions have already been provided in the current conversation.', $message);
        self::assertStringContainsString('Do not request these classes again.', $message);
        self::assertStringContainsString('Use the previously provided source code to continue evaluating the request.', $message);
        self::assertStringContainsString('a matching existing implementation', $message);
        self::assertStringContainsString('generated PHP code', $message);
        self::assertStringContainsString('a request for genuinely missing class definitions', $message);
        self::assertStringContainsString('a clarification question if essential information is missing', $message);
    }
}
