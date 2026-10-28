<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Adapter;

use LogicException;
use Stateforge\Scenario\Ai\Contract\AiClient;
use Stateforge\Scenario\Ai\Contract\AiContext;
use Stateforge\Scenario\Ai\Runtime\Configuration;
use Stateforge\Scenario\Ai\Runtime\Exception\ValidatorException;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;
use function array_key_last;
use function count;

final class AiConversation
{
    /**
     * @var list<AiAudit>
     */
    private array $audits = [];

    public function __construct(
        private readonly AiContext $context,
        private readonly Configuration $configuration,
        private readonly AdapterRegistry $registry,
    ) {

    }

    public function talk(string $prompt): void
    {
        $this->run(new AiRequest(null, $prompt, count($this->audits) + 1));
    }

    private function client(Instruction $instruction): AiClient
    {
        return $this->registry->get($this->configuration->adapter->adapter)->client(
            new ConfigBuilder(),
            $this->configuration->adapter,
            $instruction,
        );
    }

    private function run(AiRequest $request): AiResponse
    {
        $client = $this->client($this->context->instruction($request));

        $response = $client->request($request, $this->audits);
        $this->audit($request, $response);

        try {
            $this->context->validator()->validate($response);
        } catch (ValidatorException $exception) {
            $request = new AiRequest(
                $response->id,
                $exception->getMessage(),
                $request->counter,
            );
            $response = $client->request($request, $this->audits);
            $this->audit($request, $response);

            $this->context->validator()->validate($response);
        }

        return $response;
    }

    private function audit(AiRequest $request, AiResponse $response): void
    {
        $this->audits[] = new AiAudit($request, $response);
    }

    public function response(): AiResponse
    {
        return $this->lastAudit()->response;
    }

    public function completed(): bool
    {
        $audit = $this->lastAudit();
        return $audit->request->counter >= $this->configuration->maxQuestions
            || ($audit->response->php !== null
                    && $audit->response->type !== null
                    && $audit->response->question === null
                    && count($audit->response->classes) === 0);
    }

    public function question(): ?string
    {
        return $this->lastAudit()->response->question;
    }

    /**
     * @return list<class-string>
     */
    public function classes(): array
    {
        return $this->lastAudit()->response->classes;
    }

    private function lastAudit(): AiAudit
    {
        if (count($this->audits) === 0) {
            throw new LogicException('No audit available.');
        }

        return $this->audits[array_key_last($this->audits)];
    }
}
