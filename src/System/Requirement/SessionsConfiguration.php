<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\System\Requirement;

/**
 * @since 9.5.0
 */
class SessionsConfiguration extends AbstractRequirement
{
    public function __construct()
    {
        parent::__construct(
            __('Sessions configuration')
        );
    }

    protected function check()
    {
       // Check session extension
        if (!extension_loaded('session')) {
            $this->validated = false;
            $this->validation_messages[] = __('session extension is not installed.');
            return;
        }

       // Check configuration values
        $is_autostart_on   = ini_get('session.auto_start') == 1;
        $is_usetranssid_on = ini_get('session.use_trans_sid') == 1
         || isset($_POST[session_name()]) || isset($_GET[session_name()]);

        if ($is_autostart_on || $is_usetranssid_on) {
            if ($is_autostart_on && $is_usetranssid_on) {
                $this->validation_messages[] = __('"session.auto_start" and "session.use_trans_sid" must be set to off.');
            } else if ($is_autostart_on) {
                $this->validation_messages[] = __('"session.auto_start" must be set to off.');
            } else {
                $this->validation_messages[] = __('"session.use_trans_sid" must be set to off.');
            }

            $this->validated = false;
            $this->validation_messages[] = __('See .htaccess file in the GLPI root for more information.');

            return;
        }

        $this->validated = true;
        $this->validation_messages[] = __s('Sessions configuration is OK.');
    }
}
