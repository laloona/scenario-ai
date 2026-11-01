<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Adapter\Gemini;

use Stateforge\Scenario\Ai\Contract\AiSkill;
use Stateforge\Scenario\Ai\Runtime\Adapter\FileBuilder;
use Stateforge\Scenario\Core\Runtime\Application;

final class GeminiSkill implements AiSkill
{
    public function __construct(
        private readonly FileBuilder $fileBuilder,
    ) {
    }

    public function generate(): ?string
    {
        return $this->fileBuilder->build(
            [
                Application::getRootDir(),
                '.agents',
                'skills',
                'stateforge-scenario',
                'SKILL.md',
            ],
        );
    }
}
