<?php
/*
 * @version $Id$
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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/**
 * Group class
 */
class Group extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_groups';
   public $type = 'Group';
   public $may_be_recursive=true;
   public $entity_assign=true;

   static function getTypeName() {
      global $LANG;

      return $LANG['common'][35];
   }

   static function canCreate() {
      return haveRight('group', 'w');
   }

   static function canView() {
      return haveRight('group', 'r');
   }

   function cleanDBonPurge($ID) {
      global $DB,$CFG_GLPI;

      $query = "DELETE
                FROM `glpi_groups_users`
                WHERE `groups_id` = '$ID'";
      $DB->query($query);
   }

   function post_getEmpty() {
      global $CFG_GLPI;
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=array();

      if ($ID>0) {
         if (haveRight("user","r")) {
            $ong[1]=$LANG['Menu'][14];
         }
         $ong[2]=$LANG['common'][1];
         if (haveRight("config","r") && useAuthLdap()) {
            $ong[3]=$LANG['setup'][3];
         }
      } else { // New item
         $ong[1]=$LANG['title'][26];
      }
      return $ong;
   }

   /**
    * Print the group form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the contact to print
    *@param $withtemplate='' boolean : template or basic item
    *
    *@return Nothing (display)
    *
    **/
   function showForm ($target,$ID,$withtemplate='') {
      global $CFG_GLPI, $LANG;

      if (!haveRight("group","r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $this->showTabs($ID, $withtemplate,getActiveTab($this->type));
      $this->showFormHeader($target,$ID,$withtemplate,2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],40,
                              $this->fields["entities_id"]);
      echo "</td>";
      echo "<td rowspan='3' class='middle right'>".$LANG['common'][25]."&nbsp;: </td>";
      echo "<td class='center middle' rowspan='3'>.<textarea cols='45' rows='3' name='comment' >".
               $this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][64]."&nbsp;:</td>";
      echo "<td>";
      // Manager must be in the same entity
      // TODO for a recursive group the manager need to have a recursive right ?
      User::dropdown('users_id',
                     array('value'=>$this->fields["users_id"],
                        'right'=>'all',
                        'entity'=>$this->fields["entities_id"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      if (!$ID) {
         $template = "newtemplate";
         echo "<td>".$LANG['computers'][14]."&nbsp;:</td>";
         echo "<td>";
         echo convDateTime($_SESSION["glpi_currenttime"]);
      } else {
         echo "<td>".$LANG['common'][26]."&nbsp;:</td>";
         echo "<td>";
         echo  convDateTime($this->fields["date_mod"]);
      }

      echo "</td></tr>";

      $this->showFormButtons($ID,$withtemplate,2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

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
      if (haveRight("group", "w") && haveRight("user_authtype", "w") && useAuthLdap()) {
         $buttons["ldap.group.php"] = $LANG['setup'][3];
         $title="";
      } else {
         $title = $LANG['Menu'][36];
      }
      displayTitle($CFG_GLPI["root_doc"] . "/pics/groupes.png", $LANG['Menu'][36], $title, $buttons);
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = 'glpi_groups';
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = 'Group';

      $tab[2]['table']     = 'glpi_groups';
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[16]['table']     = 'glpi_groups';
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[3]['table']     = 'glpi_groups';
      $tab[3]['field']     = 'ldap_field';
      $tab[3]['linkfield'] = 'ldap_field';
      $tab[3]['name']      = $LANG['setup'][260];

      $tab[4]['table']     = 'glpi_groups';
      $tab[4]['field']     = 'ldap_value';
      $tab[4]['linkfield'] = 'ldap_value';
      $tab[4]['name']      = $LANG['setup'][601];

      $tab[5]['table']     = 'glpi_groups';
      $tab[5]['field']     = 'ldap_group_dn';
      $tab[5]['linkfield'] = 'ldap_group_dn';
      $tab[5]['name']      = $LANG['setup'][261];

      $tab[6]['table']     = 'glpi_groups';
      $tab[6]['field']     = 'is_recursive';
      $tab[6]['linkfield'] = 'is_recursive';
      $tab[6]['name']      = $LANG['entity'][9];
      $tab[6]['datatype']  = 'bool';

      $tab[19]['table']     = 'glpi_groups';
      $tab[19]['field']     = 'date_mod';
      $tab[19]['linkfield'] = '';
      $tab[19]['name']      = $LANG['common'][26];
      $tab[19]['datatype']  = 'datetime';

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      return $tab;
   }


   function showLDAPForm ($target,$ID) {
      global $CFG_GLPI, $LANG;

      if (!haveRight("group","r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }

      echo "<form name='groupldap_form' id='groupldap_form' method='post' action=\"$target\">";
      echo "<div class='center'><table class='tab_cadre_fixe'>";

      if (useAuthLdap()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>".$LANG['setup'][256]."&nbsp;:</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['setup'][260]."&nbsp;:</td>";
         echo "<td>";
         autocompletionTextField("ldap_field",$this->table,"ldap_field",
                                 $this->fields["ldap_field"],40,$this->fields["entities_id"]);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['setup'][601]."&nbsp;:</td>";
         echo "<td>";
         autocompletionTextField("ldap_value",$this->table,"ldap_value",
                                 $this->fields["ldap_value"],40,$this->fields["entities_id"]);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td colspan='2' class='center'>".$LANG['setup'][257]."&nbsp;:</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['setup'][261]."&nbsp;:</td>";
         echo "<td>";
         autocompletionTextField("ldap_group_dn",$this->table,"ldap_group_dn",
                                 $this->fields["ldap_group_dn"],40,$this->fields["entities_id"]);
         echo "</td></tr>";
      }

      $this->showFormButtons($ID,'',2, false);

      echo "</table></div></form>";
   }

   /**
    * Show items for the group
    *
    */
   function showItems() {
      global $DB,$CFG_GLPI, $LANG,$INFOFORM_PAGES;

      $ID = $this->fields['id'];

      echo "<div class='center'><table class='tab_cadre_fixe'><tr><th>".$LANG['common'][17]."</th>";
      echo "<th>".$LANG['common'][16]."</th><th>".$LANG['entity'][0]."</th></tr>";
      foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
         if (!class_exists($itemtype)) {
            continue;
         }
         $item = new $itemtype();
         $query="SELECT *
                 FROM ".$item->table."
                 WHERE `groups_id`='$ID' " .
                       getEntitiesRestrictRequest(" AND ", getTableForItemType($itemtype), '', '',
                                                  isset($CFG_GLPI["recursive_type"][$itemtype]));
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            $type_name = $item->getTypeName();
            $cansee = $item->canView();
            while ($data=$DB->fetch_array($result)) {
               $link=($data["name"] ? $data["name"] : "(".$data["id"].")");
               if ($cansee) {
                  $link="<a href='".$item->getFormURL()."?id=".
                           $data["id"]."'>".$link."</a>";
               }
               $linktype="";
               echo "<tr class='tab_bg_1'><td>$type_name</td><td>$link</td>";
               echo "<td>".Dropdown::getDropdownName("glpi_entities",$data['entities_id'])."</td></tr>";
            }
         }
      }
      echo "</table></div>";
   }
}

?>
