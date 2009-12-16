<?php
/*
 * @version $Id: document.class.php 9112 2009-10-13 20:17:16Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

/// TicketsCategory class
class TicketCategory extends CommonTreeDropdown {

   // From CommonDBTM
   public $table = 'glpi_ticketcategories';
   public $type = 'TicketCategory';
   public $entity_assign = true;
   public $may_be_recursive = true;

   function canCreate() {
      return haveRight('entity_dropdown','w');
   }

   function canView() {
      return haveRight('entity_dropdown','r');
   }

   function getAdditionalFields() {
      global $LANG;

      return  array(array('name'  => getForeignKeyFieldForTable($this->table),
                          'label' => $LANG['setup'][75],
                          'type'  => 'parent',
                          'list'  => false),
                   array('name'  => 'users_id',
                          'label' => $LANG['common'][10],
                          'type'  => 'dropdownUsersID',
                          'list'  => true),
                    array('name'  => 'groups_id',
                          'label' => $LANG['common'][35],
                          'type'  => 'dropdownValue',
                          'list'  => true),
                    array('name'  => 'knowbaseitemcategories_id',
                          'label' => $LANG['title'][5],
                          'type'  => 'dropdownValue',
                          'list'  => true));
   }

   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[70]['table']     = 'glpi_users';
      $tab[70]['field']     = 'name';
      $tab[70]['linkfield'] = 'users_id';
      $tab[70]['name']      = $LANG['common'][10];

      $tab[71]['table']     = 'glpi_groups';
      $tab[71]['field']     = 'name';
      $tab[71]['linkfield'] = 'groups_id';
      $tab[71]['name']      = $LANG['common'][35];

      return $tab;
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][79];
   }
}

?>