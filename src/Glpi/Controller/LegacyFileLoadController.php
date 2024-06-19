<?php

namespace Glpi\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LegacyFileLoadController extends AbstractController
{
    public const REQUEST_FILE_KEY = '_glpi_file_to_load';

    public function __invoke(Request $request): StreamedResponse
    {
        $target_file = $request->attributes->getString(self::REQUEST_FILE_KEY);

        if (!$target_file) {
            throw new \RuntimeException('Cannot load legacy controller without specifying a file to load.');
        }

        $callback = fn () => require $target_file;

        return new StreamedResponse($callback->bindTo($this, self::class));
    }
}
