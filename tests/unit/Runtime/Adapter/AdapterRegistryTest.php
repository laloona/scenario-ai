<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Adapter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Stateforge\Scenario\Ai\Contract\AiAdapter;
use Stateforge\Scenario\Ai\Runtime\Adapter\AdapterRegistry;
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\AdapterAlreadyRegisteredException;
use Stateforge\Scenario\Ai\Runtime\Exception\Adapter\UnknownAdapterException;

#[CoversClass(AdapterRegistry::class)]
#[UsesClass(UnknownAdapterException::class)]
#[UsesClass(AdapterAlreadyRegisteredException::class)]
#[Group('runtime')]
#[Small]
final class AdapterRegistryTest extends TestCase
{
    public function testReturnsRegisteredAdapterByName(): void
    {
        $registry = new AdapterRegistry();
        $first = self::createStub(AiAdapter::class);
        $second = self::createStub(AiAdapter::class);
        $registry->register('first', $first);
        $registry->register('second', $second);

        self::assertSame($first, $registry->get('first'));
        self::assertSame($second, $registry->get('second'));
    }

    public function testRejectsDuplicateRegistrationWithoutReplacingAdapter(): void
    {
        $registry = new AdapterRegistry();
        $original = self::createStub(AiAdapter::class);
        $registry->register('test', $original);

        try {
            $registry->register('test', self::createStub(AiAdapter::class));
            self::fail('Duplicate registration must fail.');
        } catch (AdapterAlreadyRegisteredException $exception) {
            self::assertSame('Adapter "test" is already registered.', $exception->getMessage());
        }
        self::assertSame($original, $registry->get('test'));
    }

    public function testRejectsUnknownAdapter(): void
    {
        $this->expectException(UnknownAdapterException::class);
        $this->expectExceptionMessage('Unknown adapter "missing".');

        (new AdapterRegistry())->get('missing');
    }
}
