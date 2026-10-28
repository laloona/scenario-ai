<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Instruction;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Stateforge\Scenario\Ai\Runtime\Exception\Instruction\DirectoryNotFoundException;
use function is_dir;
use function sort;
use function strtolower;

final class DocumentationFinder
{
    /**
     * @return list<string>
     */
    public function find(string $path): array
    {
        if (is_dir($path) === false) {
            throw new DirectoryNotFoundException('Documentation directory does not exist: ' . $path);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        );
        $paths = [];
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo
                && $file->isFile() === true
                && strtolower($file->getExtension()) === 'md') {
                $paths[] = $file->getPathname();
            }
        }
        sort($paths);

        return $paths;
    }
}
