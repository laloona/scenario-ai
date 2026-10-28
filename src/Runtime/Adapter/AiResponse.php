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

final class AiResponse
{
    /**
     * @param list<class-string> $classes
     */
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $php,
        public readonly ?GenerationType $type,
        public readonly ?string $question,
        public readonly array $classes,
    ) {
    }
}
