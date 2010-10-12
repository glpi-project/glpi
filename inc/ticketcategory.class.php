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

/// TicketsCategory class
class TicketCategory extends CommonTreeDropdown {

   function canCreate() {
      return haveRight('entity_dropdown','w');
   }

   function canView() {
      return haveRight('entity_dropdown','r');
   }

   function getAdditionalFields() {
      global $LANG;

      return  array(array('name'  => $this->getForeignKeyField(),
                          'label' => $LANG['setup'][75],
                          'type'  => 'parent',
                          'list'  => false),
                   array('name'  => 'users_id',
                          'label' => $LANG['common'][10],
                          'type'  => 'UserDropdown',
                          'list'  => true),
                    array('name'  => 'groups_id',
                          'label' => $LANG['common'][35],
                          'type'  => 'dropdownValue',
                          'list'  => true),
                    array('name'  => 'knowbaseitemcategories_id',
                          'label' => $LANG['title'][5],
                          'type'  => 'dropdownValue',
                          'list'  => true),
                    array('name'  => 'is_helpdeskvisible',
                          'label' => $LANG['tracking'][39],
                          'type'  => 'bool',
                          'list'  => true));
   }

   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[70]['table']     = 'glpi_users';
      $tab[70]['field']     = 'name';
      $tab[70]['name']      = $LANG['common'][10];

      $tab[71]['table']     = 'glpi_groups';
      $tab[71]['field']     = 'name';
      $tab[71]['name']      = $LANG['common'][35];

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'is_helpdeskvisible';
      $tab[2]['name']      = $LANG['tracking'][39];
      $tab[2]['datatype']  = 'bool';

      return $tab;
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][79];
   }

   function post_getEmpty () {
      $this->fields['is_helpdeskvisible'] = 1;
   }

   /**
    * Get links to Faq
    *
    * @param $withname boolean : also display name ?
    */
   function getLinks($withname=false) {
      global $CFG_GLPI,$LANG;

      $ret = '';

      if ($withname) {
         $ret .= $this->fields["name"];
         $ret .= "&nbsp;&nbsp;";
      }

      if ($this->fields['knowbaseitemcategories_id']) {
         $ret.= "<a href='".$CFG_GLPI["root_doc"].
            "/front/knowbaseitem.php?knowbaseitemcategories_id=".
            $this->fields['knowbaseitemcategories_id'].
            "'><img src='".$CFG_GLPI["root_doc"]."/pics/faqadd.png' class='middle' alt='".
            $LANG['knowbase'][1]."' title='".$LANG['knowbase'][1]."'></a>";
      }
      return $ret;
   }

}

?>