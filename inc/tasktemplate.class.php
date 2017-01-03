<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2016 Teclib'.

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
   die("Sorry. You can't access this file directly");
}

/**
 * Template for task
 * @since version 9.1
**/
class TaskTemplate extends CommonDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'taskcategory';



   static function getTypeName($nb=0) {
      return _n('Task template', 'Task templates', $nb);
   }


   function getAdditionalFields() {

      return array(array('name'  => 'taskcategories_id',
                         'label' => __('Task category'),
                         'type'  => 'dropdownValue',
                         'list'  => true),
                   array('name'  => 'actiontime',
                         'label' => __('Duration'),
                         'type'  => 'actiontime'),
                   array('name'  => 'content',
                         'label' => __('Content'),
                         'type'  => 'textarea'));
   }


   function getSearchOptionsNew() {
      $tab = parent::getSearchOptionsNew();

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
         'name'               => __('Task category'),
         'field'              => 'name',
         'table'              => getTableForItemType('TaskCategory'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }


   /**
    * @see CommonDropdown::displaySpecificTypeField()
   **/
   function displaySpecificTypeField($ID, $field=array()) {

      switch ($field['type']) {
         case 'actiontime' :
            $toadd = array();
            for ($i=9 ; $i<=100 ; $i++) {
               $toadd[] = $i*HOUR_TIMESTAMP;
            }
            Dropdown::showTimeStamp("actiontime",
                                    array('min'             => 0,
                                          'max'             => 8*HOUR_TIMESTAMP,
                                          'value'           => $this->fields["actiontime"],
                                          'addfirstminutes' => true,
                                          'inhours'         => true,
                                          'toadd'           => $toadd));
            break;
      }
   }
}
