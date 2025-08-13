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

use Config;
use DBmysql;
use Glpi\Controller\InstallController;
use Glpi\Exception\Http\HttpException;
use Glpi\Kernel\KernelListenerTrait;
use Glpi\Kernel\ListenersPriority;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Update;

final class CheckDatabaseStatusListener implements EventSubscriberInterface
{
    use KernelListenerTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', ListenersPriority::REQUEST_LISTENERS_PRIORITIES[self::class]],
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            // Do not check DB status on sub-requests.
            return;
        }

        $request = $event->getRequest();

        if ($this->isControllerAlreadyAssigned($request)) {
            // A controller has already been assigned by a previous listener, do not override it.
            return;
        }

        if ($this->isFrontEndAssetEndpoint($request) || $this->isSymfonyProfilerEndpoint($request)) {
            // These resources should always be available.
            return;
        }

        $path = $request->getPathInfo();
        if (
            // install/update endpoint
            \str_starts_with(\strtolower($path), '/install/')
            // `\Glpi\Controller\ProgressController::check()` route used during install/update process
            || \str_starts_with(\strtolower($path), '/progress/check/')
        ) {
            // DB status should never be checked when the requested endpoint is part of the install/update process.
            return;
        }

        global $DB;

        if (!($DB instanceof DBmysql)) { // @phpstan-ignore instanceof.alwaysTrue (the database may be unavailable at this point)
            // Setting the `_controller` attribute will force Symfony to consider that routing was resolved already.
            // @see `\Symfony\Component\HttpKernel\EventListener\RouterListener::onKernelRequest()`
            $event->getRequest()->attributes->set('_controller', [InstallController::class, 'installRequired']);
            return;
        }

        if (!$DB->connected) {
            $exception = new HttpException(500);
            $exception->setMessageToDisplay(
                __('The connection to the SQL server could not be established. Please check your configuration.')
            );
            $exception->setLinkText(__('Try again'));
            $exception->setLinkUrl($event->getRequest()->getRequestUri());
            throw $exception;
        }

        if (!Config::isLegacyConfigurationLoaded()) {
            $exception = new HttpException(500);
            $exception->setMessageToDisplay(
                __('Unable to load the GLPI configuration from the database.')
            );
            $exception->setLinkText(__('Try again'));
            $exception->setLinkUrl($event->getRequest()->getRequestUri());
            throw $exception;
        }

        if (Update::isUpdateMandatory()) {
            // Setting the `_controller` attribute will force Symfony to consider that routing was resolved already.
            // @see `\Symfony\Component\HttpKernel\EventListener\RouterListener::onKernelRequest()`
            $event->getRequest()->attributes->set('_controller', InstallController::class . '::updateRequired');
            return;
        }
    }
}
