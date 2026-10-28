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

use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\Validation\AttributeFinder;

#[CoversClass(AttributeFinder::class)]
#[Group('runtime')]
#[Small]
final class AttributeFinderTest extends TestCase
{
    public function testFindsAttributeAcrossGroupsIgnoringCaseAndLeadingSlash(): void
    {
        $groups = [new AttributeGroup([new Attribute(new Name('App\\Other'))]),
            new AttributeGroup([new Attribute(new Name('App\\Required'))])];

        self::assertTrue((new AttributeFinder())->hasAttribute($groups, '\\app\\required'));
    }

    public function testReturnsFalseForMissingAttributeAndEmptyGroups(): void
    {
        $finder = new AttributeFinder();

        self::assertFalse($finder->hasAttribute([], 'App\\Required'));
        self::assertFalse($finder->hasAttribute([new AttributeGroup([])], 'App\\Required'));
        self::assertFalse($finder->hasAttribute([new AttributeGroup([new Attribute(new Name('App\\Other'))])], 'App\\Required'));
    }
}
