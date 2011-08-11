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

   $matches = array(""  => array("", "r", "w"), // ne doit pas arriver normalement
                    "r" => array("r", "w"),
                    "w" => array("w"),
                    "1" => array("1"),
                    "0" => array("0", "1")); // ne doit pas arriver non plus

   if (isset($_SESSION["glpiactiveprofile"][$module])
       && in_array($_SESSION["glpiactiveprofile"][$module], $matches[$right])) {
      return true;
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
      if (!isset($_SESSION["glpiactiveprofile"]["interface"])) {
         nullHeader($LANG['login'][5]);

      } else if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
         commonHeader($LANG['login'][5]);

      } else if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
         helpHeader($LANG['login'][5]);
      }
   }
   echo "<div class='center'><br><br>";
   echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='warning'><br><br>";
   echo "<strong>" . $LANG['common'][54] . "</strong></div>";
   nullFooter();
   exit ();
}








/**
 * Check if you could create recursive object in the entity of id = $ID
 *
 * @param $ID : ID of the entity
 *
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
   if (isset($_SESSION['glpiactiveentities'])) {
      return in_array($ID, $_SESSION['glpiactiveentities']);
   }
   return false;
}


/**
 * Check if you could access (read) to the entity of id = $ID
 *
 * @param $ID : ID of the entity
 * @param $is_recursive : boolean if recursive item
 *
 * @return Boolean : read access to entity
**/
function haveAccessToEntity($ID, $is_recursive=0) {

   // Quick response when passing wrong ID : default value of getEntityID is -1
   if ($ID<0) {
      return false;
   }

   if (!isset($_SESSION['glpiactiveentities'])) {
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
      if (in_array($ID, getAncestorsOf("glpi_entities", $ent))) {
         return true;
      }
   }

   return false;
}


/**
 * Check if you could access to one entity of an list
 *
 * @param $tab : list ID of entities
 *
 * @return Boolean :
**/
function haveAccessToOneOfEntities($tab) {

   if (is_array($tab) && count($tab)) {
      foreach ($tab as $val) {
         if (haveAccessToEntity($val)) {
            return true;
         }
      }
   }
   return false;
}


/**
 * Check if you could access to ALL the entities of an list
 *
 * @param $tab : list ID of entities
 *
 * @return Boolean :
**/
function haveAccessToAllOfEntities($tab) {

   if (is_array($tab) && count($tab)) {
      foreach ($tab as $val) {
         if (!haveAccessToEntity($val)) {
            return false;
         }
      }
   }
   return true;
}


/**
 * Get SQL request to restrict to current entities of the user
 *
 * @param $separator : separator in the begin of the request
 * @param $table : table where apply the limit (if needed, multiple tables queries)
 * @param $field : field where apply the limit (id != entities_id)
 * @param $value : entity to restrict (if not set use $_SESSION['glpiactiveentities']). single item or array
 * @param $is_recursive : need to use recursive process to find item (field need to be named recursive)
 *
 * @return String : the WHERE clause to restrict
**/
function getEntitiesRestrictRequest($separator = "AND", $table = "", $field = "",$value='',
                                    $is_recursive=false) {

   $query = $separator ." ( ";

   // !='0' needed because consider as empty
   if ($value!='0'
       && empty($value)
       && isset($_SESSION['glpishowallentities'])
       && $_SESSION['glpishowallentities']) {

      // Not ADD "AND 1" if not needed
      if (trim($separator)=="AND") {
         return "";
      }
      return $query." 1 ) ";
   }

   if (!empty($table)) {
      $query .= "`$table`.";
   }
   if (empty($field)) {
      if ($table=='glpi_entities') {
         $field = "id";
      } else {
         $field = "entities_id";
      }
   }

   $query .= "`$field`";

   if (is_array($value)) {
      $query .= " IN ('" . implode("','",$value) . "') ";
   } else {
      if (strlen($value)==0) {
         $query .= " IN (".$_SESSION['glpiactiveentities_string'].") ";
      } else {
         $query .= " = '$value' ";
      }
   }

   if ($is_recursive) {
      $ancestors = array();
      if (is_array($value)) {
         foreach ($value as $val) {
            $ancestors = array_unique(array_merge(getAncestorsOf("glpi_entities", $val),
                                                  $ancestors));
         }
         $ancestors = array_diff($ancestors, $value);

      } else if (strlen($value)==0) {
         $ancestors = $_SESSION['glpiparententities'];

      } else {
         $ancestors = getAncestorsOf("glpi_entities", $value);
      }

      if (count($ancestors)) {
         if ($table=='glpi_entities') {
            $query .= " OR `$table`.`$field` IN ('" . implode("','",$ancestors) . "')";
         } else {
            $query .= " OR (`$table`.`is_recursive`='1'
                            AND `$table`.`$field` IN ('" . implode("','",$ancestors) . "'))";
         }
      }
   }
   $query .= " ) ";

   return $query;
}


/**
 * Get all replicate servers for a master one
 *
 * @param $master_id : master ldap server ID
 *
 * @return array of the replicate servers
**/
function getAllReplicateForAMaster($master_id) {
   global $DB;

   $replicates = array();
   $query = "SELECT `id`, `host`, `port`
             FROM `glpi_authldapreplicates`
             WHERE `authldaps_id` = '$master_id'";
   $result = $DB->query($query);

   if ($DB->numrows($result)>0) {
      while ($replicate = $DB->fetch_array($result)) {
         $replicates[] = array("id"   => $replicate["id"],
                               "host" => $replicate["host"],
                               "port" => $replicate["port"]);
      }
   }
   return $replicates;
}


/**
 * Get all replicate name servers for a master one
 *
 * @param $master_id : master ldap server ID
 *
 * @return string containing names of the replicate servers
**/
function getAllReplicatesNamesForAMaster($master_id) {

   $replicates = getAllReplicateForAMaster($master_id);
   $str = "";
   foreach ($replicates as $replicate) {
      $str .= ($str!=''?',':'')."&nbsp;".$replicate["host"].":".$replicate["port"];
   }
   return $str;
}

?>
