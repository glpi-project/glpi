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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}




/**  Show items of a user
* @param $ID user ID
*/
function showDeviceUser($ID) {
   global $DB,$CFG_GLPI, $LANG;

   $group_where = "";
   $groups = array();
   $query = "SELECT `glpi_groups_users`.`groups_id`, `glpi_groups`.`name`
             FROM `glpi_groups_users`
             LEFT JOIN `glpi_groups` ON (`glpi_groups`.`id` = `glpi_groups_users`.`groups_id`)
             WHERE `glpi_groups_users`.`users_id` = '$ID'";
   $result=$DB->query($query);

   if ($DB->numrows($result) >0) {
      $first = true;
      while ($data=$DB->fetch_array($result)) {
         if ($first) {
            $first = false;
         } else {
            $group_where .= " OR ";
         }
         $group_where .= " `groups_id` = '".$data["groups_id"]."' ";
         $groups[$data["groups_id"]] = $data["name"];
      }
   }

   echo "<div class='center'><table class='tab_cadre_fixe'><tr><th>".$LANG['common'][17]."</th>".
         "<th>".$LANG['entity'][0]."</th>".
         "<th>".$LANG['common'][16]."</th>".
         "<th>".$LANG['common'][19]."</th>".
         "<th>".$LANG['common'][20]."</th><th>&nbsp;</th></tr>";

   foreach ($CFG_GLPI["linkuser_types"] as $itemtype) {
      if (!class_exists($itemtype)) {
         continue;
      }
      $item = new $itemtype();
      if ($item->canView()) {
         $itemtable=getTableForItemType($itemtype);
         $query = "SELECT *
                   FROM `$itemtable`
                   WHERE `users_id` = '$ID'";

         if ($item->maybeTemplate()) {
            $query .= " AND `is_template` = '0' ";
         }
         if ($item->maybeDeleted()) {
            $query .= " AND `is_deleted` = '0' ";
         }
         $result = $DB->query($query);

         $type_name= $item->getTypeName();

         if ($DB->numrows($result) >0) {
            while ($data = $DB->fetch_array($result)) {
               $cansee = $item->can($data["id"],"r");
               $link = $data["name"];
               if ($cansee) {
                  $link_item=getItemTypeFormURL($itemtype);
                  $link = "<a href='".$link_item."?id=".
                           $data["id"]."'>".$link.
                           (($_SESSION["glpiis_ids_visible"]||empty($link))?" (".$data["id"].")":"")
                           ."</a>";
               }
               $linktype = "";
               if ($data["users_id"] == $ID) {
                  $linktype = $LANG['common'][34];
               }
               echo "<tr class='tab_bg_1'><td class='center'>$type_name</td>".
                     "<td class='center'>".Dropdown::getDropdownName("glpi_entities",$data["entities_id"]).
                     "</td><td class='center'>$link</td>".
                     "<td class='center'>";
               if (isset($data["serial"]) && !empty($data["serial"])) {
                  echo $data["serial"];
               } else {
                  echo '&nbsp;';
               }
               echo "</td><td class='center'>";
               if (isset($data["otherserial"]) && !empty($data["otherserial"])) {
                  echo $data["otherserial"];
               } else {
                  echo '&nbsp;';
               }
               echo "<td class='center'>$linktype</td></tr>";
            }
         }
      }
   }
   echo "</table></div><br>";

   if (!empty($group_where)) {
      echo "<div class='center'><table class='tab_cadre_fixe'><tr><th>".$LANG['common'][17]."</th>".
            "<th>".$LANG['entity'][0]."</th>".
            "<th>".$LANG['common'][16]."</th>".
            "<th>".$LANG['common'][19]."</th>".
            "<th>".$LANG['common'][20]."</th><th>&nbsp;</th></tr>";

      foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
         if (!class_exists($itemtype)) {
            continue;
         }
         $item = new $itemtype();
         if ($item->canView()) {

            $itemtable=getTableForItemType($itemtype);
            $query = "SELECT *
                     FROM `$itemtable`
                     WHERE $group_where";

            if ($item->maybeTemplate()) {
               $query .= " AND `is_template` = '0' ";
            }
            if ($item->maybeDeleted()) {
               $query .= " AND `is_deleted` = '0' ";
            }
            $result = $DB->query($query);

            $type_name= $item->getTypeName();


            if ($DB->numrows($result) >0) {
               while ($data = $DB->fetch_array($result)) {
                  $cansee = $item->can($data["id"],"r");
                  $link = $data["name"];
                  if ($cansee) {
                     $link_item=getItemTypeFormURL($itemtype);
                     $link = "<a href='".$link_item."?id=".
                              $data["id"]."'>".$link.
                              (($_SESSION["glpiis_ids_visible"] || empty($link))?" (".$data["id"].")":"").
                              "</a>";
                  }
                  $linktype = "";
                  if (isset($groups[$data["groups_id"]])) {
                     $linktype = $LANG['common'][35]." ".$groups[$data["groups_id"]];
                  }
                  echo "<tr class='tab_bg_1'><td class='center'>$type_name</td>".
                        "<td class='center'>".Dropdown::getDropdownName("glpi_entities",$data["entities_id"]).
                        "</td><td class='center'>$link</td>".
                        "<td class='center'>";
                  if (isset($data["serial"]) && !empty($data["serial"])) {
                     echo $data["serial"];
                  } else {
                     echo '&nbsp;';
                  }
                  echo "</td><td class='center'>";
                  if (isset($data["otherserial"]) && !empty($data["otherserial"])) {
                     echo $data["otherserial"];
                  } else {
                     echo '&nbsp;';
                  }
                  echo "</td><td class='center'>$linktype</td></tr>";
               }
            }
         }
      }
      echo "</table></div><br>";
   }
}




/**  Generate vcard for an user
* @param $ID user ID
*/
function generateUserVcard($ID) {

   include_once (GLPI_ROOT . "/lib/vcardclass/classes-vcard.php");

   $user = new User;
   $user->getFromDB($ID);

   // build the Vcard
   $vcard = new vCard();

   if (!empty($user->fields["realname"]) || !empty($user->fields["firstname"])) {
      $vcard->setName($user->fields["realname"], $user->fields["firstname"], "", "");
   } else {
      $vcard->setName($user->fields["name"], "", "", "");
   }

   $vcard->setPhoneNumber($user->fields["phone"], "PREF;WORK;VOICE");
   $vcard->setPhoneNumber($user->fields["phone2"], "HOME;VOICE");
   $vcard->setPhoneNumber($user->fields["mobile"], "WORK;CELL");

   $vcard->setEmail($user->fields["email"]);

   $vcard->setNote($user->fields["comment"]);

   // send the  VCard
   $output = $vcard->getVCard();
   $filename = $vcard->getFileName();      // "xxx xxx.vcf"

   @Header("Content-Disposition: attachment; filename=\"$filename\"");
   @Header("Content-Length: ".utf8_strlen($output));
   @Header("Connection: close");
   @Header("content-type: text/x-vcard; charset=UTF-8");

   echo $output;
}


/**  Get entities for which a user have a right
* @param $ID user ID
* @param $is_recursive check also using recurisve rights
*/
function getUserEntities($ID,$is_recursive=true) {
   global $DB;

   $query = "SELECT DISTINCT `entities_id`, `is_recursive`
             FROM `glpi_profiles_users`
             WHERE `users_id` = '$ID'";
   $result=$DB->query($query);

   if ($DB->numrows($result) >0) {
      $entities = array();
      while ($data = $DB->fetch_assoc($result)) {
         if ($data['is_recursive'] && $is_recursive) {
            $tab = getSonsOf('glpi_entities',$data['entities_id']);
            $entities = array_merge($tab,$entities);
         } else {
            $entities[] = $data['entities_id'];
         }
      }
      return array_unique($entities);
   }
   return array();
}


/** Get all the authentication methods parameters for a specific authtype
 *  and auths_id and return it as an array
* @param $authtype Authentication method
* @param $auths_id Authentication method ID
*/
function getAuthMethodsByID($authtype, $auths_id) {
   global $DB;

   $authtypes = array ();
   $sql = "";

   switch ($authtype) {
      case AUTH_X509 :
      case AUTH_EXTERNAL :
      case AUTH_CAS :
      case AUTH_LDAP :
         if ($auths_id >0) {
            //Get all the ldap directories
            $sql = "SELECT *
                    FROM `glpi_authldaps`
                    WHERE `id` = '$auths_id'";
         }
         break;

      case AUTH_MAIL :
         //Get all the pop/imap servers
         $sql = "SELECT *
                 FROM `glpi_authmails`
                 WHERE `id` = '$auths_id'";
         break;
   }

   if ($sql != "") {
      $result = $DB->query($sql);
      if ($DB->numrows($result) > 0) {
         $authtypes = $DB->fetch_array($result);
      }
   }
   //Return all the authentication methods in an array
   return $authtypes;
}



/** Get LDAP fields to sync to GLPI data from a glpi_authldaps array
* @param $authtype_array Authentication method config array
*/
function getLDAPSyncFields($authtype_array) {

   $ret = array();

   $fields = array('login_field'     => 'name',
                   'email_field'     => 'email',
                   'realname_field'  => 'realname',
                   'firstname_field' => 'firstname',
                   'phone_field'     => 'phone',
                   'phone2_field'    => 'phone2',
                   'mobile_field'    => 'mobile',
                   'comment_field'   => 'comment',
                   'title_field'     => 'usertitles_id',
                   'category_field'  => 'usercategories_id',
                   'language_field'  => 'language');

   foreach ($fields as $key => $val) {
      if (isset($authtype_array[$key])) {
         $ret[$val] = $authtype_array[$key];
      }
   }
   return $ret;
}


/**
 * Get language in GLPI associated with the value coming from LDAP
 * Value can be, for example : English, en_EN or en
 * @param $lang : the value coming from LDAP
 * @return the locale's php page in GLPI or '' is no language associated with the value
 */
function getUserLanguage($lang) {
   global $CFG_GLPI;

   /// TODO Add fields in config array to be more efficient / use stricmp instead of == for strings

   foreach ($CFG_GLPI["languages"] as $ID => $language) {
      if ($lang == $ID
          || $lang == $language[0]
          || $lang == $language[2]
          || $lang == $language[3]) {
         return $ID;
      }
   }
   return "";
}


function changeUserAuthMethod($IDs=array(), $authtype=1 ,$server=-1) {
   global $DB;

   if (!empty($IDs) && in_array($authtype, array(AUTH_DB_GLPI,
                                                 AUTH_LDAP,
                                                 AUTH_MAIL,
                                                 AUTH_EXTERNAL))) {
      $where = implode(',',$IDs);
      $query = "UPDATE
                `glpi_users`
                SET `authtype` = '$authtype', `auths_id` = '$server'
                WHERE `id` IN $where";
      $DB->query($query);
   }
}

?>