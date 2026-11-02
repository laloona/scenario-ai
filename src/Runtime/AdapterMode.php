<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime;

enum AdapterMode: string
{
    case Cli = 'cli';
    case Skill = 'skill';
    case All = 'all';

    public function isCli(): bool
    {
        return match ($this) {
            self::Skill => false,
            default => true,
        };
    }

    public function isSkill(): bool
    {
        return match ($this) {
            self::Cli => false,
            default => true,
        };
    }
}
