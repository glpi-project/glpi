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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// OCS Rules collection class
class RuleOcsCollection extends RuleCollection {

   // From RuleCollection
   public $stop_on_first_match=true;
   public $right = 'rule_ocs';
   public $menu_option='ocs';

   // Specific ones
   ///Store the id of the ocs server
   var $ocsservers_id;

   /**
    * Constructor
    * @param $ocsservers_id ID of the OCS server
   **/
   function __construct($ocsservers_id=-1) {
      $this->ocsservers_id = $ocsservers_id;
   }

   function canList() {
      global $CFG_GLPI;
      return $CFG_GLPI["use_ocs_mode"] && $this->canView();
   }

   function getTitle() {
      global $LANG;

      return $LANG['rulesengine'][18];
   }

   function prepareInputDataForProcess($input,$computers_id) {
      global $DBocs;

      $tables = $this->getTablesForQuery();
      $fields = $this->getFieldsForQuery();
      $rule_parameters = array();

      $select_sql = "";

      //Build the select request
      foreach ($fields as $field) {
         switch (utf8_strtoupper($field)) {
            //OCS server ID is provided by extra_params -> get the configuration associated with the ocs server
            case "OCS_SERVER" :
               $rule_parameters["OCS_SERVER"] = $this->ocsservers_id;
               break;

            //TAG and DOMAIN should come from the OCS DB
            default :
               $select_sql .= ($select_sql != "" ? " , " : "") . $field;
         }
      }

      //Build the FROM part of the request
      //Remove all the non duplicated table names
      $from_sql = "FROM `hardware` ";
      foreach ($tables as $table => $linkfield) {
         if ($table!='hardware' && !empty($linkfield)) {
            $from_sql .= " LEFT JOIN `$table` ON (`$table`.`$linkfield` = `hardware`.`ID`)";
         }
      }

      if ($select_sql != "") {
         //Build the all request
         $sql = "SELECT $select_sql
                 $from_sql
                 WHERE `hardware`.`ID` = '$computers_id'";

         OcsServer::checkOCSconnection($this->ocsservers_id);
         $result = $DBocs->query($sql);
         $ocs_datas = array();
         $fields = $this->getFieldsForQuery(1);

         //May have more than one line : for example in case of multiple network cards
         if ($DBocs->numrows($result) > 0) {
            while ($datas = $DBocs->fetch_array($result)) {
               foreach ($fields as $field) {
                  if ($field != "OCS_SERVER" && isset($datas[$field])) {
                     $ocs_datas[$field][] = $datas[$field];
                  }
               }
            }
         }
         //This cas should never happend but...
         //Sometimes OCS can't find network ports but fill the right ip in hardware table...
         //So let's use the ip to proceed rules (if IP is a criteria of course)
         if (in_array("IPADDRESS",$fields) && !isset($ocs_datas['IPADDRESS'])) {
            $ocs_datas['IPADDRESS']=OcsServer::getGeneralIpAddress($this->ocsservers_id,$computers_id);
         }
         return array_merge($rule_parameters, $ocs_datas);
      } else {
         return $rule_parameters;
      }
   }

   /**
   * Get the list of all tables to include in the query
       * @return an array of table names
    	*/
   function getTablesForQuery() {
      $rule = new RuleOcs();

      $tables = array();
      foreach ($rule->getCriterias() as $criteria) {
         if ((!isset($criteria['virtual']) || !$criteria['virtual'])
             && $criteria['table'] != ''
             && !isset($tables[$criteria["table"]])) {

            $tables[$criteria['table']]=$criteria['linkfield'];
         }
      }

      return $tables;
   }

   /**
   * Get fields needed to process criterias
   * @param $withouttable fields without tablename ?
   * @return an array of needed fields
   */
   function getFieldsForQuery($withouttable=0) {

      $rule = new RuleOcs();
      $fields = array();
      foreach ($rule->getCriterias() as $key => $criteria) {
         if ($withouttable) {
            if (strcasecmp($key,$criteria['field']) != 0) {
               $fields[]=$key;
            } else {
               $fields[]=$criteria['field'];
            }
         } else {
            //If the field is different from the key
            if (strcasecmp($key,$criteria['field']) != 0) {
               $as = " AS ".$key;
            } else {
               $as ="";
            }
            //If the field name is not null AND a table name is provided
            if (($criteria['field'] != ''
                 && (!isset($criteria['virtual']) || !$criteria['virtual']))) {
               if ( $criteria['table'] != '') {
                  $fields[]=$criteria['table'].".".$criteria['field'].$as;
               } else {
                  $fields[]=$criteria['field'].$as;
               }
            } else {
               $fields[]=$criteria['id'];
            }
         }
      }
      return $fields;
   }

   /**
   * Get foreign fields needed to process criterias
   * @return an array of needed fields
   */
   function getFKFieldsForQuery() {

      $rule = new RuleOcs();
      $fields = array();
      foreach ($rule->getCriterias() as $criteria) {
         //If the field name is not null AND a table name is provided
         if ((!isset($criteria['virtual']) || !$criteria['virtual'])
             && $criteria['linkfield'] != '') {

            $fields[]=$criteria['table'].".".$criteria['linkfield'];
         }
      }
      return $fields;
   }

}


?>
