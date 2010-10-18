<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// CommonDevice Class for Device*class
abstract class CommonDevice extends CommonDropdown {

   function canCreate() {
      return haveRight('device','w');
   }

   function canView() {
      return haveRight('device','r');
   }

   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => 'manufacturers_id',
                         'label' => $LANG['common'][5],
                         'type'  => 'dropdownValue'));
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common']           = $LANG['common'][32];;

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'designation';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_link'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[23]['table'] = 'glpi_manufacturers';
      $tab[23]['field'] = 'name';
      $tab[23]['name']  = $LANG['common'][5];

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = $LANG['common'][25];
      $tab[16]['datatype'] = 'text';

      return $tab;
   }


   function title() {
      global $LANG;

      Dropdown::showItemTypeMenu($LANG['title'][30],
                                 Dropdown::getDeviceItemTypes(), $this->getSearchUrl());
   }

   function displayHeader () {
      commonHeader($this->getTypeName(),'',"config","device",get_class($this));
   }

   function getName($with_comment=0) {
      $toadd="";
      if ($with_comment) {
         $toadd="&nbsp;".$this->getComments();
      }

      if (isset($this->fields['designation']) && !empty($this->fields['designation'])) {
         return $this->fields['designation'].$toadd;
      }
      return NOT_AVAILABLE;
   }

   /**
    * return the display data for a specific device
    *
    * @return array
    */
   function getFormData() {
      return false;
   }

   /**
    * Return the specifities localized name for the Device
    *
    * @return string
    */
   static function getSpecifityLabel() {
      return array();
   }

   function cleanDBonPurge() {

      $compdev = new Computer_Device();
      $compdev->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }

   /**
    * Import a device is not exists
    *
    * @param $input of data
    *
    * @return interger ID of existing or new Device
    */
   function import($input) {
      global $DB;

      if (!isset($input['designation']) || empty($input['designation'])) {
         return 0;
      }
      $query = "SELECT `id`
                FROM `".$this->getTable()."`
                WHERE `designation` = '" . $input['designation'] . "'";

      $result = $DB->query($query);
      if ($DB->numrows($result)>0) {
         $line = $DB->fetch_array($result);
         return $line['id'];
      }
      return $this->add($input);
   }
}

?>