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

   /**
    * Constructor
   **/
   function __construct() {
      $this->table="glpi_groups";
      $this->type=GROUP_TYPE;
      $this->entity_assign=true;
      $this->may_be_recursive=true;
   }

   function cleanDBonPurge($ID) {
      global $DB,$CFG_GLPI,$LINK_ID_TABLE;

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

      $this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);
      $this->showFormHeader($target,$ID,$withtemplate,2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],40,
                              $this->fields["entities_id"]);
      echo "</td>";
      if (useAuthLdap()) {
         echo "<td rowspan='7' class='middle right'>".$LANG['common'][25]."&nbsp;: </td>";
         echo "<td class='center middle' rowspan='7'>.<textarea cols='45' rows='9' name='comment' >".
                  $this->fields["comment"]."</textarea>";
      } else {
         echo "<td rowspan='2' class='middle right'>".$LANG['common'][25]."&nbsp;: </td>";
         echo "<td class='center middle' rowspan='2'>.<textarea cols='45' rows='3' name='comment' >".
                  $this->fields["comment"]."</textarea>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][64]."&nbsp;:</td>";
      echo "<td>";
      // Manager must be in the same entity
      // TODO for a recursive group the manager need to have a recursive right ?
      dropdownUsers('users_id',$this->fields["users_id"],'all',0,1,$this->fields["entities_id"]);
      echo "</td></tr>";

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
}

?>
