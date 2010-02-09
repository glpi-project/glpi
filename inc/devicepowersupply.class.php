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

/// Class DevicePowerSupply
class DevicePowerSupply extends CommonDevice {

   static function getTypeName() {
      global $LANG;

      return $LANG['devices'][23];
   }

   function getAdditionalFields() {
      global $LANG;


      return array_merge(parent::getAdditionalFields(),
                         array(array('name'  => 'is_atx',
                                     'label' => $LANG['device_power'][1],
                                     'type'  => 'bool'),
                               array('name'  => 'power',
                                     'label' => $LANG['device_power'][0],
                                     'type'  => 'text')));
   }

   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[11]['table']         = $this->getTable();
      $tab[11]['field']         = 'is_atx';
      $tab[11]['linkfield']     = 'is_atx';
      $tab[11]['name']          = $LANG['device_power'][1];
      $tab[11]['datatype']      = 'bool';

      $tab[12]['table']         = $this->getTable();
      $tab[12]['field']         = 'power';
      $tab[12]['linkfield']     = 'power';
      $tab[12]['name']          = $LANG['device_power'][0];
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
      if ($this->fields["is_atx"]) {
         $data['label'][] = $LANG['device_power'][1];
         $data['value'][] = Dropdown::getYesNo($this->fields["is_atx"]);
      }
      if (!empty($this->fields["power"])) {
         $data['label'][] = $LANG['device_power'][0];
         $data['value'][] = $this->fields["power"];
      }
      return $data;
   }
}

?>