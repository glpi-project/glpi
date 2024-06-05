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
use Glpi\Debug\Profile;
use Glpi\Toolbox\URL;
use Session;

final readonly class SessionConfig implements LegacyConfigProviderInterface
{
    public function execute(): void
    {
        // Load Language file
        Session::loadLanguage();

        if (
            isset($_SESSION['glpi_use_mode'])
            && ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE)
        ) {
            // Start the debug profile
            Profile::getCurrent();
        }

        if (!isset($_SESSION["MESSAGE_AFTER_REDIRECT"])) {
            $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
        }

        // Manage force tab
        if (isset($_REQUEST['forcetab'])) {
            $itemtype = URL::extractItemtypeFromUrlPath($_SERVER['PHP_SELF']);
            if ($itemtype !== null) {
                Session::setActiveTab($itemtype, $_REQUEST['forcetab']);
            }
        }

        // Manage tabs
        if (isset($_REQUEST['glpi_tab']) && isset($_REQUEST['itemtype'])) {
            Session::setActiveTab($_REQUEST['itemtype'], $_REQUEST['glpi_tab']);
        }
        // Override list-limit if choosen
        if (isset($_REQUEST['glpilist_limit'])) {
            $_SESSION['glpilist_limit'] = $_REQUEST['glpilist_limit'];
        }
    }
}
