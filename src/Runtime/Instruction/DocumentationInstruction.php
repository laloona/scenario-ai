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

use Stateforge\Scenario\Ai\Runtime\Exception\Instruction\ContentNotReadable;
use function basename;
use function file_get_contents;
use function implode;
use const PHP_EOL;

final class DocumentationInstruction implements Instruction
{
    public function __construct(private readonly string $path)
    {
    }

    public function instruct(): string
    {
        $paths = (new DocumentationFinder())->find($this->path);

        $contents = [];
        foreach ($paths as $path) {
            $content = file_get_contents($path);
            if ($content === false) {
                throw new ContentNotReadable('Unable to read documentation file: ' . $path);
            }
            $contents[] = '[' . basename($path) . ']' . PHP_EOL . $content;
        }

        return 'Documentation' . PHP_EOL . PHP_EOL . implode(PHP_EOL . PHP_EOL, $contents);
    }
}
