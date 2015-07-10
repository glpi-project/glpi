<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * SolutionTemplate Class
**/
class SolutionTemplate extends CommonDropdown {

   // From CommonDBTM
   public $dohistory = true;

   static $rightname = 'solutiontemplate';

   var $can_be_translated = false;


   static function getTypeName($nb=0) {
      return _n('Solution template', 'Solution templates', $nb);
   }


   function getAdditionalFields() {

      return array(array('name'  => 'solutiontypes_id',
                         'label' => __('Solution type'),
                         'type'  => 'dropdownValue',
                         'list'  => true),
                   array('name'  => 'content',
                         'label' => __('Content'),
                         'type'  => 'tinymce'));
   }


   /**
    * @since version 0.83
   **/
   function getSearchOptions() {

      $tab                = parent::getSearchOptions();

      $tab[4]['name']     = __('Content');
      $tab[4]['field']    = 'content';
      $tab[4]['table']    = $this->getTable();
      $tab[4]['datatype'] = 'text';
      $tab[4]['htmltext'] = true;

      $tab[3]['name']     = __('Solution type');
      $tab[3]['field']    = 'name';
      $tab[3]['table']    = getTableForItemType('SolutionType');
      $tab[3]['datatype'] = 'dropdown';

      return $tab;
   }


   /**
    * @see CommonDropdown::displaySpecificTypeField()
   **/
   function displaySpecificTypeField($ID, $field=array()) {

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
?>