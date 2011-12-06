<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
// Original Author of file: Damien Touraine
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class WifiNetwork
/// since version 0.84
class WifiNetwork extends CommonDropdown {

   public $dohistory = true;


   static function getWifiCardVersion() {
      return array('a', 'a/b', 'a/b/g', 'a/b/g/n', 'a/b/g/n/y');
   }


   static function getWifiCardModes() {
      global $LANG;

      return array('ad-hoc'    => $LANG['Internet'][46],
                   'managed'   => $LANG['Internet'][47],
                   'master'    => $LANG['Internet'][48],
                   'repeater'  => $LANG['Internet'][49],
                   'secondary' => $LANG['Internet'][50],
                   'monitor'   => $LANG['Internet'][51],
                   'auto'      => $LANG['Internet'][52]);
   }


   static function getWifiNetworkModes() {
      global $LANG;

      return array('infrastructure' => $LANG['Internet'][53],
                   'ad-hoc'         => $LANG['Internet'][54]);
   }


   function canCreate() {
      return Session::haveRight('internet', 'w');
   }


   function canView() {
      return Session::haveRight('internet', 'r');
   }


   function defineTabs($options=array()) {
      $ong  = array();
      $this->addStandardTab('NetworkPort',$ong, $options);

      return $ong;
   }


   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => 'essid',
                         'label' => $LANG['Internet'][24],
                         'type'  => 'text',
                         'list'  => true),
                   array('name'  => 'mode',
                         'label' => $LANG['Internet'][55],
                         'type'  => 'wifi_mode',
                         'list'  => true));
   }


   function displaySpecificTypeField($ID, $field=array()) {

      if ($field['type'] == 'wifi_mode') {
         echo "<select name='".$field['name']."'>";
         echo "<option value=''></option>";
         foreach (self::getWifiNetworkModes() as $value => $name) {
            echo "<option value='$value'";
            if ($this->fields[$field['name']] == $value) {
               echo " selected";
            }
            echo ">$name</option>";
         }
         echo "</select>";
      }
   }


   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[10]['table']    = $this->getTable();
      $tab[10]['field']    = 'essid';
      $tab[10]['name']     = $LANG['Internet'][24];

      return $tab;
   }


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['Internet'][14];
      }
      return $LANG['Internet'][13];
   }

}
?>
