<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Runtime\Adapter;

use Stateforge\Scenario\Ai\Contract\Instruction;
use Stateforge\Scenario\Ai\Runtime\AdapterMode;
use function dirname;
use function file_put_contents;
use function implode;
use function is_dir;
use function mkdir;
use const DIRECTORY_SEPARATOR;

final class FileBuilder
{
    public function __construct(
        private readonly Instruction $instruction,
    ) {
    }

    /** @param list<string> $path */
    public function build(array $path): ?string
    {
        $file = implode(DIRECTORY_SEPARATOR, $path);
        if ($file === '') {
            return null;
        }

        $directory = dirname($file);
        if (is_dir($directory) === false) {
            mkdir($directory, 0775, true);

        }

        $result = file_put_contents(
            $file,
            $this->instruction->instruct(AdapterMode::Skill),
        );

        if ($result === false) {
            return null;
        }

        return $file;
    }
}
