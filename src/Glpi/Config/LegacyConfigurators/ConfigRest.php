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

namespace Glpi\Config\LegacyConfigurators;

use Glpi\Config\LegacyConfigProviderInterface;
use Session;

final readonly class ConfigRest implements LegacyConfigProviderInterface
{
    public function execute(): void
    {
        /**
         * @var array $CFG_GLPI
         * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
         */
        global $CFG_GLPI, $GLPI_CACHE;

        // Manage profile change
        if (isset($_REQUEST["force_profile"]) && ($_SESSION['glpiactiveprofile']['id'] ?? -1) != $_REQUEST["force_profile"]) {
            if (isset($_SESSION['glpiprofiles'][$_REQUEST["force_profile"]])) {
                Session::changeProfile($_REQUEST["force_profile"]);
            }
        }

        // Manage entity change
        if (isset($_REQUEST["force_entity"]) && ($_SESSION["glpiactive_entity"] ?? -1) != $_REQUEST["force_entity"]) {
            Session::changeActiveEntities($_REQUEST["force_entity"], true);
        } elseif (Session::shouldReloadActiveEntities()) {
            Session::changeActiveEntities(
                $_SESSION["glpiactive_entity"],
                $_SESSION["glpiactive_entity_recursive"]
            );
        }

        // The user's current groups are stored in his session
        // If there was any change regarding groups membership and/or configuration, we
        // need to reset the data stored in his session
        if (
            isset($_SESSION['glpigroups'])
            && (
                !isset($_SESSION['glpigroups_cache_date'])
                || $_SESSION['glpigroups_cache_date'] < $GLPI_CACHE->get('last_group_change')
            )
        ) {
            Session::loadGroups();
        }
    }
}
