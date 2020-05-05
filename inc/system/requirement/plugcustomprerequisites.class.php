<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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
class PlugCustomPrerequisites extends AbstractRequirement {

   /**
    * GLPI plugin key.
    *
    * @var string
    */
   private $key;

   /**
    * @param string $key  GLPI plugin key
    */
   public function __construct(string $key) {
      $this->title = sprintf(__('Testing GLPI plugin %s custom prerequisites'), $key);
      $this->key = $key;
   }

   protected function check() {
      $check_function = 'plugin_' . $this->key . '_check_prerequisites';
      if (function_exists($check_function)) {
         ob_start();
         $this->validated = $check_function();
         $this->validation_messages[] = ob_get_contents();
         ob_end_clean();
      } else {
         $this->out_of_context = true; // No function defined, this check has no valid context
         $this->validated = true;
      }
   }
}
