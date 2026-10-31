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

use Stateforge\Scenario\Ai\Runtime\Exception\InvalidJsonResponseException;
use Stateforge\Scenario\Ai\Runtime\Exception\WrongResponseStructureException;
use function is_array;
use function is_string;
use function json_decode;

final class ResponseDecoder
{
    public function __construct(
        private readonly ResponseBuilder $responseBuilder,
    ) {
    }

    public function decode(string $id, string $answer): AiResponse
    {
        $result = json_decode(
            $answer,
            true,
            512,
        );

        if (is_array($result) === false) {
            throw new InvalidJsonResponseException();
        }

        /** @var array<string, mixed> $validatedResult */
        $validatedResult = [];
        foreach ($result as $key => $value) {
            if (is_string($key) === false) {
                throw new WrongResponseStructureException();
            }

            $validatedResult[$key] = $value;
        }

        return $this->responseBuilder->build(
            $id,
            $validatedResult,
        );
    }
}
