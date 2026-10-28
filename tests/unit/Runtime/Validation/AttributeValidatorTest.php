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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\AstBuilder;
use Stateforge\Scenario\Ai\Runtime\Exception\AttributeValidatorException;
use Stateforge\Scenario\Ai\Runtime\ScenarioAst;
use Stateforge\Scenario\Ai\Runtime\Validation\AttributeFinder;
use Stateforge\Scenario\Ai\Runtime\Validation\AttributeValidator;

#[CoversClass(AttributeValidator::class)]
#[UsesClass(AstBuilder::class)]
#[UsesClass(ScenarioAst::class)]
#[UsesClass(AttributeFinder::class)]
#[UsesClass(AttributeValidatorException::class)]
#[Group('runtime')]
#[Small]
final class AttributeValidatorTest extends TestCase
{
    public function testAcceptsRequiredAttributeResolvedFromAlias(): void
    {
        $ast = (new AstBuilder())->build('<?php use App\\Required as Alias; #[Alias] class Demo {}');

        (new AttributeValidator(new AttributeFinder(), 'App\\Required'))->validate($ast);

        self::assertSame('Demo', $ast->getClassName());
    }

    public function testRejectsMissingRequiredAttribute(): void
    {
        $ast = (new AstBuilder())->build('<?php #[App\\Other] class Demo {}');
        $this->expectException(AttributeValidatorException::class);
        $this->expectExceptionMessage('Missing required attribute "App\\Required".');

        (new AttributeValidator(new AttributeFinder(), 'App\\Required'))->validate($ast);
    }
}
