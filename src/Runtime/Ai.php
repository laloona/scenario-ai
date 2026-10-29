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

use Stateforge\Scenario\Ai\Contract\AiContext;
use Stateforge\Scenario\Ai\Runtime\Adapter\AdapterRegistry;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiConversation;
use Stateforge\Scenario\Ai\Runtime\Adapter\Claude\ClaudeAiAdapter;
use Stateforge\Scenario\Ai\Runtime\Adapter\OpenAi\OpenAiAdapter;
use Stateforge\Scenario\Core\Runtime\Application;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\ConfigurationFinder;
use Stateforge\Scenario\Core\Runtime\Application\Configuration\XMLParser;
use const DIRECTORY_SEPARATOR;

final class Ai
{
    private static ?AdapterRegistry $registry = null;

    private static ?Configuration $config = null;

    private static function registry(): AdapterRegistry
    {
        if (self::$registry === null) {
            self::$registry = new AdapterRegistry();
            self::$registry->register('claude', new ClaudeAiAdapter());
            self::$registry->register('openai', new OpenAiAdapter());
        }

        return self::$registry;
    }

    public static function config(): Configuration
    {
        if (self::$config === null) {
            $vendorPath = Application::getRootDir() . DIRECTORY_SEPARATOR .
                'vendor' . DIRECTORY_SEPARATOR .
                'stateforge' . DIRECTORY_SEPARATOR .
                'scenario-ai' . DIRECTORY_SEPARATOR .
                'xsd' . DIRECTORY_SEPARATOR . 'scenario-ai.xsd';

            self::$config = (new ConfigurationBuilder(
                new ConfigurationFinder(),
                new XMLParser($vendorPath),
            ))->build();
        }

        return self::$config;
    }

    public static function start(?AiContext $context): AiConversation
    {
        return new AiConversation($context ?? new Context(), self::config(), self::registry());
    }
}
