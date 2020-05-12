<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\System\Requirement;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5.0
 */
class SessionsConfiguration extends AbstractRequirement {

   public function __construct() {
      $this->title = __('Sessions test');
   }

   protected function check() {
      // Check session extension
      if (!extension_loaded('session')) {
         $this->validated = false;
         $this->validation_messages[] = __('Your parser PHP is not installed with sessions support!');
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
         $this->validation_messages[] = __('See .htaccess file in the GLPI root for more informations.');

         return;
      }

      $this->validated = true;
      $this->validation_messages[] = __s('Sessions support is available - Perfect!');
   }

}
