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

namespace Glpi\Http\Listener;

use Glpi\Controller\MaintenanceController;
use Glpi\Http\RequestPoliciesTrait;
use Glpi\Kernel\ListenersPriority;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class CheckMaintenanceListener implements EventSubscriberInterface
{
    use RequestPoliciesTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', ListenersPriority::REQUEST_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (
            $this->isFrontEndAssetEndpoint($event->getRequest())
            || $this->isSymfonyProfilerEndpoint($event->getRequest())
        ) {
            return;
        }

        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // Check maintenance mode
        if (!isset($CFG_GLPI["maintenance_mode"]) || !$CFG_GLPI["maintenance_mode"]) {
            return;
        }

        if ($event->getRequest()->query->get('skipMaintenance')) {
            $_SESSION["glpiskipMaintenance"] = 1;
            return;
        }

        if (isset($_SESSION["glpiskipMaintenance"]) && $_SESSION["glpiskipMaintenance"]) {
            return;
        }

        $event->getRequest()->attributes->set('_controller', MaintenanceController::class);
    }
}
