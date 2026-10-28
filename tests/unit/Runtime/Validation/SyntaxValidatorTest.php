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

use PhpParser\Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Exception\InvalidGeneratedCodeException;
use Stateforge\Scenario\Ai\Runtime\Validation\SyntaxValidator;

#[CoversClass(SyntaxValidator::class)]
#[UsesClass(InvalidGeneratedCodeException::class)]
#[Group('runtime')]
#[Small]
final class SyntaxValidatorTest extends TestCase
{
    public function testAcceptsValidPhpWithoutExecutingIt(): void
    {
        $code = '<?php throw new \\RuntimeException("Must not execute");';

        (new SyntaxValidator())->validate($code);

        self::assertStringContainsString('Must not execute', $code);
    }

    public function testWrapsParserErrorAndPreservesPreviousException(): void
    {
        try {
            (new SyntaxValidator())->validate('<?php class {');
            self::fail('Invalid syntax must be rejected.');
        } catch (InvalidGeneratedCodeException $exception) {
            self::assertInstanceOf(Error::class, $exception->getPrevious());
            self::assertStringContainsString('Syntax error', $exception->getMessage());
        }
    }
}
