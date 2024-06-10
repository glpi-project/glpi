<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class LegacyAssetsListener implements EventSubscriberInterface
{
    use LegacyRouterTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 250],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $response = $this->serveLegacyAssets($request);

        if ($response) {
            $event->setResponse($response);
        }
    }

    private function serveLegacyAssets(Request $request): ?Response
    {
        $glpi_root = dirname(__DIR__, 3);

        if (
            $request->server->get('SCRIPT_NAME') === '/public/index.php'
            && preg_match('/^\/public/', $request->server->get('REQUEST_URI')) !== 1
        ) {
            // When requested URI does not start with '/public' but `$request->server->get('SCRIPT_NAME')` is '/public/index.php',
            // it means that document root is the GLPI root directory, but a rewrite rule redirects the request to the PHP router.
            // This case happen when redirection to PHP router is made by an `.htaccess` file placed in the GLPI root directory,
            // and has to be handled to support shared hosting where it is not possible to change the web server root directory.
            $uri_prefix = '';
        } else {
            // `$request->server->get('SCRIPT_NAME')` corresponds to the script path relative to server document root.
            // -> if server document root is `/public`, then `$request->server->get('SCRIPT_NAME')` will be equal to `/index.php`
            // -> if script is located into a `/glpi-alias` alias directory, then `$request->server->get('SCRIPT_NAME')` will be equal to `/glpi-alias/index.php`
            $uri_prefix = rtrim(str_replace('\\', '/', dirname($request->server->get('SCRIPT_NAME'))), '/');
        }

        $path = preg_replace(
            '/^' . preg_quote($uri_prefix, '/') . '/',
            '',
            parse_url($request->server->get('REQUEST_URI') ?? '/', PHP_URL_PATH)
        );

        if ($this->isPathAllowed($path) === false) {
            return null;
        }

        $target_file = $glpi_root . $path;

        if (!is_file($target_file)) {
            return null;
        }

        if ($this->isTargetAPhpScript($path)) {
            return null;
        }

        // Serve static files if web server is not configured to do it directly.
        $response = new BinaryFileResponse($target_file, 200, [
            'Content-Type' => $this->getMimeType($target_file),
        ]);
        $response->setMaxAge(2_592_000);
        $response->mustRevalidate();

        // Automatically modifies the response to 304 if HTTP Cache headers match
        $response->isNotModified($request);

        return $response;
    }

    private function getMimeType(string $target_file): string
    {
        $extension = \pathinfo($target_file, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'css':
                return 'text/css';
            case 'js':
            case 'vue':
                return 'application/javascript';
            case 'woff':
                return 'font/woff';
            case 'woff2':
                return 'font/woff2';
            default:
                $mime = \mime_content_type($target_file);

                if ($mime === false) {
                    $mime = 'application/octet-stream';
                }

                return $mime;
        }
    }
}
