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
 * @since 9.5.1
 */
class MysqliMysqlnd extends Extension {

   /**
    */
   public function __construct() {
      parent::__construct('mysqli');
   }

   protected function check() {
      $extension_loaded = extension_loaded('mysqli');
      $driver_is_mysqlnd = defined('MYSQLI_OPT_INT_AND_FLOAT_NATIVE');

      // We check for "mysqli_fetch_all" function to be sure that the used driver is "mysqlnd".
      // Indeed, it is mandatory to be able to use MYSQLI_OPT_INT_AND_FLOAT_NATIVE option.
      $this->validated = $extension_loaded && $driver_is_mysqlnd;

      if ($extension_loaded && $driver_is_mysqlnd) {
         $this->validation_messages[] = sprintf(__('%s extension is installed'), $this->name);
      } else if ($extension_loaded && !$driver_is_mysqlnd) {
         $this->validation_messages[] = sprintf(__('%s extension is installed but is not using mysqlnd driver'), $this->name);
      } else {
         $this->validation_messages[] = sprintf(__('%s extension is missing'), $this->name);
      }
   }
}
