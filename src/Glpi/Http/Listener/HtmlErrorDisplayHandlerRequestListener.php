<?php

namespace Glpi\Http\Listener;

use Glpi\Error\ErrorDisplayHandler\HtmlErrorDisplayHandler;
use Glpi\Kernel\ListenersPriority;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class HtmlErrorDisplayHandlerRequestListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => ['onRequest', ListenersPriority::REQUEST_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        HtmlErrorDisplayHandler::setCurrentRequest($event->getRequest());
    }
}
