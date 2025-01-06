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

namespace Glpi\Kernel\Listener;

use Glpi\Kernel\ListenersPriority;
use Glpi\Kernel\PostBootEvent;
use Session;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SessionStart implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PostBootEvent::class => ['onPostBoot', ListenersPriority::POST_BOOT_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onPostBoot(): void
    {
        // The session must be started even in CLI context.
        // The GLPI code refers to the session in many places
        // and we cannot safely remove its initialization in the CLI context.
        $start_session = true;

        if (isset($_SERVER['REQUEST_URI'])) {
            // Specific configuration related to web context

            $request = Request::createFromGlobals();
            $path = $request->getPathInfo();

            $use_cookies = true;
            if (\str_starts_with($path, '/api.php') || \str_starts_with($path, '/apirest.php')) {
                // API clients must not use cookies, as the session token is expected to be passed in headers.
                $use_cookies = false;
                // The API endpoint is starting the session manually.
                $start_session = false;
            } elseif (\str_starts_with($path, '/caldav.php')) {
                // CalDAV clients must not use cookies, as the authentication is expected to be passed in headers.
                $use_cookies = false;
            } elseif (\str_starts_with($path, '/front/cron.php')) {
                // The cron endpoint is not expected to use the authenticated user session.
                $use_cookies = false;
            } elseif (\str_starts_with($path, '/front/planning.php') && $request->query->has('genical')) {
                // The `genical` endpoint must not use cookies, as the authentication is expected to be passed in the query parameters.
                $use_cookies = false;
            }

            if (!$use_cookies) {
                ini_set('session.use_cookies', 0);
            }
        }

        if ($start_session) {
            if (Session::canWriteSessionFiles()) {
                Session::setPath();
            } else {
                \trigger_error(
                    sprintf('Unable to write session files on `%s`.', GLPI_SESSION_DIR),
                    E_USER_WARNING
                );
            }

            Session::start();
        }
    }
}
