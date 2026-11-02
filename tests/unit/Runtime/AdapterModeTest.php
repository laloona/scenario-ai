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
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;

#[CoversClass(AdapterMode::class)]
#[Small]
final class AdapterModeTest extends TestCase
{
    #[DataProvider('modes')]
    public function testIndicatesSupportedUsage(AdapterMode $mode, bool $cli, bool $skill): void
    {
        self::assertSame($cli, $mode->isCli());
        self::assertSame($skill, $mode->isSkill());
    }

    /** @return iterable<string, array{AdapterMode, bool, bool}> */
    public static function modes(): iterable
    {
        yield 'cli' => [AdapterMode::Cli, true, false];
        yield 'skill' => [AdapterMode::Skill, false, true];
        yield 'all' => [AdapterMode::All, true, true];
    }
}
