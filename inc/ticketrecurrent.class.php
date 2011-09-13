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
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {
      return parent::getSearchOptions();
   }


}
?>
