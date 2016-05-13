<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * ITILCategory class
**/
class ITILCategory extends CommonTreeDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'itilcategory';



   function getAdditionalFields() {

      $tab = array(array('name'      => $this->getForeignKeyField(),
                         'label'     => __('As child of'),
                         'type'      => 'parent',
                         'list'      => false),
                   array('name'      => 'users_id',
                         'label'     => __('Technician in charge of the hardware'),
                         'type'      => 'UserDropdown',
                         'right'     => 'own_ticket',
                         'list'      => true),
                   array('name'      => 'groups_id',
                         'label'     => __('Group in charge of the hardware'),
                         'type'      => 'dropdownValue',
                         'condition' => '`is_assign`',
                         'list'      => true),
                   array('name'      => 'knowbaseitemcategories_id',
                         'label'     => __('Knowledge base'),
                         'type'      => 'dropdownValue',
                         'list'      => true),
                   array('name'      => 'is_helpdeskvisible',
                         'label'     => __('Visible in the simplified interface'),
                         'type'      => 'bool',
                         'list'      => true),
                   array('name'      => 'is_incident',
                         'label'     => __('Visible for an incident'),
                         'type'      => 'bool',
                         'list'      => true),
                   array('name'      => 'is_request',
                         'label'     => __('Visible for a request'),
                         'type'      => 'bool',
                         'list'      => true),
                   array('name'      => 'is_problem',
                         'label'     => __('Visible for a problem'),
                         'type'      => 'bool',
                         'list'      => true),
                   array('name'      => 'is_change',
                         'label'     => __('Visible for a change'),
                         'type'      => 'bool',
                         'list'      => true),
                   array('name'      => 'tickettemplates_id_demand',
                         'label'     => __('Template for a request'),
                         'type'      => 'dropdownValue',
                         'list'      => true),
                   array('name'      => 'tickettemplates_id_incident',
                         'label'     => __('Template for an incident'),
                         'type'      => 'dropdownValue',
                         'list'      => true),
                  );

      if (!Session::haveRightsOr('problem', array(CREATE, UPDATE, DELETE,
                                                  Problem::READALL, Problem::READMY))) {

         unset($tab[7]);
      }
      return $tab;

   }


   function getSearchOptions() {

      $tab                       = parent::getSearchOptions();

      $tab[70]['table']          = 'glpi_users';
      $tab[70]['field']          = 'name';
      $tab[70]['name']           = __('Technician in charge of the hardware');
      $tab[70]['datatype']       = 'dropdown';
      $tab[70]['right']          = 'own_ticket';

      $tab[71]['table']          = 'glpi_groups';
      $tab[71]['field']          = 'completename';
      $tab[71]['name']           = __('Group');
      $tab[71]['datatype']       = 'dropdown';

      $tab[72]['table']          = 'glpi_tickettemplates';
      $tab[72]['field']          = 'name';
      $tab[72]['linkfield']      = 'tickettemplates_id_demand';
      $tab[72]['name']           = __('Template for a request');
      $tab[72]['datatype']       = 'dropdown';

      $tab[73]['table']          = 'glpi_tickettemplates';
      $tab[73]['field']          = 'name';
      $tab[73]['linkfield']      = 'tickettemplates_id_incident';
      $tab[73]['name']           = __('Template for an incident');
      $tab[73]['datatype']       = 'dropdown';

      $tab[74]['table']          = $this->getTable();
      $tab[74]['field']          = 'is_incident';
      $tab[74]['name']           = __('Visible for an incident');
      $tab[74]['datatype']       = 'bool';

      $tab[75]['table']          = $this->getTable();
      $tab[75]['field']          = 'is_request';
      $tab[75]['name']           = __('Visible for a request');
      $tab[75]['datatype']       = 'bool';

      $tab[76]['table']          = $this->getTable();
      $tab[76]['field']          = 'is_problem';
      $tab[76]['name']           = __('Visible for a problem');
      $tab[76]['datatype']       = 'bool';

      $tab[85]['table']          = $this->getTable();
      $tab[85]['field']          = 'is_change';
      $tab[85]['name']           = __('Visible for a change');
      $tab[85]['datatype']       = 'bool';

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'is_helpdeskvisible';
      $tab[3]['name']            = __('Visible in the simplified interface');
      $tab[3]['datatype']        = 'bool';

      $tab[77]['table']          = 'glpi_tickets';
      $tab[77]['field']          = 'id';
      $tab[77]['name']           = _x('quantity', 'Number of tickets');
      $tab[77]['datatype']       = 'count';
      $tab[77]['forcegroupby']   = true;
      $tab[77]['massiveaction']  = false;
      $tab[77]['joinparams']     = array('jointype' => 'child');

      $tab[78]['table']          = 'glpi_problems';
      $tab[78]['field']          = 'id';
      $tab[78]['name']           = _x('quantity', 'Number of problems');
      $tab[78]['datatype']       = 'count';
      $tab[78]['forcegroupby']   = true;
      $tab[78]['massiveaction']  = false;
      $tab[78]['joinparams']     = array('jointype' => 'child');

      $tab[98]['table']          = 'glpi_changes';
      $tab[98]['field']          = 'id';
      $tab[98]['name']           = _x('quantity', 'Number of changes');
      $tab[98]['datatype']       = 'count';
      $tab[98]['forcegroupby']   = true;
      $tab[98]['massiveaction']  = false;
      $tab[98]['joinparams']     = array('jointype' => 'child');

      $tab[79]['table']          = 'glpi_knowbaseitemcategories';
      $tab[79]['field']          = 'completename';
      $tab[79]['name']           = __('Knowledge base');
      $tab[79]['datatype']       = 'dropdown';

      return $tab;
   }


   static function getTypeName($nb=0) {
      return _n('Ticket category', 'Ticket categories', $nb);
   }


   function post_getEmpty() {

      $this->fields['is_helpdeskvisible'] = 1;
      $this->fields['is_request']         = 1;
      $this->fields['is_incident']        = 1;
      $this->fields['is_problem']         = 1;
      $this->fields['is_change']          = 1;
   }


   /**
    * Get links to Faq
    *
    * @param $withname  boolean  also display name ? (false by default)
   **/
   function getLinks($withname=false) {
      global $CFG_GLPI;

      $ret = '';

      if ($withname) {
         $ret .= $this->fields["name"];
         $ret .= "&nbsp;&nbsp;";
      }

      if ($this->fields['knowbaseitemcategories_id']) {
         $title = __('FAQ');

         if (isset($_SESSION['glpiactiveprofile'])
             && ($_SESSION['glpiactiveprofile']['interface'] == 'central')) {
            $title = __('Knowledge base');
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


   /**
    * @since version 0.84
    *
    * @param $item         CommonGLPI object
    * @param $withtemplate (default 0)
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (Session::haveRight(self::$rightname, READ)) {
         switch ($item->getType()) {
            case 'TicketTemplate' :
               $ong[1] = $this->getTypeName(Session::getPluralNumber());
               return $ong;
         }
      }
      return parent::getTabNameForItem($item, $withtemplate);
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == 'TicketTemplate') {
         self::showForTicketTemplate($item, $withtemplate);
      }
      return parent::displayTabContentForItem($item, $tabnum, $withtemplate);
   }


   /**
    * @param $tt           TicketTemplate object
    * @param $withtemplate (default '')
   **/
   static function showForTicketTemplate(TicketTemplate $tt, $withtemplate='') {
      global $DB, $CFG_GLPI;

      $itilcategory = new self();
      $ID           = $tt->fields['id'];

      if (!$tt->getFromDB($ID)
          || !$tt->can($ID, READ)) {
         return false;
      }
      $ttm  = new self();
      $rand = mt_rand();

      echo "<div class='center'>";

      $query = "SELECT `glpi_itilcategories`.*
                FROM `glpi_itilcategories`
                WHERE (`tickettemplates_id_incident` = '$ID')
                       OR (`tickettemplates_id_demand` = '$ID')
                ORDER BY `name`";

      if ($result=$DB->query($query)) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='3'>";
         echo "<a href='".Toolbox::getItemTypeSearchURL($itilcategory->getType())."'>";
         echo self::getTypeName($DB->numrows($result));
         echo "</a>";
         echo "</th></tr>";
         $used_incident = array();
         $used_demand   = array();
         if ($DB->numrows($result)) {
            echo "<th>".__('Name')."</th>";
            echo "<th>".__('Incident')."</th>";
            echo "<th>".__('Request')."</th>";
            echo "</tr>";

            while ($data = $DB->fetch_assoc($result)) {
               echo "<tr class='tab_bg_2'>";
               $itilcategory->getFromDB($data['id']);
               echo "<td>".$itilcategory->getLink(array('comments' => true))."</td>";
               if ($data['tickettemplates_id_incident'] == $ID) {
                  echo "<td class='center'>
                        <img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' alt=\"".__('OK').
                         "\" width='14' height='14'>
                        </td>";
                  $used_incident[] = $data["id"];
               } else {
                  echo "<td>&nbsp;</td>";
               }
               if ($data['tickettemplates_id_demand'] == $ID) {
                  echo "<td class='center'>
                        <img src='".$CFG_GLPI["root_doc"]."/pics/ok.png' alt=\"".__('OK').
                         "\" width='14' height='14'>
                        </td>";
                  $used_demand[] = $data["id"];
               } else {
                  echo "<td>&nbsp;</td>";
               }
            }

         } else {
            echo "<tr><th colspan='3'>".__('No item found')."</th></tr>";
         }

         echo "</table></div>";
      }
   }

}
