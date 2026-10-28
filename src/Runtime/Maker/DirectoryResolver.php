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
use Stateforge\Scenario\Core\Runtime\NamespaceResolver;
use function array_values;
use function count;
use function rtrim;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;
use const DIRECTORY_SEPARATOR;

final class DirectoryResolver
{
    /**
     * @param list<string> $directories
     */
    public function __construct(
        private array $directories,
        private string $baseNamespace,
    ) {
        $this->directories = array_values($directories);
    }

    public function resolve(string $namespace): string
    {
        if (count($this->directories) === 0) {
            throw new RuntimeException('No directories configured.');
        }

        $namespace = trim($namespace, '\\');
        $baseNamespace = trim($this->baseNamespace, '\\');

        $selectedDirectory = $this->directories[0];
        $selectedNamespace = null;
        foreach ($this->directories as $directory) {
            $directoryNamespace = trim(
                $baseNamespace . '\\' . (new NamespaceResolver())->toNamespace($directory),
                '\\',
            );

            if ($namespace !== $directoryNamespace
                && str_starts_with($namespace, $directoryNamespace . '\\') === false) {
                continue;
            }

            if ($selectedNamespace === null
                || strlen($directoryNamespace) > strlen($selectedNamespace)) {
                $selectedDirectory = $directory;
                $selectedNamespace = $directoryNamespace;
            }
        }

        // Fallback: first configured directory.
        if ($selectedNamespace === null) {
            return $selectedDirectory;
        }

        if ($namespace === $selectedNamespace) {
            return $selectedDirectory;
        }

        $relativeNamespace = substr(
            $namespace,
            strlen($selectedNamespace) + 1,
        );

        return rtrim($selectedDirectory, '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace('\\', DIRECTORY_SEPARATOR, $relativeNamespace);
    }
}
