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

/**
 * Have I the right $right to module $module (conpare to session variable)
 *
 * @param $module Module to check
 * @param $right Right to check
 *
 * @return Boolean : session variable have more than the right specified for the module
**/
function haveRight($module, $right) {
   global $DB;

   //If GLPI is using the slave DB -> read only mode
   if ($DB->isSlave() && $right == "w") {
      return false;
   }

   $matches = array ("" => array ("","r","w"), // ne doit pas arriver normalement
                     "r" => array ("r","w"),
                     "w" => array ("w"),
                     "1" => array ("1"),
                     "0" => array ("0","1"), // ne doit pas arriver non plus
                     );

   if (isset ($_SESSION["glpiactiveprofile"][$module])
       && in_array($_SESSION["glpiactiveprofile"][$module], $matches[$right])) {
      return true;
   }
   return false;
}


// TODO keep for transition => to be removed ??
/**
 * Have I the right $right to module type $itemtype (conpare to session variable)
 *
 * @param $right Right to check
 * @param $itemtype Type to check
 *
 * @return Boolean : session variable have more than the right specified for the module type
**/
function haveTypeRight($itemtype, $right) {
   global $LANG,$PLUGIN_HOOKS,$CFG_GLPI;

   if ($right='w') {
      $method = array($itemtype,'canCreate');
   } else {
      $method = array($itemtype,'canView');
   }
   $item=new $itemtype();
   if (method_exists($item,$method[1])) {
      return $item->$method[1]();
      return (call_user_func($method));
   }
   return false;
}

/**
 * Display common message for privileges errors
 *
 * @return Nothing (die)
**/
function displayRightError() {
   global $LANG;

   displayErrorAndDie($LANG['common'][83]);
}

/**
 * Display common message for item not found
 *
 * @return Nothing
**/
function displayNotFoundError() {
   global $LANG, $CFG_GLPI, $HEADER_LOADED;

   if (!$HEADER_LOADED) {
      if (!isset ($_SESSION["glpiactiveprofile"]["interface"])) {
         nullHeader($LANG['login'][5], $_SERVER['PHP_SELF']);
      } else if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
         commonHeader($LANG['login'][5], $_SERVER['PHP_SELF']);
      } else if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
         helpHeader($LANG['login'][5], $_SERVER['PHP_SELF']);
      }
   }
   echo "<div class='center'><br><br>";
   echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='warning'><br><br>";
   echo "<strong>" . $LANG['common'][54] . "</strong></div>";
   nullFooter();
   exit ();
}

/**
 * Check if I have the right $right to module $module (conpare to session variable)
 *
 * @param $module Module to check
 * @param $right Right to check
 *
 * @return Nothing : display error if not permit
**/
function checkRight($module, $right) {
   global $CFG_GLPI;

   if (!haveRight($module, $right)) {
      // Gestion timeout session
      if (!isset ($_SESSION["glpiID"])) {
         glpi_header($CFG_GLPI["root_doc"] . "/index.php");
         exit ();
      }
      displayRightError();
   }
}

/**
 * Check if I have one of the right specified
 *
 * @param $modules array of modules where keys are modules and value are right
 *
 * @return Nothing : display error if not permit
**/
function checkSeveralRightsOr($modules) {
   global $CFG_GLPI;

   $valid = false;
   if (count($modules)) {
      foreach ($modules as $mod => $right) {
         if (is_numeric($mod)){
            if (haveTypeRight($mod, $right)) {
               $valid = true;
            }
         } else if (haveRight($mod, $right)) {
            $valid = true;
         }
      }
   }

   if (!$valid) {
      // Gestion timeout session
      if (!isset ($_SESSION["glpiID"])) {
         glpi_header($CFG_GLPI["root_doc"] . "/index.php");
         exit ();
      }
      displayRightError();
   }
}

/**
 * Check if I have all the rights specified
 *
 * @param $modules array of modules where keys are modules and value are right
 *
 * @return Nothing : display error if not permit
**/
function checkSeveralRightsAnd($modules) {
   global $CFG_GLPI;

   $valid = true;
   if (count($modules)) {
      foreach ($modules as $mod => $right) {
         if (is_numeric($mod)) {
            if (!haveTypeRight($mod, $right)) {
               $valid = false;
            }
         } else if (!haveRight($mod, $right)) {
            $valid = false;
         }
      }
   }

   if (!$valid) {
      // Gestion timeout session
      if (!isset ($_SESSION["glpiID"])) {
         glpi_header($CFG_GLPI["root_doc"] . "/index.php");
         exit ();
      }
      displayRightError();
   }
}
/**
 * Check if I have the right $right to module type $itemtype (conpare to session variable)
 *
 * @param $itemtype Module type to check
 * @param $right Right to check
 *
 * @return Nothing : display error if not permit
**/
function checkTypeRight($itemtype, $right) {
   global $CFG_GLPI;

   if (!haveTypeRight($itemtype, $right)) {
      // Gestion timeout session
      if (!isset ($_SESSION["glpiID"])) {
         glpi_header($CFG_GLPI["root_doc"] . "/index.php");
         exit ();
      }
      displayRightError();
   }
}
/**
 * Check if I have access to the central interface
 *
 * @return Nothing : display error if not permit
**/
function checkCentralAccess() {
   global $CFG_GLPI;

   if (!isset ($_SESSION["glpiactiveprofile"])
       || $_SESSION["glpiactiveprofile"]["interface"] != "central") {
      // Gestion timeout session
      if (!isset ($_SESSION["glpiID"])) {
         glpi_header($CFG_GLPI["root_doc"] . "/index.php");
         exit ();
      }
      displayRightError();
   }
}
/**
 * Check if I have access to the helpdesk interface
 *
 * @return Nothing : display error if not permit
**/
function checkHelpdeskAccess() {
   global $CFG_GLPI;

   if (!isset ($_SESSION["glpiactiveprofile"])
       || $_SESSION["glpiactiveprofile"]["interface"] != "helpdesk") {
      // Gestion timeout session
      if (!isset ($_SESSION["glpiID"])) {
         glpi_header($CFG_GLPI["root_doc"] . "/index.php");
         exit ();
      }
      displayRightError();
   }
}

/**
 * Check if I am logged in
 *
 * @return Nothing : display error if not permit
**/
function checkLoginUser() {
   global $CFG_GLPI;

   if (!isset ($_SESSION["glpiname"])) {
      // Gestion timeout session
      if (!isset ($_SESSION["glpiID"])) {
         glpi_header($CFG_GLPI["root_doc"] . "/index.php");
         exit ();
      }
      displayRightError();
   }
}

/**
 * Check if I have the right to access to the FAQ (profile or anonymous FAQ)
 *
 * @return Nothing : display error if not permit
**/
function checkFaqAccess() {
   global $CFG_GLPI;

   if ($CFG_GLPI["use_public_faq"] == 0 && !haveRight("faq", "r")) {
      displayRightError();
   }
}

/**
 * Include the good language dict.
 *
 * Get the default language from current user in $_SESSION["glpilanguage"].
 * And load the dict that correspond.
 * @param $forcelang Force to load a specific lang
 *
 * @return nothing (make an include)
 *
**/
function loadLanguage($forcelang='') {
   global $LANG, $CFG_GLPI;

   $file = "";

   if (!isset($_SESSION["glpilanguage"])) {
      if (isset($CFG_GLPI["language"])) {
         // Default config in GLPI >= 0.72
         $_SESSION["glpilanguage"]=$CFG_GLPI["language"];
      } else if (isset($CFG_GLPI["default_language"])) {
         // Default config in GLPI < 0.72 : keep it for upgrade process
         $_SESSION["glpilanguage"]=$CFG_GLPI["default_language"];
      }
   }
   $trytoload=$_SESSION["glpilanguage"];
   // Force to load a specific lang
   if (!empty($forcelang)) {
      $trytoload=$forcelang;
   }
   // If not set try default lang file
   if (empty($trytoload)) {
      $trytoload=$CFG_GLPI["language"];
   }

   if (isset ($CFG_GLPI["languages"][$trytoload][1])) {
      $file = "/locales/" . $CFG_GLPI["languages"][$trytoload][1];
   }

   if (empty ($file) || !is_file(GLPI_ROOT . $file)) {
      $trytoload='en_GB';
      $file = "/locales/en_GB.php";
   }

   include (GLPI_ROOT . $file);

   // Debug display lang element with item
   if ($_SESSION['glpi_use_mode']==TRANSLATION_MODE && $CFG_GLPI["debug_lang"]) {
      foreach ($LANG as $module => $tab) {
         foreach ($tab as $num => $val) {
            $LANG[$module][$num] = "".$LANG[$module][$num].
                                   "/<span style='font-size:12px; color:red;'>$module/$num</span>";
         }
      }
   }
   return $trytoload;
}

/**
 * Set the entities session variable. Load all entities from DB
 *
 * @param $userID : ID of the user
 * @return Nothing
**/
function initEntityProfiles($userID) {
   global $DB;

   $query = "SELECT DISTINCT `glpi_profiles`.*
             FROM `glpi_profiles_users`
             INNER JOIN `glpi_profiles` ON (`glpi_profiles_users`.`profiles_id`=`glpi_profiles`.`id`)
             WHERE `glpi_profiles_users`.`users_id`='$userID'
             ORDER BY `glpi_profiles`.`name`";
   $result = $DB->query($query);
   $_SESSION['glpiprofiles'] = array ();
   if ($DB->numrows($result)) {
      while ($data = $DB->fetch_assoc($result)) {
         $_SESSION['glpiprofiles'][$data['id']]['name'] = $data['name'];
      }
      foreach ($_SESSION['glpiprofiles'] as $key => $tab) {
         $query2 = "SELECT `glpi_profiles_users`.`entities_id` AS eID,
                           `glpi_profiles_users`.`id` AS kID,
                           `glpi_profiles_users`.`is_recursive`, `glpi_entities`.*
                    FROM `glpi_profiles_users`
                    LEFT JOIN `glpi_entities`
                             ON (`glpi_profiles_users`.`entities_id` = `glpi_entities`.`id`)
                    WHERE `glpi_profiles_users`.`profiles_id`='$key'
                          AND `glpi_profiles_users`.`users_id`='$userID'
                    ORDER BY `glpi_entities`.`completename`";
         $result2 = $DB->query($query2);
         if ($DB->numrows($result2)) {
            while ($data = $DB->fetch_array($result2)) {
               $_SESSION['glpiprofiles'][$key]['entities'][$data['kID']]['id'] = $data['eID'];
               $_SESSION['glpiprofiles'][$key]['entities'][$data['kID']]['name'] = $data['name'];
               $_SESSION['glpiprofiles'][$key]['entities'][$data['kID']]['is_recursive']
                 = $data['is_recursive'];
            }
         }
      }
   }
}

/**
 * Change active profile to the $ID one. Update glpiactiveprofile session variable.
 *
 * @param $ID : ID of the new profile
 * @return Nothing
**/
function changeProfile($ID) {
   global $CFG_GLPI,$LANG;

   if (isset ($_SESSION['glpiprofiles'][$ID]) && count($_SESSION['glpiprofiles'][$ID]['entities'])) {
      $profile=new Profile();
      if ($profile->getFromDB($ID)) {
         $profile->cleanProfile();
         $data = $profile->fields;
         $data['entities']=$_SESSION['glpiprofiles'][$ID]['entities'];

         $_SESSION['glpiactiveprofile'] = $data;
         $_SESSION['glpiactiveentities'] = array ();

         $active_entity_done=false;
         // Try to load default entity if it is a root entity
         foreach ($data['entities'] as $key => $val) {
            if ($val['id']==$_SESSION["glpidefault_entity"]) {
               if (changeActiveEntities($val['id'],$val['is_recursive'])) {
                  $active_entity_done=true;
               }
            }
         }
         if (!$active_entity_done) {
            // Try to load default entity
            if (!changeActiveEntities($_SESSION["glpidefault_entity"],true)) {
               // Load all entities
               changeActiveEntities("all");
            }
         }
         doHook("change_profile");
      }
   }
   // Clean specific datas
   if (isset($_SESSION['glpi_faqcategories'])) {
      unset($_SESSION['glpi_faqcategories']);
   }
}


/**
 * Change active entity to the $ID one. Update glpiactiveentities session variable.
 * Reload groups related to this entity.
 *
 * @param $ID : ID of the new active entity ("all"=>load all possible entities)
 * @param $is_recursive : also display sub entities of the active entity ?
 * @return Nothing
**/
function changeActiveEntities($ID="all",$is_recursive=false) {
   global $LANG;

   $newentities=array();
   $newroots=array();
   if ($ID=="all") {
      $ancestors=array();
      foreach ($_SESSION['glpiactiveprofile']['entities'] as $key => $val) {
         $ancestors=array_unique(array_merge(getAncestorsOf("glpi_entities",$val['id']),$ancestors));
         $newroots[$val['id']]=$val['is_recursive'];
         $newentities[$val['id']] = $val['id'];
         if ($val['is_recursive']) {
            $entities = getSonsOf("glpi_entities", $val['id']);
            if (count($entities)) {
               foreach ($entities as $key2 => $val2) {
                  $newentities[$key2] = $key2;
               }
            }
         }
      }
   } else {
      /// Check entity validity
      $ancestors=getAncestorsOf("glpi_entities",$ID);
      $ok=false;
      foreach ($_SESSION['glpiactiveprofile']['entities'] as $key => $val) {
         if ($val['id']== $ID || in_array($val['id'], $ancestors)) {
            // Not recursive or recursive and root entity is recursive
            if (! $is_recursive || $val['is_recursive']) {
               $ok=true;
            }
         }
      }
      if (!$ok) {
         return false;
      }

      $newroots[$ID]=$is_recursive;
      $newentities[$ID] = $ID;
      if ($is_recursive) {
         $entities = getSonsOf("glpi_entities", $ID);
         if (count($entities)) {
            foreach ($entities as $key2 => $val2) {
               $newentities[$key2] = $key2;
            }
         }
      }
   }

   if (count($newentities)>0) {
      $_SESSION['glpiactiveentities']=$newentities;
      $_SESSION['glpiactiveentities_string']="'".implode("','",$newentities)."'";
      $active = reset($newentities);
      $_SESSION['glpiparententities']=$ancestors;
      $_SESSION['glpiparententities_string']=implode("','",$ancestors);
      if (!empty($_SESSION['glpiparententities_string'])) {
         $_SESSION['glpiparententities_string']="'".$_SESSION['glpiparententities_string']."'";
      }
      // Active entity loading
      $_SESSION["glpiactive_entity"] = $active;
      $_SESSION["glpiactive_entity_name"] = Dropdown::getDropdownName("glpi_entities",$active);
      $_SESSION["glpiactive_entity_shortname"] = getTreeLeafValueName("glpi_entities",$active);
      if ($is_recursive) {
         $_SESSION["glpiactive_entity_name"] .= " (".$LANG['entity'][7].")";
         $_SESSION["glpiactive_entity_shortname"] .= " (".$LANG['entity'][7].")";
      }
      if ($ID=="all") {
         $_SESSION["glpiactive_entity_name"] .= " (".$LANG['buttons'][40].")";
         $_SESSION["glpiactive_entity_shortname"] .= " (".$LANG['buttons'][40].")";
      }
      if (countElementsInTable('glpi_entities')<count($_SESSION['glpiactiveentities'])) {
         $_SESSION['glpishowallentities']=1;
      } else {
         $_SESSION['glpishowallentities']=0;
      }
      // Clean session variable to search system
      if (isset($_SESSION['glpisearch']) && count($_SESSION['glpisearch'])) {
         foreach ($_SESSION['glpisearch'] as $itemtype => $tab) {
            if (isset($tab['start'])&&$tab['start']>0) {
               $_SESSION['glpisearch'][$itemtype]['start']=0;
            }
         }
      }
      loadGroups();
      doHook("change_entity");
      return true;
   }
   return false;
}

/**
 * Load groups where I am in the active entity.
 * @return Nothing
**/
function loadGroups() {
   global $DB;

   $_SESSION["glpigroups"] = array ();

   $query_gp = "SELECT `groups_id`
                FROM `glpi_groups_users`
                LEFT JOIN `glpi_groups` ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`)
                WHERE `glpi_groups_users`.`users_id`='" . $_SESSION['glpiID'] . "' " .
                      getEntitiesRestrictRequest(" AND ","glpi_groups","entities_id",
                                                 $_SESSION['glpiactiveentities'],true);
   $result_gp = $DB->query($query_gp);
   if ($DB->numrows($result_gp)) {
      while ($data = $DB->fetch_array($result_gp)) {
         $_SESSION["glpigroups"][] = $data["groups_id"];
      }
   }
}

/**
 * Check if you could create recursive object in the entity of id = $ID
 *
 * @param $ID : ID of the entity
 * @return Boolean :
**/
function haveRecursiveAccessToEntity($ID) {
   // Right by profile
   foreach ($_SESSION['glpiactiveprofile']['entities'] as $key => $val) {
      if ($val['id']==$ID) {
         return $val['is_recursive'];
      }
   }
   // Right is from a recursive profile
   if (isset ($_SESSION['glpiactiveentities'])) {
      return in_array($ID, $_SESSION['glpiactiveentities']);
   }
   return false;
}

/**
 * Check if you could access (read) to the entity of id = $ID
 *
 * @param $ID : ID of the entity
 * @param $is_recursive : boolean if resursive item
 *
 * @return Boolean : read access to entity
**/
function haveAccessToEntity($ID, $is_recursive=0) {

   // Quick response when passing wrong ID : default value of getEntityID is -1
   if ($ID<0){
      return false;
   }

   if (!isset ($_SESSION['glpiactiveentities'])) {
      return false;
   }

   if (!$is_recursive) {
      return in_array($ID, $_SESSION['glpiactiveentities']);
   }

   if (in_array($ID, $_SESSION['glpiactiveentities'])) {
      return true;
   }

   /// Recursive object
   foreach ($_SESSION['glpiactiveentities'] as $ent) {
      if (in_array($ID, getAncestorsOf("glpi_entities",$ent))) {
         return true;
      }
   }

   return false;
}

/**
 * Check if you could access to one entity of an list
 *
 * @param $tab : list ID of entities
 * @return Boolean :
**/
function haveAccessToOneOfEntities($tab) {
   $access=false;
   if (is_array($tab)&&count($tab)) {
      foreach ($tab as $val) {
         if (haveAccessToEntity($val)) {
            return true;
         }
      }
   }
   return $access;
}

/**
 * Get SQL request to restrict to current entities of the user
 *
 * @param $separator : separator in the begin of the request
 * @param $table : table where apply the limit (if needed, multiple tables queries)
 * @param $field : field where apply the limit (id != entities_id)
 * @param $value : entity to restrict (if not set use $_SESSION['glpiactiveentities']). single item or array
 * @param $is_recursive : need to use recursive process to find item (field need to be named recursive)
 * @return String : the WHERE clause to restrict
**/
function getEntitiesRestrictRequest($separator = "AND", $table = "", $field = "",$value='',
                                    $is_recursive=false) {
   $query = $separator ." ( ";

   // !='0' needed because consider as empty
   if ($value!='0' && empty($value) && isset($_SESSION['glpishowallentities'])
       && $_SESSION['glpishowallentities']) {
      // Not ADD "AND 1" if not needed
      if (trim($separator)=="AND") {
         return "";
      } else {
         return $query." 1 ) ";
      }
   }

   if (!empty ($table)) {
      $query .= $table . ".";
   }
   if (empty($field)) {
      if ($table=='glpi_entities') {
         $field="id";
      } else {
         $field="entities_id";
      }
   }

   $query.=$field;

   if (is_array($value)) {
      $query .= " IN ('" . implode("','",$value) . "') ";
   } else {
      if (strlen($value)==0) {
         $query.=" IN (".$_SESSION['glpiactiveentities_string'].") ";
      } else {
         $query.= " = '$value' ";
      }
   }

   if ($is_recursive) {
      $ancestors=array();
      if (is_array($value)) {
         foreach ($value as $val) {
            $ancestors=array_unique(array_merge(getAncestorsOf("glpi_entities",$val),$ancestors));
         }
         $ancestors=array_diff($ancestors,$value);
      } else if (strlen($value)==0) {
         $ancestors=$_SESSION['glpiparententities'];
      } else {
         $ancestors=getAncestorsOf("glpi_entities",$value);
      }

      if (count($ancestors)) {
         if ($table=='glpi_entities') {
            $query.=" OR `$table`.`$field` IN ('" . implode("','",$ancestors) . "')";
         } else {
            $query.=" OR ( `$table`.`is_recursive`='1'
                          AND `$table`.`$field` IN ('" . implode("','",$ancestors) . "'))";
         }
      }
   }
   $query.=" ) ";

   return $query;
}

/**
 * Connect to a LDAP serveur
 *
 * @param $host : LDAP host to connect
 * @param $port : port to use
 * @param $login : login to use
 * @param $password : password to use
 * @param $use_tls : use a tls connection ?
 * @param $deref_options Deref options used
 * @return link to the LDAP server : false if connection failed
**/
function connect_ldap($host, $port, $login = "", $password = "", $use_tls = false,$deref_options) {
   global $CFG_GLPI;

   $ds = @ldap_connect($host, intval($port));
   if ($ds) {
      @ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
      @ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
      @ldap_set_option($ds, LDAP_OPT_DEREF, $deref_options);
      if ($use_tls) {
         if (!@ldap_start_tls($ds)) {
            return false;
         }
      }
      // Auth bind
      if ($login != '') {
         $b = @ldap_bind($ds, $login, $password);
      } else { // Anonymous bind
         $b = @ldap_bind($ds);
      }
      if ($b) {
         return $ds;
      } else {
         return false;
      }
   } else {
      return false;
   }
}

/**
 * Try to connect to a ldap server
 *
 * @param $id ID of the LDAP config (use to find replicate)
 * @param $host : LDAP host to connect
 * @param $port : port to use
 * @param $rdn : rootdn to use
 * @param $rpass : rootdn password to use
 * @param $use_tls : use a tls connection ?
 * @param $login : user login
 * @param $password : user password
 * @param $deref_options Deref options used
 * @return link to the LDAP server : false if connection failed
**/
function try_connect_ldap($host, $port, $rdn, $rpass, $use_tls,$login, $password,$deref_options,$id){

   // TODO try to pass array of connection config to minimise parameters
   $ds = connect_ldap($host, $port, $rdn, $rpass, $use_tls,$deref_options);
   // Test with login and password of the user if exists
   if (!$ds && !empty($login)) {
      $ds = connect_ldap($host, $port, $login, $password, $use_tls,$deref_options);
   }

   //If connection is not successfull on this directory, try replicates (if replicates exists)
   if (!$ds && $id>0) {
      foreach (getAllReplicateForAMaster($id) as $replicate) {
         $ds = connect_ldap($replicate["host"], $replicate["port"], $rdn, $rpass, $use_tls,
                            $deref_options);
         // Test with login and password of the user
         if (!$ds && !empty($login)) {
            $ds = connect_ldap($replicate["host"], $replicate["port"], $login, $password, $use_tls,
                               $deref_options);
         }
         if ($ds) {
            return $ds;
         }
      }
   }
   return $ds;
}

/**
 * Get infos for groups
 *
 * @param $ds : LDAP link
 * @param $basedn : base dn used to search
 * @param $group_dn : dn of the group
 * @param $condition : ldap condition used
 * @return group infos if found, else false
**/
function ldap_search_group_by_dn($ds, $basedn, $group_dn,$condition) {

   if($result = @ ldap_read($ds, $group_dn, "objectClass=*", array("cn"))) {
      $info = ldap_get_entries($ds, $result);
      if (is_array($info) AND $info['count'] == 1) {
         return $info[0];
      } else {
         return false;
      }
   }
   return false;
}

/**
 * Get dn for a user
 *
 * @param $ds : LDAP link
 * @param $basedn : base dn used to search
 * @param $login_attr : attribute to store login
 * @param $login : user login
 * @param $condition : ldap condition used
 * @return dn of the user, else false
**/
function ldap_search_user_dn($ds, $basedn, $login_attr, $login, $condition) {

   // Tenter une recherche pour essayer de retrouver le DN
   $filter = "($login_attr=$login)";

   if (!empty ($condition)) {
      $filter = "(& $filter $condition)";
   }
   if ($result = ldap_search($ds, $basedn, $filter, array ("dn", $login_attr),0,0)){
      $info = ldap_get_entries($ds, $result);
      if (is_array($info) AND $info['count'] == 1) {
         return $info[0]['dn'];
      } else { // Si echec, essayer de deviner le DN / Flat LDAP
         $dn = "$login_attr=$login," . $basedn;
         return $dn;
      }
   } else {
      return false;
   }
}

/**
 * Try to authentify a user by checking all the directories
 * @param $auth : identification object
 * @param $login : user login
 * @param $password : user password
 * @param $auths_id : auths_id already used for the user
 * @return identification object
**/
function try_ldap_auth($auth,$login,$password, $auths_id = 0) {

   //If no specific source is given, test all ldap directories
   if ($auths_id <= 0) {
      foreach  ($auth->authtypes["ldap"] as $ldap_method) {
         if (!$auth->auth_succeded) {
            $auth = ldap_auth($auth, $login,$password,$ldap_method);
         } else {
            break;
         }
      }
   //Check if the ldap server indicated as the last good one still exists !
   } else if(array_key_exists($auths_id,$auth->authtypes["ldap"])) {
      //A specific ldap directory is given, test it and only this one !
      $auth = ldap_auth($auth, $login,$password,
                               $auth->authtypes["ldap"][$auths_id]);
   }
   return $auth;
}

/**
 * Authentify a user by checking a specific directory
 * @param $auth : identification object
 * @param $login : user login
 * @param $password : user password
 * @param $ldap_method : ldap_method array to use
 * @return identification object
**/
function ldap_auth($auth,$login,$password, $ldap_method) {

   $user_dn = $auth->connection_ldap($ldap_method["id"],$ldap_method["host"],
                                            $ldap_method["port"], $ldap_method["basedn"],
                                            $ldap_method["rootdn"], $ldap_method["rootdn_password"],
                                            $ldap_method["login_field"],$login, $password,
                                            $ldap_method["condition"], $ldap_method["use_tls"],
                                            $ldap_method["deref_option"]);
   if ($user_dn) {
      $auth->auth_succeded = true;
      $auth->extauth = 1;
      $auth->user_present = $auth->user->getFromDBbyName(addslashes($login));
      $auth->user->getFromLDAP($auth->ldap_connection,$ldap_method, $user_dn, $login,
                                      $password);
      $auth->auth_parameters = $ldap_method;
      $auth->user->fields["authtype"] = AUTH_LDAP;
      $auth->user->fields["auths_id"] = $ldap_method["id"];
   }
   return $auth;
}

/**
 * Try to authentify a user by checking all the mail server
 * @param $auth : identification object
 * @param $login : user login
 * @param $password : user password
 * @param $auths_id : auths_id already used for the user
 * @return identification object
**/
function try_mail_auth($auth, $login,$password,$auths_id = 0) {
   if ($auths_id <= 0) {
      foreach ($auth->authtypes["mail"] as $mail_method) {
         if (!$auth->auth_succeded) {
            $auth = mail_auth($auth, $login,$password,$mail_method);
         } else {
            break;
         }
      }
   } else if(array_key_exists($auths_id,$auth->authtypes["mail"])){
      //Check if the mail server indicated as the last good one still exists !
      $auth = mail_auth($auth, $login,$password,
                               $auth->authtypes["mail"][$auths_id]);
   }
   return $auth;
}

/**
 * Authentify a user by checking a specific mail server
 * @param $auth : identification object
 * @param $login : user login
 * @param $password : user password
 * @param $mail_method : mail_method array to use
 * @return identification object
**/
function mail_auth($auth, $login,$password,$mail_method) {

   if (isset($mail_method["connect_string"]) && !empty ($mail_method["connect_string"])) {
      $auth->auth_succeded = $auth->connection_imap($mail_method["connect_string"],
                                                                  decodeFromUtf8($login),
                                                                  decodeFromUtf8($password));
      if ($auth->auth_succeded) {
         $auth->extauth = 1;
         $auth->user_present = $auth->user->getFromDBbyName(addslashes($login));
         $auth->auth_parameters = $mail_method;
         $auth->user->getFromIMAP($mail_method, decodeFromUtf8($login));

         //Update the authentication method for the current user
         $auth->user->fields["authtype"] = AUTH_MAIL;
         $auth->user->fields["auths_id"] = $mail_method["id"];
      }
   }
   return $auth;
}

/**
 * Test a connexion to the IMAP/POP server
 * @param $connect_string : mail server
 * @param $login : user login
 * @param $password : user password
 * @return authentification succeeded ?
**/
function test_auth_mail($connect_string,$login,$password) {
   $auth = new Auth();
   return $auth->connection_imap($connect_string, decodeFromUtf8($login), decodeFromUtf8($password));
}

/**
 * Import a user from ldap
 * Check all the directories. When the user is found, then import it
 * @param $login : user login
**/
function import_user_from_ldap_servers($login) {
   global $LANG;

   $auth = new Auth;
   $auth->user_present = $auth->userExists($login);

   //If the user does not exists
   if ($auth->user_present == 0) {
      $auth->getAuthMethods();
      $ldap_methods = $auth->authtypes["ldap"];
      $userid = -1;

      foreach ($ldap_methods as $ldap_method) {
         $result=ldapImportUserByServerId($login, 0,$ldap_method["id"],true);
         if ($result != false) {
            return $result;
         }
      }
      addMessageAfterRedirect($LANG['login'][15],false,ERROR);
   } else {
      addMessageAfterRedirect($LANG['setup'][606],false,ERROR);
   }
   return false;
}

/**
 * Is the Mail authentication used ?
 *
 * @return boolean
**/
function useAuthMail() {
   global $DB;

   //Get all the pop/imap servers
   $sql = "SELECT count(*)
           FROM `glpi_authmails`";
   $result = $DB->query($sql);
   if ($DB->result($result,0,0) > 0) {
      return true;
   }
   return false;
}

/**
 * Is the LDAP authentication used ?
 *
 * @return boolean
**/
function useAuthLdap() {
   global $DB;

   //Get all the ldap directories
   $sql = "SELECT count(*)
           FROM `glpi_authldaps`";
   $result = $DB->query($sql);
   if ($DB->result($result,0,0) > 0) {
      return true;
   }
   return false;
}


/**
 * Is an external authentication used ?
 *
 * @return boolean
**/
function useAuthExt() {
   global $DB;

   //Get all the ldap directories
   if (useAuthLdap()) {
      return true;
   }

   if (useAuthMail()) {
      return true;
   }
   return false;
}

/**
 * Get all replicate servers for a master one
 *
 * @param $master_id : master ldap server ID
 * @return array of the replicate servers
**/
function getAllReplicateForAMaster($master_id) {
   global $DB;

   $replicates = array();
   $query="SELECT `id`, `host`, `port`
           FROM `glpi_authldapreplicates`
           WHERE `authldaps_id` = '".$master_id."'";
   $result = $DB->query($query);
   if ($DB->numrows($result)>0) {
      while ($replicate = $DB->fetch_array($result)) {
         $replicates[] = array("id"=>$replicate["id"],
                               "host"=>$replicate["host"],
                               "port"=>$replicate["port"]);
      }
   }
   return $replicates;
}

/**
 * Get all replicate name servers for a master one
 *
 * @param $master_id : master ldap server ID
 * @return string containing names of the replicate servers
**/
function getAllReplicatesNamesForAMaster($master_id) {

   $replicates = getAllReplicateForAMaster($master_id);
   $str = "";
   foreach ($replicates as $replicate) {
      $str.= ($str!=''?',':'')."&nbsp;".$replicate["host"].":".$replicate["port"];
   }
   return $str;
}

/**
 * Check alternate authentication systems
 *
 * @param $redirect : need to redirect (true) or get type of Auth system which match
 * @param $redirect_string : redirect string if exists
 * @return nothing if redirect is true, else Auth system ID
**/
function checkAlternateAuthSystems($redirect=false,$redirect_string='') {
   global $CFG_GLPI;

   if (isset($_GET["noAUTO"]) || isset($_POST["noAUTO"])) {
      return false;
   }

   $redir_string="";
   if (!empty($redirect_string)) {
      $redir_string="?redirect=".$redirect_string;
   }
   // Using x509 server
   if (!empty($CFG_GLPI["x509_email_field"]) && isset($_SERVER['SSL_CLIENT_S_DN'])
       && strstr($_SERVER['SSL_CLIENT_S_DN'],$CFG_GLPI["x509_email_field"])) {
      if ($redirect) {
         glpi_header("login.php".$redir_string);
      } else {
         return AUTH_X509;
      }
   }
   // Existing auth method
   if (!empty($CFG_GLPI["existing_auth_server_field"])
       && isset($_SERVER[$CFG_GLPI["existing_auth_server_field"]])
       && !empty($_SERVER[$CFG_GLPI["existing_auth_server_field"]])) {
      if ($redirect) {
         glpi_header("login.php".$redir_string);
      } else {
         return AUTH_EXTERNAL;
      }
   }
   // Using CAS server
   if (!empty($CFG_GLPI["cas_host"])) {
      if ($redirect) {
         glpi_header("login.php".$redir_string);
      } else {
         return AUTH_CAS;
      }
   }
return false;
}

/**
 * Is an alternate auth ?
 *
 * @param $auths_id auth type
 * @return boolean
**/
function isAlternateAuth($auths_id) {
   return  in_array($auths_id,array(AUTH_X509,AUTH_CAS,AUTH_EXTERNAL));
}

/**
 * Is an alternate auth wich used LDAP extra server?
 *
 * @param $auths_id auth type
 * @return boolean
**/
function isAlternateAuthWithLdap($auths_id) {
   global $CFG_GLPI;

   return (isAlternateAuth($auths_id) && $CFG_GLPI["authldaps_id_extra"] > 0);
}

function getLdapServers () {
   return getAllDatasFromTable('glpi_authldaps');
}
?>
