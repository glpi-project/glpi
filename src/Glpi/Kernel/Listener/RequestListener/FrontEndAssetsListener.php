<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Kernel\Listener\RequestListener;

use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Http\RequestRouterTrait;
use Glpi\Kernel\ListenersPriority;
use Plugin;
use Safe\Exceptions\FileinfoException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function Safe\mime_content_type;
use function Safe\preg_match;

final class FrontEndAssetsListener implements EventSubscriberInterface
{
    use RequestRouterTrait;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $glpi_root,
        array $plugin_directories = GLPI_PLUGINS_DIRECTORIES,
    ) {
        $this->glpi_root = $glpi_root;
        $this->plugin_directories = $plugin_directories;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', ListenersPriority::REQUEST_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $path = $this->normalizePath($request);

        $target_file = $this->getTargetFile($path);

        if (
            $target_file === null
            || $this->isTargetAPhpScript($path)
            || $this->isHiddenFile($path)
        ) {
            return;
        }

        $path_matches = [];
        if (
            preg_match(Plugin::PLUGIN_RESOURCE_PATTERN, $path, $path_matches) === 1
            && Plugin::isPluginLoaded($path_matches['plugin_key']) === false
        ) {
            // Plugin is not loaded, forward to 404 error page.
            throw new NotFoundHttpException(sprintf('Plugin `%s` is not loaded.', $path_matches['plugin_key']));
        }

        // Serve static files if web server is not configured to do it directly.
        $response = new BinaryFileResponse($target_file, 200, [
            'Content-Type' => $this->getMimeType($target_file),
        ]);
        $response->setMaxAge(2_592_000);
        $response->mustRevalidate();

        // Automatically modifies the response to 304 if HTTP Cache headers match
        $response->isNotModified($request);

        // Setting the response will stop the event propagation (see `Symfony\Component\HttpKernel\Event\RequestEvent::setResponse()`).
        // No other request listener will be evaluated.
        $event->setResponse($response);
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
                try {
                    $mime = mime_content_type($target_file);
                } catch (FileinfoException $e) {
                    $mime = 'application/octet-stream';
                }

                return $mime;
        }
    }
}
