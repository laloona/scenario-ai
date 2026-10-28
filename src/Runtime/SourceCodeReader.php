<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime;

use ReflectionClass;
use Stateforge\Scenario\Ai\Runtime\Exception\ClassFileDoesNotExistException;
use function file_get_contents;
use const PHP_EOL;

final class SourceCodeReader
{
    /**
     * @param list<class-string> $classNames
     */
    public function read(array $classNames): string
    {
        $source = PHP_EOL;
        foreach ($classNames as $className) {
            $file = (new ReflectionClass($className))->getFileName();
            if ($file === false) {
                throw new ClassFileDoesNotExistException($className);
            }

            $source .= file_get_contents($file) . PHP_EOL;
        }

        return $source;
    }
}
