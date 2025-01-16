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

use Config;
use DBmysql;
use Glpi\Http\RequestPoliciesTrait;
use Glpi\Kernel\ListenersPriority;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CheckDatabaseStatusListener implements EventSubscriberInterface
{
    use RequestPoliciesTrait;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

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
        /** @var ?DBmysql $DB */
        global $DB;

        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->shouldCheckDbStatus($event->getRequest())) {
            return;
        }

        if (!($DB instanceof DBmysql)) {
            $exception = new \Glpi\Exception\Http\HttpException(500);
            $exception->setMessageToDisplay(
                __('The database configuration file is missing or is corrupted. You have to either restart the install process, or restore this file.')
            );
            if (file_exists($this->projectDir . '/install/install.php')) {
                $exception->setLinkText(__('Go to install page'));
                $exception->setLinkUrl($event->getRequest()->getBasePath() . '/install/install.php');
            }
            throw $exception;
        }

        if (!$DB->connected) {
            $exception = new \Glpi\Exception\Http\HttpException(500);
            $exception->setMessageToDisplay(
                __('The connection to the SQL server could not be established. Please check your configuration.')
            );
            $exception->setLinkText(__('Try again'));
            $exception->setLinkUrl($event->getRequest()->getRequestUri());
            throw $exception;
        }

        if (!Config::isLegacyConfigurationLoaded()) {
            $exception = new \Glpi\Exception\Http\HttpException(500);
            $exception->setMessageToDisplay(
                __('Unable to load the GLPI configuration from the database.')
            );
            $exception->setLinkText(__('Try again'));
            $exception->setLinkUrl($event->getRequest()->getRequestUri());
            throw $exception;
        }
    }
}
