<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Kernel\Listener\PostBootListener;

use DBConnection;
use Glpi\Debug\Profiler;
use Glpi\Kernel\ListenersPriority;
use Glpi\Kernel\PostBootEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SetDbSessionVars implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PostBootEvent::class => [
                ['onPostBoot', ListenersPriority::POST_BOOT_LISTENERS_PRIORITIES[self::class]],
            ],
        ];
    }

    public function onPostBoot(): void
    {
        global $DB;

        Profiler::getInstance()->start('SetDbSessionVars::execute', Profiler::CATEGORY_BOOT);

        if (DBConnection::isDbAvailable() && $DB->use_timezones) {
            $timezone = $this->getConfiguredTimezone();
            $DB->setTimezone($timezone);
        }

        Profiler::getInstance()->stop('SetDbSessionVars::execute');
    }

    /**
     * Get the currently configured timezone.
     *
     * The value is fetched from `$_SESSION['glpitimezone']`.
     * It will contain the value defined by the connected user in its preference,
     * with a fallback to the value defined by the global GLPI configuration.
     *
     * @return string
     */
    private function getConfiguredTimezone(): string
    {
        $timezone = $_SESSION['glpitimezone'] ?? '0';
        if ($timezone === '0') {
            // '0' is for 'Use server configuration'
            return date_default_timezone_get();
        }

        return $timezone;
    }
}
