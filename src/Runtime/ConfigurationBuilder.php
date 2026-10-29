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

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use Stateforge\Scenario\Ai\Runtime\Exception\NoConfigException;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\ConfigurationFinder;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\XMLParser;
use function filter_var;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_INT;

final class ConfigurationBuilder
{
    public function __construct(
        private ConfigurationFinder $finder,
        private XMLParser $parser,
    ) {
    }

    public function build(): Configuration
    {
        $file = $this->finder->find([ 'scenario-ai.dist.xml', 'scenario-ai.xml' ]);
        if ($file === null) {
            throw new NoConfigException();
        }

        return $this->create(
            $this->parser->parse($file),
        );
    }

    private function create(DOMDocument $document): Configuration
    {
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('/scenario-ai/adapter');
        if ($nodes === false) {
            throw new InvalidArgumentException('Invalid adapter XPath expression.');
        }

        $adapter = $nodes->item(0);
        if (!$adapter instanceof DOMElement) {
            throw new InvalidArgumentException('Adapter configuration not found.');
        }

        /** @var array<string, string> $vars */
        $vars = [];
        $varNodes = $xpath->query('var', $adapter);
        if ($varNodes === false) {
            throw new InvalidArgumentException('Invalid adapter variables XPath expression.');
        }

        foreach ($varNodes as $var) {
            if (!$var instanceof DOMElement) {
                continue;
            }

            $vars[$var->getAttribute('name')] = $var->textContent;
        }

        $class = $adapter->getAttribute('class');
        $path = $adapter->getAttribute('path');

        $adapterConfig = new AdapterConfiguration(
            adapter: $adapter->getAttribute('type'),
            vars: $vars,
            class: $class === '' ? null : $class,
            path: $path === '' ? null : $path,
        );

        $root = $document->documentElement;
        if ($root === null) {
            throw new InvalidArgumentException('XML document has no root element.');
        }

        $projectDoc = $root->getAttribute('projectDoc');
        $allowProjectInformation = $root->getAttribute('allowProjectInformation');
        $maxQuestions = filter_var($root->getAttribute('maxQuestions'), FILTER_VALIDATE_INT);

        if ($maxQuestions === false
            || $maxQuestions < 0) {
            $maxQuestions = 3;
        }

        return new Configuration(
            adapter: $adapterConfig,
            projectDoc: $projectDoc === '' ? null : $projectDoc,
            allowProjectInformation: filter_var($allowProjectInformation, FILTER_VALIDATE_BOOLEAN),
            maxQuestions: $maxQuestions,
        );
    }
}
