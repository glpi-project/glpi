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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class Holiday
class Holiday extends CommonDropdown {

   static function getTypeName() {
      global $LANG;

      return $LANG['calendar'][11];
   }


   function canCreate() {
      return haveRight('calendar', 'w');
   }


   function canView() {
      return haveRight('calendar', 'r');
   }


   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => 'begin_date',
                         'label' => $LANG['buttons'][33],
                         'type'  => 'date'),
                   array('name'  => 'end_date',
                         'label' => $LANG['buttons'][32],
                         'type'  => 'date'),
                   array('name'  => 'is_perpetual',
                         'label' => $LANG['calendar'][3],
                         'type'  => 'bool'));
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
      $tab[11]['field']    = 'begin_date';
      $tab[11]['name']     = $LANG['buttons'][33];
      $tab[11]['datatype'] = 'date';

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'end_date';
      $tab[12]['name']     = $LANG['buttons'][32];
      $tab[12]['datatype'] = 'date';

      $tab[13]['table']    = $this->getTable();
      $tab[13]['field']    = 'is_perpetual';
      $tab[13]['name']     = $LANG['calendar'][3];
      $tab[13]['datatype'] = 'bool';

      return $tab;
   }


   function prepareInputForAdd($input) {

      $input = parent::prepareInputForAdd ($input);

      if (empty($input['end_date'])
          || $input['end_date'] == 'NULL'
          || $input['end_date'] < $input['begin_date']) {

         $input['end_date'] = $input['begin_date'];
      }
      return $input;
   }


   function prepareInputForUpdate($input) {

      $input = parent::prepareInputForUpdate($input);

      if (empty($input['end_date'])
          || $input['end_date'] == 'NULL'
          || $input['end_date'] < $input['begin_date']) {

         $input['end_date'] = $input['begin_date'];
      }

      return $input;
   }

}

?>
