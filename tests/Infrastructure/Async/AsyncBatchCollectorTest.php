<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Async;

use App\Infrastructure\Async\AsyncBatchCollector;
use App\Infrastructure\Async\AsyncBatchCollectorInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AsyncBatchCollectorTest extends TestCase
{
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    private function createCollector(): AsyncBatchCollectorInterface
    {
        return new AsyncBatchCollector($this->logger);
    }

    public function testImplementsInterface(): void
    {
        $collector = $this->createCollector();

        self::assertInstanceOf(AsyncBatchCollectorInterface::class, $collector);
    }

    public function testAddSettleAndGetBasicFlow(): void
    {
        $collector = $this->createCollector();

        $collector->add('group1', 'key1', fn () => new FulfilledPromise('value1'));
        $collector->settle('group1');

        self::assertSame('value1', $collector->get('group1', 'key1'));
    }

    public function testSettleResolvesMultiplePromisesInSameGroup(): void
    {
        $collector = $this->createCollector();

        $collector->add('group1', 'a', fn () => new FulfilledPromise('val_a'));
        $collector->add('group1', 'b', fn () => new FulfilledPromise('val_b'));
        $collector->add('group1', 'c', fn () => new FulfilledPromise('val_c'));
        $collector->settle('group1');

        self::assertSame('val_a', $collector->get('group1', 'a'));
        self::assertSame('val_b', $collector->get('group1', 'b'));
        self::assertSame('val_c', $collector->get('group1', 'c'));
    }

    public function testDedupByKeySameKeyNotOverwritten(): void
    {
        $callCount = 0;
        $collector = $this->createCollector();

        $collector->add('group1', 'key1', function () use (&$callCount) {
            ++$callCount;

            return new FulfilledPromise('first');
        });
        $collector->add('group1', 'key1', function () use (&$callCount) {
            ++$callCount;

            return new FulfilledPromise('second');
        });
        $collector->settle('group1');

        self::assertSame(1, $callCount);
        self::assertSame('first', $collector->get('group1', 'key1'));
    }

    public function testRejectedPromiseExcludedFromResults(): void
    {
        $this->logger->expects(self::once())
            ->method('warning');

        $collector = $this->createCollector();

        $collector->add('group1', 'good', fn () => new FulfilledPromise('ok'));
        $collector->add('group1', 'bad', fn () => new RejectedPromise(new \RuntimeException('fail')));
        $collector->settle('group1');

        self::assertTrue($collector->has('group1', 'good'));
        self::assertFalse($collector->has('group1', 'bad'));
    }

    public function testGetThrowsExceptionForMissingKey(): void
    {
        $collector = $this->createCollector();

        $collector->add('group1', 'good', fn () => new FulfilledPromise('ok'));
        $collector->settle('group1');

        $this->expectException(\RuntimeException::class);
        $collector->get('group1', 'nonexistent');
    }

    public function testGetThrowsExceptionForUnsettledGroup(): void
    {
        $collector = $this->createCollector();

        $collector->add('group1', 'key1', fn () => new FulfilledPromise('val'));

        $this->expectException(\RuntimeException::class);
        $collector->get('group1', 'key1');
    }

    public function testGetGroupReturnsOnlyFulfilled(): void
    {
        $this->logger->expects(self::once())
            ->method('warning');

        $collector = $this->createCollector();

        $collector->add('group1', 'a', fn () => new FulfilledPromise('val_a'));
        $collector->add('group1', 'b', fn () => new RejectedPromise(new \RuntimeException('fail')));
        $collector->add('group1', 'c', fn () => new FulfilledPromise('val_c'));
        $collector->settle('group1');

        $results = $collector->getGroup('group1');

        self::assertCount(2, $results);
        self::assertSame('val_a', $results['a']);
        self::assertSame('val_c', $results['c']);
        self::assertArrayNotHasKey('b', $results);
    }

    public function testChainingSettleGroupAThenPopulateAndSettleGroupB(): void
    {
        $collector = $this->createCollector();

        $collector->add('phase1', 'data', fn () => new FulfilledPromise(42));
        $collector->settle('phase1');

        $valueFromPhase1 = $collector->get('phase1', 'data');
        $collector->add('phase2', 'derived', fn () => new FulfilledPromise($valueFromPhase1 * 2));
        $collector->settle('phase2');

        self::assertSame(84, $collector->get('phase2', 'derived'));
    }

    public function testSettleEmptyGroupDoesNotFail(): void
    {
        $collector = $this->createCollector();

        $collector->settle('empty_group');

        self::assertSame([], $collector->getGroup('empty_group'));
    }

    public function testSettleCleansCallablesAllowingNewAddsToSameGroup(): void
    {
        $collector = $this->createCollector();

        $collector->add('group1', 'first', fn () => new FulfilledPromise('val1'));
        $collector->settle('group1');

        $collector->add('group1', 'second', fn () => new FulfilledPromise('val2'));
        $collector->settle('group1');

        self::assertSame('val2', $collector->get('group1', 'second'));
    }

    public function testMultipleGroupsResolveIndependently(): void
    {
        $collector = $this->createCollector();

        $collector->add('tags', 'tag_1', fn () => new FulfilledPromise('tag_value'));
        $collector->add('journalists', 'j_1', fn () => new FulfilledPromise('journalist_value'));

        $collector->settle('tags');

        self::assertTrue($collector->has('tags', 'tag_1'));
        self::assertFalse($collector->has('journalists', 'j_1'));

        $collector->settle('journalists');

        self::assertTrue($collector->has('journalists', 'j_1'));
    }

    public function testHasReturnsFalseForNonExistentGroup(): void
    {
        $collector = $this->createCollector();

        self::assertFalse($collector->has('nonexistent', 'key'));
    }

    public function testGetGroupReturnsEmptyArrayForNonExistentGroup(): void
    {
        $collector = $this->createCollector();

        self::assertSame([], $collector->getGroup('nonexistent'));
    }

    public function testRejectedPromiseIsLoggedAsWarning(): void
    {
        $exception = new \RuntimeException('Connection timeout');

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                self::stringContains('AsyncBatchCollector'),
                self::callback(fn (array $ctx) => $ctx['group'] === 'mygroup'
                    && $ctx['key'] === 'failed_key'
                    && str_contains($ctx['error'], 'Connection timeout'))
            );

        $collector = $this->createCollector();

        $collector->add('mygroup', 'failed_key', fn () => new RejectedPromise($exception));
        $collector->settle('mygroup');
    }

    public function testSettlePreservesResultsFromPreviousSettle(): void
    {
        $collector = $this->createCollector();

        $collector->add('group1', 'a', fn () => new FulfilledPromise('val_a'));
        $collector->settle('group1');

        $collector->add('group2', 'b', fn () => new FulfilledPromise('val_b'));
        $collector->settle('group2');

        self::assertSame('val_a', $collector->get('group1', 'a'));
        self::assertSame('val_b', $collector->get('group2', 'b'));
    }
}
