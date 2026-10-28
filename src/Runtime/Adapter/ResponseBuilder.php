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

use function array_filter;
use function array_values;
use function class_exists;
use function is_array;
use function is_string;

final class ResponseBuilder
{
    /**
     * @param array<string, mixed> $response
     */
    public function build(string $id, array $response): AiResponse
    {
        $response['id'] = $id;
        $response['type'] = isset($response['type']) && is_string($response['type'])
            ? GenerationType::from($response['type'])
            : null;

        $response['php'] = isset($response['php']) && is_string($response['php'])
            ? $response['php']
            : null;

        $response['question'] = isset($response['question']) && is_string($response['question'])
            ? $response['question']
            : null;

        $response['classes'] = isset($response['classes']) && is_array($response['classes'])
            ? array_values(array_filter(
                $response['classes'],
                static fn (mixed $class): bool => is_string($class) && class_exists($class),
            ))
            : [];

        return new AiResponse(
            $response['id'],
            $response['php'],
            $response['type'],
            $response['question'],
            $response['classes'],
        );
    }
}
