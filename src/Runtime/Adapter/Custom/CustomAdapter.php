<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Adapter\Custom;

use InvalidArgumentException;
use Stateforge\Scenario\Ai\Contract\AiAdapter;
use Stateforge\Scenario\Ai\Contract\AiClient;
use Stateforge\Scenario\Ai\Runtime\Adapter\ConfigBuilder;
use Stateforge\Scenario\Ai\Runtime\AdapterConfiguration;
use Stateforge\Scenario\Ai\Runtime\Exception\AdapterClassDoesNotExistException;
use Stateforge\Scenario\Ai\Runtime\Exception\MissingAdapterClassException;
use Stateforge\Scenario\Ai\Runtime\Exception\MissingAdapterPathException;
use Stateforge\Scenario\Ai\Runtime\Instruction\Instruction;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\ClassFinder;
use function class_exists;
use function is_a;
use function sprintf;
use const DIRECTORY_SEPARATOR;

final class CustomAdapter implements AiAdapter
{
    public function client(
        ConfigBuilder $configBuilder,
        AdapterConfiguration $adapterConfiguration,
        Instruction $instruction,
    ): AiClient {
        if ($adapterConfiguration->path === null) {
            throw new MissingAdapterPathException();
        }

        (new ClassFinder())->findClassesInDirectory(Application::getRootDir() . DIRECTORY_SEPARATOR  . $adapterConfiguration->path);

        $class = $adapterConfiguration->class;
        if ($class === null) {
            throw new MissingAdapterClassException();
        }

        if (class_exists($class, false) === false) {
            throw new AdapterClassDoesNotExistException($class);
        }

        if (is_a($class, AiAdapter::class, true) === false) {
            throw new InvalidArgumentException(
                sprintf('Class "%s" must implement AiAdapter.', $class),
            );
        }

        return (new $class())->client(
            $configBuilder,
            $adapterConfiguration,
            $instruction,
        );
    }
}
