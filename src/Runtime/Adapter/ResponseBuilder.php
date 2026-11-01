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

use Stateforge\Scenario\Ai\Runtime\Exception\MissingResponseElementException;
use Stateforge\Scenario\Ai\Runtime\Exception\WrongResponseTypeException;
use function array_key_exists;
use function array_unique;
use function array_values;
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
        $response['type'] = $this->extractString($response, 'type');
        if ($response['type'] !== null) {
            $type = GenerationType::tryFrom($response['type']);
            if ($type === null) {
                throw new WrongResponseTypeException($response['type'], GenerationType::class . ' for "type"');
            }

            $response['type'] = $type;
        }

        $response['php'] = $this->extractString($response, 'php');
        $response['question'] = $this->extractString($response, 'question');

        if (array_key_exists('classes', $response) === false) {
            throw new MissingResponseElementException('classes');
        }

        if (is_array($response['classes']) === false) {
            throw new WrongResponseTypeException($response['classes'], 'array for "classes"');
        }

        foreach ($response['classes'] as $class) {
            if (is_string($class) === false) {
                throw new WrongResponseTypeException($class, 'string in [classes]');
            }
        }

        $response['match'] = $this->extractString($response, 'match');

        return new AiResponse(
            $response['id'],
            $response['php'],
            $response['type'],
            $response['question'],
            array_values(array_unique($response['classes'])),
            $response['match'],
        );
    }

    /**
     * @param array<string, mixed> $response
     */
    private function extractString(array $response, string $name): ?string
    {
        if (array_key_exists($name, $response) === false) {
            throw new MissingResponseElementException($name);
        }

        return $this->getString($response[$name], $name);
    }

    private function getString(mixed $value, string $name): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_string($value) === true
            ? $value
            : throw new WrongResponseTypeException($value, 'string for "' . $name . '"');
    }
}
