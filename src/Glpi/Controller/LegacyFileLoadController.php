<?php

namespace Glpi\Controller;

use Glpi\Http\Firewall;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegacyFileLoadController extends AbstractController
{
    public const REQUEST_FILE_KEY = '_glpi_file_to_load';

    private readonly Request $request;

    public function __construct(
        private readonly Firewall $firewall,
    ) {
    }


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

    private function applySecurityStrategy(string $strategy): void
    {
        $this->firewall->applyStrategy($this->request->server->get('PHP_SELF'), $strategy);
    }
}
