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

use Glpi\Controller\LegacyFileLoadController;
use Glpi\Exception\Http\NotFoundHttpException;
use Glpi\Http\RequestRouterTrait;
use Glpi\Kernel\KernelListenerTrait;
use Glpi\Kernel\ListenersPriority;
use Plugin;
use Safe\Exceptions\DirException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function Safe\chdir;
use function Safe\preg_match;

final class LegacyRouterListener implements EventSubscriberInterface
{
    use RequestRouterTrait;
    use KernelListenerTrait;

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

        if ($this->isControllerAlreadyAssigned($request)) {
            // A controller has already been assigned by a previous listener, do not override it.
            return;
        }

        $path = $this->normalizePath($request);

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

        $path_matches = [];
        if (
            preg_match(Plugin::PLUGIN_RESOURCE_PATTERN, $path, $path_matches) === 1
            && Plugin::isPluginLoaded($path_matches['plugin_key']) === false
        ) {
            // Plugin is not loaded, forward to 404 error page.
            throw new NotFoundHttpException(sprintf('Plugin `%s` is not loaded.', $path_matches['plugin_key']));
        }

        // Ensure `getcwd()` and inclusion path is based on requested file FS location.
        try {
            // use `@` to silence errors on unit tests (`chdir` does not work on streamed mocked dir)
            @chdir(dirname($target_file));
        } catch (DirException $e) {
            //no error
        }

        // Setting the `_controller` attribute will force Symfony to consider that routing was resolved already.
        // @see `\Symfony\Component\HttpKernel\EventListener\RouterListener::onKernelRequest()`
        $request->attributes->set('_controller', LegacyFileLoadController::class);
        $request->attributes->set(LegacyFileLoadController::REQUEST_FILE_KEY, $target_file);
    }
}
