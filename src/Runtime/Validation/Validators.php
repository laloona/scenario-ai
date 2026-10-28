<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Validation;

use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;

final class Validators implements Validator
{
    /**
     * @var list<Validator>
     */
    private array $validators = [];

    public function add(Validator $validator): void
    {
        $this->validators[] = $validator;
    }

    public function validate(AiResponse $response): void
    {
        foreach ($this->validators as $validator) {
            $validator->validate($response);
        }
    }
}
