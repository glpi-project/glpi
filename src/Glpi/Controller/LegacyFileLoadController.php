<?php

namespace Glpi\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LegacyFileLoadController extends AbstractController
{
    public const REQUEST_FILE_KEY = '_glpi_file_to_load';

    private ?Request $request = null;

    public function __invoke(Request $request): StreamedResponse
    {
        $this->request = $request;

        $target_file = $request->attributes->getString(self::REQUEST_FILE_KEY);

        if (!$target_file) {
            throw new \RuntimeException('Cannot load legacy controller without specifying a file to load.');
        }

        $callback = fn () => require $target_file;

        return new StreamedResponse($callback->bindTo($this, self::class));
    }

    protected function setAjax(): void
    {
        $this->getRequest()->attributes->set('_glpi_ajax', true);

        \Session::setAjax();
        \Html::setAjax();
    }

    private function getRequest(): ?Request
    {
        if (!$this->request) {
            throw new \RuntimeException(\sprintf(
                'Could not find Request in "%s" controller. Did you forget to call "%s"?',
                self::class,
                '__invoke',
            ));
        }

        return $this->request;
    }
}
