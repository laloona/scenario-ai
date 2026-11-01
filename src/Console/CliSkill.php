<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Console;

use Stateforge\Scenario\Ai\Contract\AiContext;
use Stateforge\Scenario\Ai\Runtime\Ai;
use Stateforge\Scenario\Core\Contract\CliOutput;

final class CliSkill
{
    public function generate(
        CliOutput $output,
        ?AiContext $context,
    ): bool {
        $skill = Ai::skill($context);

        if ($skill === null) {
            $output->error('Skill generation failed.');
            return false;
        }

        $output->success('Skill was generated successfully: ' . $skill);
        return true;
    }
}
