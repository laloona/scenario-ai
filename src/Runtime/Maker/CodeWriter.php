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

use RuntimeException;
use function dirname;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function sprintf;

final readonly class CodeWriter
{
    public function write(string $path, string $code): string
    {
        $directory = dirname($path);
        if (is_dir($directory) === false
            && mkdir($directory, 0775, true) === false
            && is_dir($directory) === false) {
            throw new RuntimeException(
                sprintf('Could not create directory "%s".', $directory),
            );
        }

        $path = $this->resolveAvailablePath($path);
        if (file_put_contents($path, $code) === false) {
            throw new RuntimeException(
                sprintf('Could not write file "%s".', $path),
            );
        }

        return $path;
    }

    private function resolveAvailablePath(string $path): string
    {
        $availablePath = $path . '.ai';
        if (file_exists($availablePath) === false) {
            return $availablePath;
        }

        $counter = 1;
        do {
            $availablePath = sprintf(
                '%s.%d.ai',
                $path,
                $counter++,
            );
        } while (file_exists($availablePath));

        return $availablePath;
    }
}
