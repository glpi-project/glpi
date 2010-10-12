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

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/// Class DeviceNetworkCard
class DeviceNetworkCard extends CommonDevice {

   static function getTypeName() {
      global $LANG;

      return $LANG['devices'][3];
   }

   static function getSpecifityLabel() {
      global $LANG;

      return array('specificity'=>$LANG['device_iface'][2]);
   }

   function getAdditionalFields() {
      global $LANG;


      return array_merge(parent::getAdditionalFields(),
                         array(array('name'  => 'specif_default',
                                     'label' => $LANG['device_iface'][2]." ".$LANG['devices'][24],
                                     'type'  => 'text'),
                               array('name'  => 'bandwidth',
                                     'label' => $LANG['device_iface'][0],
                                     'type'  => 'text')));
   }

   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[11]['table']         = $this->getTable();
      $tab[11]['field']         = 'specif_default';
      $tab[11]['name']          = $LANG['device_iface'][2]." ".$LANG['devices'][24];
      $tab[11]['datatype']      = 'text';

      $tab[12]['table']         = $this->getTable();
      $tab[12]['field']         = 'bandwidth';
      $tab[12]['name']          = $LANG['device_iface'][0];
      $tab[12]['datatype']      = 'text';

      return $tab;
   }

   /**
    * return the display data for a specific device
    *
    * @return array
    */
   function getFormData() {
      global $LANG;

      $data['label'] = $data['value'] = array();
      if (!empty($this->fields["bandwidth"])) {
         $data['label'][] = $LANG['device_iface'][0];
         $data['value'][] = $this->fields["bandwidth"];
      }
      // Specificity
      $data['label'][] = $LANG['device_iface'][2];
      $data['size'] = 18;

      return $data;
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

      if (isset($input["bandwidth"])) {
         $query.=" AND `bandwidth` = '".$input["bandwidth"]."'";
      }

      $result = $DB->query($query);
      if ($DB->numrows($result)>0) {
         $line = $DB->fetch_array($result);
         return $line['id'];
      }
      return $this->add($input);
   }
}

?>