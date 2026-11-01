<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Files;

use Stateforge\Scenario\Ai\Contract\AiAdapter;
use Stateforge\Scenario\Ai\Contract\AiClient;
use Stateforge\Scenario\Ai\Contract\AiSkill;
use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Runtime\Adapter\ConfigBuilder;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;

final class CustomTestAdapter implements AiAdapter
{
    public function client(ConfigBuilder $configBuilder, AdapterConfiguration $adapterConfiguration, Instruction $instruction): AiClient
    {
        return new CustomTestClient($configBuilder, $adapterConfiguration, $instruction);
    }
    public function skill(AdapterConfiguration $adapterConfiguration, Instruction $instruction): AiSkill
    {
        return new class ($adapterConfiguration, $instruction) implements AiSkill {
            public function __construct(
                private readonly AdapterConfiguration $configuration,
                private readonly Instruction $instruction,
            ) {
            }

            public function generate(): string
            {
                return $this->instruction->instruct($this->configuration->mode);
            }
        };
    }
}
