<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Exception\Validation;

final class AllClassesProvidedException extends ValidatorException
{
    public function __construct()
    {
        parent::__construct(
            <<<MESSAGE
All requested class definitions have already been provided in the current conversation.
Do not request these classes again.
Use the previously provided source code to continue evaluating the request.

Return a valid response containing either:
- a matching existing implementation,
- generated PHP code,
- a request for genuinely missing class definitions,
- or a clarification question if essential information is missing.
MESSAGE
        );
    }
}
