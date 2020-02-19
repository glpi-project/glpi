<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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
   die("Sorry. You can't access directly to this file");
}


class ApplianceType extends CommonDropdown {

   static $rightname = "appliance";

   public static function getTypeName($nb = 0) {
      return _n('Appliance type', 'Appliances types', $nb);
   }


   public function prepareInputForAdd($input) {
      if (array_key_exists('externalidentifier', $input) && !$input['externalid']) {
         // INSERT NULL as this value is an UNIQUE index
         unset($input['externalidentifier']);
      }
      return $input;
   }


    /**
     * @param $ID
     * @param $entity
     *
     * @return integer or boolean
    **/
   static function transfer($ID, $entity) {
      global $DB;

      $temp = new self();
      if (($ID <= 0) || !$temp->getFromDB($ID)) {
         return 0;
      }

      $iterator = $DB->request([
        'SELECT' => 'id',
        'FROM'   => $temp->getTable(),
        'WHERE'  => [
           'entities_id' => $entity,
           'name'        => addslashes($temp->fields['name'])
        ]
      ]);
      if (count($iterator)) {
         $rel = $iterator->next();
         return $rel['id'];
      }

      $input = $temp->fields;
      $input['entities_id'] = $entity;
      unset($input['id']);
      return $temp->add($input);
   }
}
