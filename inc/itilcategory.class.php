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
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// ITILCategory class
class ITILCategory extends CommonTreeDropdown {

   // From CommonDBTM
   public $dohistory = true;


   function canCreate() {
      return Session::haveRight('entity_dropdown', 'w');
   }


   function canView() {
      return Session::haveRight('entity_dropdown', 'r');
   }


   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => $this->getForeignKeyField(),
                         'label' => $LANG['setup'][75],
                         'type'  => 'parent',
                         'list'  => false),
                   array('name'  => 'users_id',
                         'label' => $LANG['common'][10],
                         'type'  => 'UserDropdown',
                         'list'  => true),
                   array('name'  => 'groups_id',
                         'label' => $LANG['common'][109],
                         'type'  => 'dropdownValue',
                         'list'  => true),
                   array('name'  => 'knowbaseitemcategories_id',
                         'label' => $LANG['title'][5],
                         'type'  => 'dropdownValue',
                         'list'  => true),
                   array('name'  => 'is_helpdeskvisible',
                         'label' => $LANG['tracking'][39],
                         'type'  => 'bool',
                         'list'  => true),
                   array('name'  => 'is_incident',
                         'label' => $LANG['job'][70],
                         'type'  => 'bool',
                         'list'  => true),
                   array('name'  => 'is_request',
                         'label' => $LANG['job'][71],
                         'type'  => 'bool',
                         'list'  => true),
                   array('name'  => 'is_problem',
                         'label' => $LANG['job'][72],
                         'type'  => 'bool',
                         'list'  => true),
                   array('name'  => 'tickettemplates_id_demand',
                         'label' => $LANG['job'][66],
                         'type'  => 'dropdownValue',
                         'list'  => true),
                   array('name'  => 'tickettemplates_id_incident',
                         'label' => $LANG['job'][67],
                         'type'  => 'dropdownValue',
                         'list'  => true),
                  );
   }


   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[70]['table'] = 'glpi_users';
      $tab[70]['field'] = 'name';
      $tab[70]['name']  = $LANG['common'][10];

      $tab[71]['table'] = 'glpi_groups';
      $tab[71]['field'] = 'completename';
      $tab[71]['name']  = $LANG['common'][35];

      $tab[72]['table']     = 'glpi_tickettemplates';
      $tab[72]['field']     = 'name';
      $tab[72]['linkfield'] = 'tickettemplates_id_demand';
      $tab[72]['name']      = $LANG['job'][66];

      $tab[73]['table']     = 'glpi_tickettemplates';
      $tab[73]['field']     = 'name';
      $tab[73]['linkfield'] = 'tickettemplates_id_incident';
      $tab[73]['name']      = $LANG['job'][67];

      $tab[74]['table']     = $this->getTable();
      $tab[74]['field']     = 'is_incident';
      $tab[74]['name']      = $LANG['job'][70];
      $tab[74]['datatype'] = 'bool';

      $tab[75]['table']     = $this->getTable();
      $tab[75]['field']     = 'is_request';
      $tab[75]['name']      = $LANG['job'][71];
      $tab[75]['datatype'] = 'bool';

      $tab[76]['table']    = $this->getTable();
      $tab[76]['field']    = 'is_problem';
      $tab[76]['name']     = $LANG['job'][72];
      $tab[76]['datatype'] = 'bool';

      $tab[3]['table']    = $this->getTable();
      $tab[3]['field']    = 'is_helpdeskvisible';
      $tab[3]['name']     = $LANG['tracking'][39];
      $tab[3]['datatype'] = 'bool';

      return $tab;
   }


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['dropdown'][3];
      }
      return $LANG['setup'][79];
   }


   function post_getEmpty() {

      $this->fields['is_helpdeskvisible'] = 1;
      $this->fields['is_request']         = 1;
      $this->fields['is_incident']        = 1;
      $this->fields['is_problem']         = 1;
   }


   /**
    * Get links to Faq
    *
    * @param $withname boolean : also display name ?
   **/
   function getLinks($withname=false) {
      global $CFG_GLPI, $LANG;

      $ret = '';

      if ($withname) {
         $ret .= $this->fields["name"];
         $ret .= "&nbsp;&nbsp;";
      }

      if ($this->fields['knowbaseitemcategories_id']) {
         $title = $LANG['knowbase'][1];

         if (isset($_SESSION['glpiactiveprofile'])
             && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
            $title = $LANG['Menu'][19];
         }

         $ret .= "<a href='".$CFG_GLPI["root_doc"].
                   "/front/knowbaseitem.php?knowbaseitemcategories_id=".
                   $this->fields['knowbaseitemcategories_id']."'>".
                 "<img src='".$CFG_GLPI["root_doc"]."/pics/faqadd.png' class='middle'
                   alt=\"$title\" title=\"$title\"></a>";
      }
      return $ret;
   }


   function cleanDBonPurge() {
      Rule::cleanForItemCriteria($this);
   }

}

?>
