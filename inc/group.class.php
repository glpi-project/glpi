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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Group class
 */
class Group extends CommonDBTM {

   static function getTypeName() {
      global $LANG;

      return $LANG['common'][35];
   }


   function canCreate() {
      return haveRight('group', 'w');
   }


   function canView() {
      return haveRight('group', 'r');
   }


   function cleanDBonPurge() {
      global $DB;

      $query = "DELETE
                FROM `glpi_groups_users`
                WHERE `groups_id` = '".$this->fields['id']."'";
      $DB->query($query);
   }


   function post_getEmpty() {
      global $CFG_GLPI;
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();

      if ($this->fields['id'] > 0) {
         if (haveRight("user","r")) {
            $ong[1] = $LANG['Menu'][14];
         }
         $ong[2] = $LANG['common'][96];

         if (haveRight("config","r") && AuthLdap::useAuthLdap()) {
            $ong[3] = $LANG['setup'][3];
         }

      } else { // New item
         $ong[1] = $LANG['title'][26];
      }
      return $ong;
   }


   /**
   * Print the group form
   *
   * @param $ID integer ID of the item
   * @param $options array
   *     - target filename : where to go when done.
   *     - withtemplate boolean : template or basic item
   *
   * @return Nothing (display)
   **/
   function showForm ($ID, $options=array()) {
      global $LANG;

      if (!haveRight("group", "r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID, 'r');
      } else {
         // Create item
         $this->check(-1, 'w');
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='3' class='middle right'>".$LANG['common'][25]."&nbsp;:&nbsp;</td>";
      echo "<td class='center middle' rowspan='3'>";
      echo "<textarea cols='45' rows='3' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][64]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      // Manager must be in the same entity
      User::dropdown(array('value'  => $this->fields["users_id"],
                           'right'  => 'all',
                           'entity' => $this->fields["entities_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";

      if (!$ID) {
         $template = "newtemplate";
         echo "<td>".$LANG['computers'][14]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         echo convDateTime($_SESSION["glpi_currenttime"]);

      } else {
         echo "<td>".$LANG['common'][26]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         echo  convDateTime($this->fields["date_mod"]);
      }

      echo "</td></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   /**
    * Print a good title for group pages
    *
    *@return nothing (display)
    **/
   function title() {
      global $LANG, $CFG_GLPI;

      $buttons = array ();
      if (haveRight("group", "w") && haveRight("user_authtype", "w") && AuthLdap::useAuthLdap()) {
         $buttons["ldap.group.php"] = $LANG['setup'][3];
         $title = "";

      } else {
         $title = $LANG['Menu'][36];
      }

      displayTitle($CFG_GLPI["root_doc"] . "/pics/groupes.png", $LANG['Menu'][36], $title, $buttons);
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = $LANG['common'][25];
      $tab[16]['datatype'] = 'text';

      if (AuthLdap::useAuthLdap()) {

         $tab[3]['table'] = $this->getTable();
         $tab[3]['field'] = 'ldap_field';
         $tab[3]['name']  = $LANG['setup'][260];

         $tab[4]['table'] = $this->getTable();
         $tab[4]['field'] = 'ldap_value';
         $tab[4]['name']  = $LANG['setup'][601];

         $tab[5]['table'] = $this->getTable();
         $tab[5]['field'] = 'ldap_group_dn';
         $tab[5]['name']  = $LANG['setup'][261];
      }

      $tab[6]['table']    = $this->getTable();
      $tab[6]['field']    = 'is_recursive';
      $tab[6]['name']     = $LANG['entity'][9];
      $tab[6]['datatype'] = 'bool';

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'date_mod';
      $tab[19]['name']          = $LANG['common'][26];
      $tab[19]['datatype']      = 'datetime';
      $tab[19]['massiveaction'] = false;

      $tab[80]['table']         = 'glpi_entities';
      $tab[80]['field']         = 'completename';
      $tab[80]['name']          = $LANG['entity'][0];
      $tab[80]['massiveaction'] = false;

      return $tab;
   }


   function showLDAPForm ($target,$ID) {
      global $LANG;

      if (!haveRight("group", "r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID, 'r');
      } else {
         // Create item
         $this->check(-1, 'w');
      }

      echo "<form name='groupldap_form' id='groupldap_form' method='post' action='$target'>";
      echo "<div class='spaced'><table class='tab_cadre_fixe'>";

      if (haveRight("config","r") && AuthLdap::useAuthLdap()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>".$LANG['setup'][256]."&nbsp;:&nbsp;</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['setup'][260]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         autocompletionTextField($this, "ldap_field");
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['setup'][601]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         autocompletionTextField($this, "ldap_value");
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>".$LANG['setup'][257]."&nbsp;:&nbsp;</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['setup'][261]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         autocompletionTextField($this, "ldap_group_dn");
         echo "</td></tr>";
      }

      $options = array('colspan' => 1,
                       'candel'  => false);
      $this->showFormButtons($options);

      echo "</table></div></form>";
   }


   /**
    * Show items for the group
    */
   function showItems() {
      global $DB, $CFG_GLPI, $LANG;

      $ID = $this->fields['id'];

      echo "<div class='spaced'>";
      echo "<form name='group_form' id='group_form' method='post' action='".$this->getFormURL()."'>";
      echo "<table class='tab_cadre_fixe'><tr><th width='10'>&nbsp</th>";
      echo "<th>".$LANG['common'][17]."</th>";
      echo "<th>".$LANG['common'][16]."</th><th>".$LANG['entity'][0]."</th></tr>";

      foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
         if (!class_exists($itemtype)) {
            continue;
         }
         $item = new $itemtype();
         $query = "SELECT *
                   FROM `".$item->getTable()."`
                   WHERE `groups_id` = '$ID'".
                         getEntitiesRestrictRequest(" AND ", getTableForItemType($itemtype), '', '',
                                                     $item->maybeRecursive());
         $result = $DB->query($query);

         if ($DB->numrows($result)>0) {
            $type_name = $item->getTypeName();
            $cansee    = $item->canView();
            $canedit   = $item->canUpdate();

            while ($data=$DB->fetch_array($result)) {
               echo "<tr class='tab_bg_1'><td>";
               if ($canedit) {
                  echo "<input type='checkbox' name='item[$itemtype][".$data["id"]."]' value='1'>";
               }
               $link = ($data["name"] ? $data["name"] : "(".$data["id"].")");

               if ($cansee) {
                  $link = "<a href='".$item->getFormURL()."?id=". $data["id"]."'>".$link."</a>";
               }

               echo "</td><td>$type_name</td><td>$link</td>";
               echo "<td>".Dropdown::getDropdownName("glpi_entities", $data['entities_id']);
               echo "</td></tr>";
            }
         }
      }
      echo "</table>";

      openArrowMassive("group_form", true);
      echo $LANG['common'][35]."&nbsp;:&nbsp;";
      Dropdown::show('Group', array('entity' => $this->fields["entities_id"],
                                    'used'   => array($this->fields["id"])));
      echo "&nbsp;";
      closeArrowMassive('changegroup', $LANG['buttons'][14]);

      echo "</form></div>";
   }

}

?>
