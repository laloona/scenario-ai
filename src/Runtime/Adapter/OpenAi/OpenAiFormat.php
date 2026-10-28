<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi;

final class OpenAiFormat
{
    /**
     * @return array<string, mixed>
     */
    public function format(): array
    {
        return [
            'format' => [
                'type' => 'json_schema',
                'name' => 'scenario_response',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'php' => [
                            'type' => ['string', 'null'],
                            'description' => 'The complete generated PHP code.',
                        ],
                        'type' => [
                            'type' => ['string', 'null'],
                            'description' => 'The requested type of code to generate.',
                        ],
                        'question' => [
                            'type' => ['string', 'null'],
                            'description' => 'A question if additional information is required.',
                        ],
                        'classes' => [
                            'type' => 'array',
                            'description' => 'Fully qualified class names of existing classes required by the generated scenario or parameter type.',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'required' => [
                        'php',
                        'type',
                        'question',
                        'classes',
                    ],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }
}
