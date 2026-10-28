<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Prompt;

use function array_values;
use function ltrim;
use function preg_match_all;
use function strtolower;

final class ClassNameParser
{
    /**
     * @return list<string>
     */
    public function parse(string $prompt): array
    {
        preg_match_all(
            '/\\\\?[a-zA-Z_][a-zA-Z0-9_]*(?:\\\\[a-zA-Z_][a-zA-Z0-9_]*)+/',
            $prompt,
            $matches,
        );

        $classes = [];
        foreach ($matches[0] as $match) {
            $name = ltrim($match, '\\');
            $key = strtolower($name);
            if (isset($classes[$key]) === false) {
                $classes[$key] = $name;
            }
        }

        return array_values($classes);
    }
}
