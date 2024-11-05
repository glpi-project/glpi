<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Controller\LegacyFileLoadController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LegacyRouterListener implements EventSubscriberInterface
{
    use LegacyRouterTrait;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $glpi_root,
        array $plugin_directories = PLUGINS_DIRECTORIES,
    ) {
        $this->glpi_root = $glpi_root;
        $this->plugin_directories = $plugin_directories;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', ListenersPriority::LEGACY_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            // Legacy endpoints are not supposed to be executed in sub-requests.
            return;
        }

        $request = $event->getRequest();

        if (
            $request->attributes->get('_controller') !== null
            || $event->getResponse() !== null
        ) {
            // A controller or a response has already been defined for this request, do not override them.
            return;
        }

        [$uri_prefix, $path] = $this->extractPathAndPrefix($request);

        $target_file = $this->getTargetFile($path);

        if (
            $target_file === $this->glpi_root . '/public/index.php' // prevent infinite loop
            || $target_file === null
            || $this->isHiddenFile($path)
            || !$this->isTargetAPhpScript($path)
        ) {
            // Let the previous router do the trick, it's fine.
            return;
        }

        // Ensure `getcwd()` and inclusion path is based on requested file FS location.
        // use `@` to silence errors on unit tests (`chdir` does not work on streamed mocked dir)
        @chdir(dirname($target_file));

        // (legacy) Redefine some $_SERVER variables to have same values whenever scripts are called directly
        // or through current router.
        $target_path = $uri_prefix . $path;
        $_SERVER['PHP_SELF']  = $target_path;

        // New server overrides:
        $request->server->set('PHP_SELF', $target_path);

        /**
         * This will force Symfony to consider that routing was resolved already.
         * @see \Symfony\Component\HttpKernel\EventListener\RouterListener::onKernelRequest
         */
        $request->attributes->set('_controller', LegacyFileLoadController::class);
        $request->attributes->set(LegacyFileLoadController::REQUEST_FILE_KEY, $target_file);
    }
}
