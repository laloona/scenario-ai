<?php declare(strict_types=1);

/*
 * This file is part of Stateforge\Scenario\Ai package.
 *
 * (c) Christina Koenig <christina.koenig@looriva.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stateforge\Scenario\Ai\Tests\Unit\Runtime\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Stateforge\Scenario\Ai\Runtime\Adapter\AiResponse;
use Stateforge\Scenario\Ai\Runtime\Validation\Validator;
use Stateforge\Scenario\Ai\Runtime\Validation\Validators;

#[CoversClass(Validators::class)]
#[UsesClass(AiResponse::class)]
#[Group('runtime')]
#[Small]
final class ValidatorsTest extends TestCase
{
    public function testEmptyCollectionAcceptsResponse(): void
    {
        $validators = new Validators();
        $response = new AiResponse(null, null, null, null, []);
        $validators->validate($response);

        self::assertNull($response->php);
    }

    public function testCallsEveryValidatorInInsertionOrderWithSameResponse(): void
    {
        $response = new AiResponse('id', null, null, null, []);
        $calls = [];
        $first = $this->createMock(Validator::class);
        $first->expects(self::once())->method('validate')->with($response)
            ->willReturnCallback(function () use (&$calls): void {
                $calls[] = 'first';
            });
        $second = $this->createMock(Validator::class);
        $second->expects(self::once())->method('validate')->with($response)
            ->willReturnCallback(function () use (&$calls): void {
                $calls[] = 'second';
            });
        $validators = new Validators();
        $validators->add($first);
        $validators->add($second);

        $validators->validate($response);

        self::assertSame(['first', 'second'], $calls);
    }

    public function testStopsAfterFirstValidationFailure(): void
    {
        $response = new AiResponse(null, null, null, null, []);
        $first = $this->createMock(Validator::class);
        $first->expects(self::once())->method('validate')->with($response)
            ->willThrowException(new RuntimeException('Invalid response'));
        $second = $this->createMock(Validator::class);
        $second->expects(self::never())->method('validate');
        $validators = new Validators();
        $validators->add($first);
        $validators->add($second);
        $this->expectException(RuntimeException::class);

        $validators->validate($response);
    }
}
