<?php

/**
 * @copyright
 */

namespace App\EventHandler;

use Ec\Cqrs\Application\Service\CqrsFactory;
use Ec\Editorial\Domain\Model\EventEditorial;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @author Jose Guillermo Moreu Peso <jgmoreu@ext.elconfidencial.com>
 */
#[AsMessageHandler]
class PurgeEditorialHandler
{
    /** @var string */
    private const WARMUP_MESSENGER = 'warmup::messenger';
    private CqrsFactory $cqrsFactory;
    private MessageBusInterface $messageBus;
    private UrlGeneratorInterface $router;

    private string $hostname;

    public function __construct(
        CqrsFactory $cqrsFactory,
        MessageBusInterface $messageBus,
        UrlGeneratorInterface $router,
        string $hostname,
    ) {
        $this->cqrsFactory = $cqrsFactory;
        $this->messageBus = $messageBus;
        $this->router = $router;
        $this->hostname = $hostname;
    }

    public function __invoke(EventEditorial $eventEditorial): void
    {
        $id = $eventEditorial->id()->id();
        $parameters = [
            'id' => $id,
            'hostname' => $this->hostname,
        ];
        $editorialUrl = $this->generateRouteUrl(
            'getEditorialById',
            $parameters,
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $message = $this->cqrsFactory->buildCommandNotification('editorial:cache-delete');
        $message->addParameter('-f', 'findEditorialById')
            ->addParameter('-p', $id);

        $messageLast = $this->cqrsFactory->buildCommandNotificationOnComplete('cdn:purge', 'cdn::purge::messenger');
        $messageLast->addParameter('-u', $editorialUrl);
        $message->addNotificationOnComplete($messageLast);

        $stamp = new AmqpStamp(self::WARMUP_MESSENGER);
        $this->messageBus->dispatch($message, [$stamp]);
    }

    /**
     * @param array<string, string> $parameters
     */
    private function generateRouteUrl(
        string $routeName,
        array $parameters = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
    ): string {
        $result = $this->router->generate($routeName, $parameters, $referenceType);

        /** @var string $url */
        $url = preg_replace('/^http:/', 'https:', $result);

        return $url;
    }
}
