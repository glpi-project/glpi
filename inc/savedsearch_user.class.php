<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

class SavedSearch_User extends CommonDBRelation {
   public $auto_message_on_action = false;

   static public $itemtype_1          = 'SavedSearch';
   static public $items_id_1          = 'savedsearches_id';

   static public $itemtype_2          = 'User';
   static public $items_id_2          = 'users_id';


   static function getSpecificValueToDisplay($field, $values, array $options=array()) {
      global $CFG_GLPI;

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'users_id':
            if (!empty($values[$field])) {
               return "<img src='{$CFG_GLPI['root_doc']}/pics/bookmark_default.png' alt='" . __('Yes') . "'/>";
            } else {
               return "<img src='{$CFG_GLPI['root_doc']}/pics/bookmark_record.png' alt='" . __('No') . "'/>";
            }
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {
      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;

      switch ($field) {
         case 'users_id':
            $options['name']  = $name;
            $options['value'] = $values[$field];
            return Dropdown::showFromArray(
               $options['name'],
               [
                  '1'   => __('Yes'),
                  '0'   => __('No')
               ],
               $options
            );
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }
}
