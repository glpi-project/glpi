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
   die("Sorry. You can't access this file directly");
}

/**
 * Template for followups
 * @since 9.5
**/
class ITILFollowupTemplate extends CommonDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;


   static function getTypeName($nb = 0) {
      return _n('Followup template', 'Followup templates', $nb);
   }


   function getAdditionalFields() {
      return [
         [
            'name'  => 'content',
            'label' => __('Content'),
            'type'  => 'tinymce',
         ], [
            'name'  => 'requesttypes_id',
            'label' => __('Source of followup'),
            'type'  => 'dropdownValue',
            'list'  => true
         ], [
            'name'  => 'is_private',
            'label' => __('Private'),
            'type'  => 'bool'
         ]
      ];
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '4',
         'name'               => __('Content'),
         'field'              => 'content',
         'table'              => self::getTable(),
         'datatype'           => 'text',
         'htmltext'           => true
      ];

      $tab[] = [
         'id'                 => '5',
         'name'               => __('Source of followup'),
         'field'              => 'name',
         'table'              => getTableForItemType('RequestType'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '6',
         'name'               => __('Private'),
         'field'              => 'is_private',
         'table'              => self::getTable(),
         'datatype'           => 'bool'
      ];

      return $tab;
   }

   function displaySpecificTypeField($ID, $field = []) {
      switch ($field['type']) {
         case 'tinymce' :
            Html::textarea([
               'name'            => 'content',
               'value'           => $this->fields["content"],
               'enable_richtext' => true,
            ]);
            break;
      }
   }
}
