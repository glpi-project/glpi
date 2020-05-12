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
class DbTimezones extends AbstractRequirement {

   /**
    * DB instance.
    *
    * @var \DBmysql
    */
   private $db;

   public function __construct(\DBmysql $db) {
      $this->title = __('Testing DB timezone data');
      $this->db = $db;
      $this->optional = true;
   }

   protected function check() {
      $tz_warning = '';
      $tz_available = $this->db->areTimezonesAvailable($tz_warning);

      if (!$tz_available) {
         $this->validated = false;
         $this->validation_messages[] = $tz_warning;
      } else {
         $this->validated = true;
         $this->validation_messages[] = __('Timezones seems loaded in database');
      }
   }
}
