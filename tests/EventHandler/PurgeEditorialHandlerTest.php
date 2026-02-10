<?php

/**
 * @copyright
 */

namespace App\Tests\EventHandler;

use App\EventHandler\PurgeEditorialHandler;
use Ec\Cqrs\Application\Service\CqrsFactory;
use Ec\Cqrs\Messages\CommandNotification;
use Ec\Cqrs\Messages\CommandNotificationOnComplete;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\EventEditorial;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Jose Guillermo Moreu Peso <jgmoreu@ext.elconfidencial.com>
 */
class PurgeEditorialHandlerTest extends TestCase
{
    /** @var string */
    private const WARMUP_MESSENGER = 'warmup::messenger';
    /** @var MockObject|CqrsFactory */
    private MockObject|CqrsFactory $cqrsFactoryMock;
    /** @var MockObject|MessageBusInterface */
    private MockObject|MessageBusInterface $messageBusMock;

    /** @var MockObject|UrlGeneratorInterface */
    private UrlGeneratorInterface|MockObject $routerMock;
    private PurgeEditorialHandler $purgeEditorialHandler;

    protected function setUp(): void
    {
        $this->cqrsFactoryMock = $this->createMock(CqrsFactory::class);
        $this->messageBusMock = $this->createMock(MessageBusInterface::class);
        $this->routerMock = $this->createMock(UrlGeneratorInterface::class);

        $this->purgeEditorialHandler = new PurgeEditorialHandler(
            $this->cqrsFactoryMock,
            $this->messageBusMock,
            $this->routerMock,
            'hostname'
        );
    }

    #[Test]
    public function invokeShouldDispatchMessageToWarmup(): void
    {
        $id = 'id';
        $snaapiUrl = "https://snaapi.url/editorials/$id";

        $this->routerMock->expects(self::once())
            ->method('generate')
            ->with('getEditorialById', ['id' => $id, 'hostname' => 'hostname'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($snaapiUrl);

        $editorialId = $this->createMock(EditorialId::class);
        $editorialId->method('id')
            ->willReturn($id);

        $eventEditorial = $this->createMock(EventEditorial::class);
        $eventEditorial->method('id')
            ->willReturn($editorialId);

        $commandNotificationMock = $this->createMock(CommandNotification::class);
        $commandNotificationOnCompleteMock = $this->createMock(CommandNotificationOnComplete::class);
        $commandNotificationOnCompleteMock->expects(static::once())
            ->method('addParameter')
            ->with('-u', $snaapiUrl)
            ->willReturnSelf();

        $this->cqrsFactoryMock->method('buildCommandNotification')
            ->willReturn($commandNotificationMock);
        $this->cqrsFactoryMock->method('buildCommandNotificationOnComplete')
            ->willReturn($commandNotificationOnCompleteMock);

        $callArgumentsAddParameter = [];
        $commandNotificationMock->expects($this->exactly(2))
            ->method('addParameter')
            ->willReturnCallback(
                function (string $name, string $value) use (&$callArgumentsAddParameter, $commandNotificationMock) {
                    $callArgumentsAddParameter[] = [$name, $value];

                    return $commandNotificationMock;
                }
            );

        $expectedAddParameter = [
            ['-f', 'findEditorialById'],
            ['-p', $id],
        ];

        $commandNotificationMock->expects(static::once())
            ->method('addNotificationOnComplete')
            ->with($commandNotificationOnCompleteMock);

        $stubEnvelope = new Envelope(new \stdClass());
        $this->messageBusMock->expects(static::once())
            ->method('dispatch')
            ->with(
                $commandNotificationMock,
                static::equalTo([new AmqpStamp(self::WARMUP_MESSENGER)])
            )
            ->willReturn($stubEnvelope);

        $this->purgeEditorialHandler->__invoke($eventEditorial);

        static::assertSame($expectedAddParameter, $callArgumentsAddParameter);
    }
}
