<?php

namespace Glpi\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class LegacyPostRequestActionsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::FINISH_REQUEST => ['onFinishRequest'],
        ];
    }

    public function onFinishRequest(): void
    {
        $this->resetSessionAjaxParam();
        $this->triggerGlobalsDeprecation();
    }

    private function resetSessionAjaxParam(): void
    {
        \Session::resetAjaxParam();
    }

    private function triggerGlobalsDeprecation(): void
    {
        /** @var int|bool|null $AJAX_INCLUDE */
        global $AJAX_INCLUDE;
        if (isset($AJAX_INCLUDE)) {
            \Toolbox::deprecated('Using the global "$AJAX_INCLUDE" variable has been removed. Use "$this->setAjax()" from your controllers instead.', version: "11.0");
        }

        /** @var string|null $SECURITY_STRATEGY */
        global $SECURITY_STRATEGY;
        if (isset($SECURITY_STRATEGY)) {
            \Toolbox::deprecated('Using the global "$SECURITY_STRATEGY" variable has been removed. Use proper Route attributes in your controllers instead.', version: "11.0");
        }
    }
}
