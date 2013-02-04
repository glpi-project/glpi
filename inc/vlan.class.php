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

/// Class Vlan
class Vlan extends CommonDropdown {


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['setup'][90];
      }
      return $LANG['networking'][56];
   }


   function getAdditionalFields() {
      global $LANG;

      return array(array('name'     => 'tag',
                         'label'    => $LANG['common'][114],
                         'type'     => 'number',
                         'list'     => true));
   }


   function displaySpecificTypeField($ID, $field=array()) {

      if ($field['name'] == 'tag') {
         Dropdown::showInteger('tag', $this->fields['tag'], 1, pow(2,12)-2);
      }
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
      $tab[11]['field']    = 'tag';
      $tab[11]['name']     = $LANG['common'][114];
      $tab[11]['datatype'] = 'number';

      return $tab;
   }


   function cleanDBonPurge() {
      global $DB;

      // Temporary solution to clean wrong updated items
      $query = "DELETE
                FROM `glpi_networkports_vlans`
                WHERE `vlans_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);
   }

}
?>