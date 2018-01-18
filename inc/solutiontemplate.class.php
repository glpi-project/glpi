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
 * SolutionTemplate Class
**/
class SolutionTemplate extends CommonDropdown {

   // From CommonDBTM
   public $dohistory = true;

   static $rightname = 'solutiontemplate';

   public $can_be_translated = false;


   static function getTypeName($nb = 0) {
      return _n('Solution template', 'Solution templates', $nb);
   }


   function getAdditionalFields() {

      return [['name'  => 'solutiontypes_id',
                         'label' => __('Solution type'),
                         'type'  => 'dropdownValue',
                         'list'  => true],
                   ['name'  => 'content',
                         'label' => __('Content'),
                         'type'  => 'tinymce']];
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '4',
         'name'               => __('Content'),
         'field'              => 'content',
         'table'              => $this->getTable(),
         'datatype'           => 'text',
         'htmltext'           => true
      ];

      $tab[] = [
         'id'                 => '3',
         'name'               => __('Solution type'),
         'field'              => 'name',
         'table'              => getTableForItemType('SolutionType'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }


   /**
    * @see CommonDropdown::displaySpecificTypeField()
   **/
   function displaySpecificTypeField($ID, $field = []) {

      switch ($field['type']) {
         case 'tinymce' :
            // Display empty field
            echo "&nbsp;</td></tr>";
            // And a new line to have a complete display
            echo "<tr class='center'><td colspan='5'>";
            $rand = mt_rand();
            Html::initEditorSystem($field['name'].$rand);
            echo "<textarea id='".$field['name']."$rand' name='".$field['name']."' rows='3'>".
                   $this->fields[$field['name']]."</textarea>";
            break;
      }
   }

}
