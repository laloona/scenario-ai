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
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\AstBuilder;

final class CodeValidator implements Validator
{
    public function __construct(
        private readonly string $scenarioClass,
        private readonly string $scenarioAttribute,
        private readonly string $parameterClass,
        private readonly string $parameterAttribute,
    ) {
    }

    public function validate(AiResponse $response): void
    {
        if ($response->php === null
            || $response->type === null) {
            return;
        }

        (new SyntaxValidator())->validate($response->php);

        match($response->type) {
            GenerationType::Scenario => $this->validateType($this->scenarioClass, $this->scenarioAttribute, $response->php),
            GenerationType::Parameter => $this->validateType($this->parameterClass, $this->parameterAttribute, $response->php),
        };
    }

    private function validateType(string $class, string $attribute, string $generatedCode): void
    {
        $ast = (new AstBuilder())->build($generatedCode);

        (new InheritanceValidator($class))->validate($ast);
        (new AttributeValidator(
            new AttributeFinder(),
            $attribute,
        ))->validate($ast);
    }
}
