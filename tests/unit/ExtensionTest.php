<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Console\CliApplication;
use Stateforge\Scenario\Ai\Extension;

#[CoversClass(Extension::class)]
#[Group('runtime')]
#[Small]
final class ExtensionTest extends TestCase
{
    public function testApplicationReturnsNull(): void
    {
        self::assertNull((new Extension())->application());
    }

    public function testCliReturnsAiCliApplication(): void
    {
        self::assertInstanceOf(CliApplication::class, (new Extension())->cli());
    }
}
