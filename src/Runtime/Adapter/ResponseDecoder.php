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

use RuntimeException;
use function is_array;
use function is_string;
use function json_decode;
use const JSON_THROW_ON_ERROR;

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
            JSON_THROW_ON_ERROR,
        );

        if (is_array($result) === false) {
            throw new RuntimeException('Claude output is not a JSON object.');
        }

        /** @var array<string, mixed> $validatedResult */
        $validatedResult = [];
        foreach ($result as $key => $value) {
            if (is_string($key) === false) {
                throw new RuntimeException('Invalid Claude output structure.');
            }

            $validatedResult[$key] = $value;
        }

        return $this->responseBuilder->build(
            $id,
            $validatedResult,
        );
    }
}
