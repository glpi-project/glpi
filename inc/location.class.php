<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Location class
class Location extends CommonTreeDropdown {

   // From CommonDBTM
   public $dohistory = true;


   function canCreate() {
      return Session::haveRight('entity_dropdown', 'w');
   }


   function canView() {
      return Session::haveRight('entity_dropdown', 'r');
   }


   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => $this->getForeignKeyField(),
                         'label' => $LANG['setup'][75],
                         'type'  => 'parent',
                         'list'  => false),
                   array('name'  => 'building',
                         'label' => $LANG['setup'][99],
                         'type'  => 'text',
                         'list'  => true),
                   array('name'  => 'room',
                         'label' => $LANG['setup'][100],
                         'type'  => 'text',
                         'list'  => true));
   }


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['dropdown'][1];
      }
      return $LANG['common'][15];
   }


   static function getSearchOptionsToAdd() {
      global $LANG;

      $tab = array();
      $tab[3]['table'] = 'glpi_locations';
      $tab[3]['field'] = 'completename';
      $tab[3]['name']  = $LANG['common'][15];

      $tab[91]['table']         = 'glpi_locations';
      $tab[91]['field']         = 'building';
      $tab[91]['name']          = $LANG['common'][15]." - ".$LANG['setup'][99];
      $tab[91]['massiveaction'] = false;

      $tab[92]['table']         = 'glpi_locations';
      $tab[92]['field']         = 'room';
      $tab[92]['name']          = $LANG['common'][15]." - ".$LANG['setup'][100];
      $tab[92]['massiveaction'] = false;

      $tab[93]['table']         = 'glpi_locations';
      $tab[93]['field']         = 'comment';
      $tab[93]['name']          = $LANG['common'][15]." - ".$LANG['common'][25];
      $tab[93]['massiveaction'] = false;

      return $tab;
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'building';
      $tab[11]['name']     = $LANG['setup'][99];
      $tab[11]['datatype'] = 'text';

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'room';
      $tab[12]['name']     = $LANG['setup'][100];
      $tab[12]['datatype'] = 'text';

      return $tab;
   }


   function defineTabs($options=array()) {
      global $LANG;

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