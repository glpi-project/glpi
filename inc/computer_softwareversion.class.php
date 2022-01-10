<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @deprecated 9.5.0 Use Item_SoftwareVersion
 */
class Computer_SoftwareVersion extends Item_SoftwareVersion {

   static function getTable($classname = null) {
      return Item_SoftwareVersion::getTable();
   }

   public function prepareInputForAdd($input) {
      //Copy input to match new format
      $input['itemtype'] = 'Computer';
      if (isset($input['computers_id'])) {
         $input['items_id'] = $input['computers_id'];
      }
      parent::prepareInputForAdd($input);
   }

   public function prepareInputForUpdate($input) {
      //Copy input to match new format
      $input['itemtype'] = 'Computer';
      if (isset($input['computers_id'])) {
         $input['items_id'] = $input['computers_id'];
      }
      parent::prepareInputForUpdate($input);
   }

   public function post_getFromDB() {
      //Copy fields to match new format
      $this->fields['itemtype'] = 'Computer';
      $this->fields['computers_id'] = $this->fields['items_id'];
      parent::post_getFromDB();
   }
}
