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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Ticket Recurrent class
/// since version 0.83
class TicketRecurrent extends CommonDropdown {

   // From CommonDBTM
   public $dohistory = true;

   // From CommonDropdown
   public $first_level_menu  = "maintain";
   public $second_level_menu = "ticketrecurrent";

   public $display_dropdowntitle  = false;


   static function getTypeName($nb=0) {
      global $LANG;

      return $LANG['jobrecurrent'][1];
   }


   function canCreate() {
      return Session::haveRight('show_all_ticket', 1);
   }


   function canView() {
      return Session::haveRight('show_all_ticket', 1);
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $LANG;

      switch ($item->getType()) {
         case 'TicketRecurrent' :
            switch ($tabnum) {
               case 1 :
                  // TODO showStats est une fonction de ticket.class sans paramètre
                  $item->showStats($item);
                  return true;
            }
            break;
      }
      return false;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (Session::haveRight("tickettemplate","r")) {
         switch ($item->getType()) {
            case 'TicketRecurrent' :
               $ong[1] = $LANG['jobrecurrent'][1];
               return $ong;
         }
      }
      return '';
   }


   function defineTabs($options=array()) {
      global $LANG, $CFG_GLPI;

      $ong = array();

      $this->addStandardTab('TicketRecurrent', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   /**
    * Return Additional Fileds for this type
   **/
   function getAdditionalFields() {
      global $LANG;
      return array(array('name'  => 'is_active',
                         'label' => $LANG['common'][60],
                         'type'  => 'bool',
                         'list'  => false),
                   array('name'  => 'tickettemplates_id',
                         'label' => $LANG['job'][58],
                         'type'  => 'dropdownValue',
                         'list'  => true),
                   array('name'  => 'begin_date',
                         'label' => $LANG['search'][8],
                         'type'  => 'datetime',
                         'list'  => false),
                   array('name'  => 'create_before',
                         'label' => $LANG['jobrecurrent'][2],
                         'type'  => 'integer',
                         'unit'  => $LANG['gmt'][1],
                         'list'  => true),);
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
      $tab[11]['field']    = 'is_active';
      $tab[11]['name']     = $LANG['common'][60];
      $tab[11]['datatype'] = 'bool';

      $tab[12]['table']    = 'glpi_tickettemplates';
      $tab[12]['field']    = 'name';
      $tab[12]['name']     = $LANG['job'][58];
      $tab[12]['datatype'] = 'itemtypename';

      $tab[13]['table']    = $this->getTable();
      $tab[13]['field']    = 'begin_date';
      $tab[13]['name']     = $LANG['search'][8];
      $tab[13]['datatype'] = 'datetime';

      $tab[14]['table']    = $this->getTable();
      $tab[14]['field']    = 'create_before';
      $tab[14]['name']     = $LANG['jobrecurrent'][2];
      $tab[14]['datatype'] = 'integer';
      $tab[14]['unit']     = '&nbsp;'.$LANG['gmt'][1];

      return $tab;
   }



}
?>
