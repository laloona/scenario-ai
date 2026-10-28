<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Maker;

use LogicException;
use Stateforge\Scenario\Ai\Runtime\Adapter\GenerationType;
use Stateforge\Scenario\Ai\Runtime\AstBuilder;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\Value\SuiteValue;
use function array_map;
use function array_values;
use function rtrim;
use const DIRECTORY_SEPARATOR;

final readonly class CodeMaker
{
    public function make(
        GenerationType $type,
        string $code,
    ): string {
        $ast = (new AstBuilder())->build($code);

        $config = Application::config();
        if ($config === null) {
            throw new LogicException('Scenario configuration is not initialized.');
        }

        $resolver = match ($type) {
            GenerationType::Scenario => (new DirectoryResolver(array_values(array_map(
                static fn (SuiteValue $suite): string => $suite->directory,
                $config->getSuites(),
            )), 'Stateforge\\Suite')),
            GenerationType::Parameter => (new DirectoryResolver([ $config->getParameterDirectory() ], 'Stateforge\\Suite')),
        };

        $path = (new CodeWriter())->write(
            rtrim($resolver->resolve($ast->getNamespace()), '/\\')
            . DIRECTORY_SEPARATOR
            . $ast->getClassName()
            . '.php',
            $code,
        );

        return $path;
    }
}
