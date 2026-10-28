<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Exception;

use function sprintf;

final class AttributeValidatorException extends ValidatorException
{
    public function __construct(string $attribute)
    {
        parent::__construct(
            sprintf(
                'Missing required attribute "%s".',
                $attribute,
            ),
        );
    }
}
