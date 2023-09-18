<?php declare(strict_types=1);

namespace Palmyr\Console\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DebugHandlerListener implements EventSubscriberInterface
{

    protected ErrorHandler $errorHandler;

    protected LoggerInterface $logger;

    public function __construct(
        ErrorHandler $errorHandler,
        LoggerInterface $logger
    )
    {
        $this->errorHandler = $errorHandler;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['configure', 0],
        ];
    }

    public function configure(ConsoleEvent $event): void
    {
        $this->errorHandler->setDefaultLogger($this->logger);
    }
}