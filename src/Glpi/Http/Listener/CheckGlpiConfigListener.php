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

namespace Glpi\Http\Listener;

use Config;
use DBConnection;
use DBmysql;
use Glpi\Config\LegacyConfigProviderListener;
use Glpi\Http\Error\DisplayGlpiMisconfiguredPage;
use Glpi\Http\ListenersPriority;
use Session;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CheckGlpiConfigListener implements EventSubscriberInterface
{
    private static bool $skip_db_checks = false;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    public static function skipDbChecks(): bool
    {
        return self::$skip_db_checks;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', ListenersPriority::REQUEST_LISTENERS_PRIORITIES[self::class]],
                ['onAfterLegacyConfigurators', -10 + ListenersPriority::REQUEST_LISTENERS_PRIORITIES[LegacyConfigProviderListener::class]]
            ],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $root_doc = $event->getRequest()->getBasePath();
        $request_uri = $event->getRequest()->getRequestUri();

        self::$skip_db_checks = false;
        if ($event->getRequest()->server->has('REQUEST_URI')) {
            if (preg_match('#^' . $root_doc . '/front/(css|locale).php#', $request_uri) === 1) {
                self::$skip_db_checks  = true;
            }

            $no_db_checks_scripts = [
                '#^' . $root_doc . '/$#',
                '#^' . $root_doc . '/index.php#',
                '#^' . $root_doc . '/install/install.php#',
                '#^' . $root_doc . '/install/update.php#',
            ];
            foreach ($no_db_checks_scripts as $pattern) {
                if (preg_match($pattern, $request_uri) === 1) {
                    self::$skip_db_checks = true;
                    break;
                }
            }
        }

        // Check if the DB is configured properly
        if (self::$skip_db_checks) {
            return;
        }

        if (!\is_file(GLPI_CONFIG_DIR . '/config_db.php')) {
            Session::loadLanguage('', false);

            $event->setResponse(new StreamedResponse(new DisplayGlpiMisconfiguredPage($this->projectDir), 500));
        }
    }

    public function onAfterLegacyConfigurators(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (self::$skip_db_checks) {
            return;
        }

        /** @var ?DBmysql $DB */
        global $DB;

        if ($DB instanceof DBmysql) {
            //Database connection
            if (!$DB->connected) {
                $event->setResponse(new StreamedResponse(fn () => DBConnection::displayMySQLError(), 500));
                return;
            }

            //Options from DB, do not touch this part.
            if (!Config::isLegacyConfigurationLoaded()) {
                $event->setResponse(new Response('Error accessing config table', 500));
            }
        } else {
            $event->setResponse(new StreamedResponse(new DisplayGlpiMisconfiguredPage($this->projectDir), 500));
        }
    }
}
