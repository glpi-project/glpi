<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

/// Location class
class Location extends CommonTreeDropdown {

   // From CommonDBTM
   public $dohistory = true;


   static function canCreate() {
      return Session::haveRight('entity_dropdown', 'w');
   }


   static function canView() {
      return Session::haveRight('entity_dropdown', 'r');
   }


   function getAdditionalFields() {

      return array(array('name'  => $this->getForeignKeyField(),
                         'label' => __('As child of'),
                         'type'  => 'parent',
                         'list'  => false),
                   array('name'  => 'building',
                         'label' => __('Building number'),
                         'type'  => 'text',
                         'list'  => true),
                   array('name'  => 'room',
                         'label' => __('Room number'),
                         'type'  => 'text',
                         'list'  => true));
   }


   static function getTypeName($nb=0) {
      return _n('Location','Locations',$nb);
   }


   static function getSearchOptionsToAdd() {

      $tab                      = array();

      $tab[3]['table']          = 'glpi_locations';
      $tab[3]['field']          = 'completename';
      $tab[3]['name']           = __('Location');
      $tab[3]['datatype']       = 'dropdown';

      $tab[91]['table']         = 'glpi_locations';
      $tab[91]['field']         = 'building';
      $tab[91]['name']          = __('Building number');
      $tab[91]['massiveaction'] = false;
      $tab[91]['datatype']      = 'string';
      

      $tab[92]['table']         = 'glpi_locations';
      $tab[92]['field']         = 'room';
      $tab[92]['name']          = __('Room number');
      $tab[92]['massiveaction'] = false;
      $tab[92]['datatype']      = 'string';

      $tab[93]['table']         = 'glpi_locations';
      $tab[93]['field']         = 'comment';
      $tab[93]['name']          = __('Location comments');
      $tab[93]['massiveaction'] = false;
      $tab[93]['datatype']      = 'text';

      return $tab;
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab                 = parent::getSearchOptions();

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'building';
      $tab[11]['name']     = __('Building number');
      $tab[11]['datatype'] = 'text';

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'room';
      $tab[12]['name']     = __('Room number');
      $tab[12]['datatype'] = 'text';

      return $tab;
   }


   function defineTabs($options=array()) {

      $ong = parent::defineTabs($options);
      $this->addStandardTab('Netpoint', $ong, $options);

      return $ong;
   }


   function cleanDBonPurge() {

      Rule::cleanForItemAction($this);
      Rule::cleanForItemCriteria($this, 'users_locations');
   }

}
?>