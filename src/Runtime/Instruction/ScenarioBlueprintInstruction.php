<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Instruction;

use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Value\SuiteValue;
use Stateforge\Scenario\Core\Runtime\NamespaceResolver;
use function count;
use function file_get_contents;
use const PHP_EOL;

final class ScenarioBlueprintInstruction implements Instruction
{
    /**
     * @param array<string, SuiteValue> $suites
     */
    public function __construct(
        private readonly string $scenarioPath,
        private readonly array $suites,
    ) {
    }

    public function instruct(AdapterMode $mode): string
    {
        $instructions = <<<'INSTRUCTIONS'
Scenario Rules

- A Scenario MUST extend the Scenario base class defined by the selected blueprint.
- A Scenario MUST use the #[AsScenario] attribute.
- The Scenario name MUST clearly describe the resulting application state.
- The Scenario description MUST explain its purpose.
- Implement all requested behavior that can be implemented using the available framework and project information.
- Prefer executable PHP code over implementation comments.
- Use only documented framework APIs and known project dependencies.
- Prefer existing Scenario composition when suitable Scenarios are explicitly available.
- Request missing project class definitions when necessary.
- Never invent application-specific APIs to replace missing information.
- Never generate an empty up() method unless the requested state is fully established through supported Scenario composition.
- If essential implementation details remain unavailable, request the missing information instead of generating an incomplete Scenario.

Scenario Blueprint: 

INSTRUCTIONS . file_get_contents($this->scenarioPath);
        $instructions .= <<<'INSTRUCTIONS'

Scenario Namespace Rules

- If Scenario Suites are available, select the most appropriate Suite namespace based on the user’s request and the Suite descriptions.
- The generated Scenario MUST use one of the configured Suite namespaces or a sub-namespace of it.
- Never invent a namespace outside the configured Suite namespaces when Suites are available.
- If no Scenario Suites are available, choose an appropriate namespace based on the selected blueprint and the user’s request.
- Do not ask the user to specify a namespace when a suitable configured namespace is available.

INSTRUCTIONS;
        if ($mode->isCli()
            && count($this->suites) > 0) {
            $resolver = new NamespaceResolver();
            $instructions .= PHP_EOL . '- Available Scenario Suites: ';
            foreach ($this->suites as $suite) {
                $instructions .= PHP_EOL . "\t" .
                    '- [ name: ' . $suite->name . ', description: ' . $suite->description. ', directory: ' . $suite->directory . ', namespace: ' . $resolver->toNamespace($suite->directory) . ' ]';
            }
        }

        return $instructions;
    }
}
