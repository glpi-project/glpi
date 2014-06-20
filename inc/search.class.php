<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Generic class for Search Engine
class Search {

   // Default number of items displayed in global search
   const GLOBAL_DISPLAY_COUNT = 10;
   // EXPORT TYPE
   const GLOBAL_SEARCH        = -1;
   const HTML_OUTPUT          = 0;
   const SYLK_OUTPUT          = 1;
   const PDF_OUTPUT_LANDSCAPE = 2;
   const CSV_OUTPUT           = 3;
   const PDF_OUTPUT_PORTRAIT  = 4;
   static $output_type = self::HTML_OUTPUT;

   /**
    * Display search engine for an type
    *
    * @param $itemtype item type to manage
    *
    * @return nothing
   **/
   static function show($itemtype) {

      self::manageGetValues($itemtype);
      self::showGenericSearch($itemtype, $_GET);
      self::showList($itemtype, $_GET);
   }


   /**
    * Generic Search and list function
    *
    * Build the query, make the search and list items after a search.
    *
    * @param $itemtype        item type
    * @param $params    array of parameters may include field, contains, searchtype, sort, order,
    *                         start, deleted, link, link2, contains2, field2, itemtype2, searchtype2
    *
    * @return Nothing (display)
   **/
   static function showList($itemtype, array $params) {
      global $DB, $CFG_GLPI;

      // Instanciate an object to access method
      $item = NULL;

      if ($itemtype != 'AllAssets') {
         $item = getItemForItemtype($itemtype);
      }

      // Default values of parameters
      $p['link']        = array();//
      $p['field']       = array();//
      $p['contains']    = array();//
      $p['searchtype']  = array();//
      $p['sort']        = '1'; //
      $p['order']       = 'ASC';//
      $p['start']       = 0;//
      $p['is_deleted']  = 0;
      $p['export_all']  = 0;
      $p['link2']       = '';//
      $p['contains2']   = '';//
      $p['field2']      = '';//
      $p['itemtype2']   = '';
      $p['searchtype2'] = '';

      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }

      if ($p['export_all']) {
         $p['start'] = 0;
      }

      if (in_array('all', $p['field'])
          && !$CFG_GLPI['allow_search_all']) {
         Html::displayRightError();
      }
      if (in_array('view', $p['field'])
          && !$CFG_GLPI['allow_search_view']) {
         Html::displayRightError();
      }

      // Manage defautll seachtype value : for bookmark compatibility
      if (count($p['contains'])) {
         foreach ($p['contains'] as $key => $val) {
            if (!isset($p['searchtype'][$key])) {
               $p['searchtype'][$key] = 'contains';
            }
         }
      }
      if (is_array($p['contains2']) && count($p['contains2'])) {
         foreach ($p['contains2'] as $key => $val) {
            if (!isset($p['searchtype2'][$key])) {
               $p['searchtype2'][$key] = 'contains';
            }
         }
      }

      $target           = Toolbox::getItemTypeSearchURL($itemtype);

      $limitsearchopt   = self::getCleanedOptions($itemtype);

      $blacklist_tables = array();
      if (isset($CFG_GLPI['union_search_type'][$itemtype])) {
         $itemtable = $CFG_GLPI['union_search_type'][$itemtype];
         $blacklist_tables[] = getTableForItemType($itemtype);
      } else {
         $itemtable = getTableForItemType($itemtype);
      }

      $LIST_LIMIT = $_SESSION['glpilist_limit'];

      // Set display type for export if define
      $output_type = self::HTML_OUTPUT;
      if (isset($_GET['display_type'])) {
         $output_type = $_GET['display_type'];
         // Limit to 10 element
         if ($_GET['display_type'] == self::GLOBAL_SEARCH) {
            $LIST_LIMIT = self::GLOBAL_DISPLAY_COUNT;
         }
      }
      // hack for AllAssets
      if (isset($CFG_GLPI['union_search_type'][$itemtype])) {
         $entity_restrict = true;
      } else {
         $entity_restrict = $item->isEntityAssign();
      }

      self::$output_type = $output_type;

      $metanames = array();

      // Get the items to display
      $toview = self::addDefaultToView($itemtype);

      // Add items to display depending of personal prefs
      $displaypref = DisplayPreference::getForTypeUser($itemtype, Session::getLoginUserID());
      if (count($displaypref)) {
         foreach ($displaypref as $val) {
            array_push($toview,$val);
         }
      }

      // Add searched items
      if (count($p['field'])  >0) {
         foreach ($p['field'] as $key => $val) {
            if (!in_array($val, $toview)
                && ($val != 'all') && ($val != 'view')) {
               array_push($toview, $val);
            }
         }
      }

      // Add order item
      if (!in_array($p['sort'],$toview)) {
         array_push($toview, $p['sort']);
      }

      // Special case for Ticket : put ID in front
      if ($itemtype == 'Ticket') {
         array_unshift($toview, 2);
      }

      // Clean toview array
      $toview = array_unique($toview);
      foreach ($toview as $key => $val) {
         if (!isset($limitsearchopt[$val])) {
            unset($toview[$key]);
         }
      }

      $toview_count = count($toview);

      // Construct the request

      //// 1 - SELECT
      // request currentuser for SQL supervision, not displayed
      $SELECT = "SELECT '".Toolbox::addslashes_deep($_SESSION['glpiname'])."' AS currentuser,
                        ".self::addDefaultSelect($itemtype);

      // Add select for all toview item
      foreach ($toview as $key => $val) {
         $SELECT .= self::addSelect($itemtype, $val, $key, 0);
      }

      //// 2 - FROM AND LEFT JOIN
      // Set reference table
      $FROM = " FROM `$itemtable`";

      // Init already linked tables array in order not to link a table several times
      $already_link_tables = array();
      // Put reference table
      array_push($already_link_tables, $itemtable);

      // Add default join
      $COMMONLEFTJOIN = self::addDefaultJoin($itemtype, $itemtable, $already_link_tables);
      $FROM          .= $COMMONLEFTJOIN;

      $searchopt            = array();
      $searchopt[$itemtype] = &self::getOptions($itemtype);
      // Add all table for toview items
      foreach ($toview as $key => $val) {
         if (!in_array($searchopt[$itemtype][$val]["table"], $blacklist_tables)) {
            $FROM .= self::addLeftJoin($itemtype, $itemtable, $already_link_tables,
                                       $searchopt[$itemtype][$val]["table"],
                                       $searchopt[$itemtype][$val]["linkfield"], 0, 0,
                                       $searchopt[$itemtype][$val]["joinparams"]);
         }
      }

      // Search all case :
      if (in_array("all", $p['field'])) {
         foreach ($searchopt[$itemtype] as $key => $val) {
            // Do not search on Group Name
            if (is_array($val)) {
               if (!in_array($searchopt[$itemtype][$key]["table"], $blacklist_tables)) {
                  $FROM .= self::addLeftJoin($itemtype, $itemtable, $already_link_tables,
                                             $searchopt[$itemtype][$key]["table"],
                                             $searchopt[$itemtype][$key]["linkfield"], 0, 0,
                                             $searchopt[$itemtype][$key]["joinparams"]);
               }
            }
         }
      }


      //// 3 - WHERE

      // default string
      $COMMONWHERE = self::addDefaultWhere($itemtype);
      $first       = empty($COMMONWHERE);

      // Add deleted if item have it
      if ($item && $item->maybeDeleted()) {
         $LINK = " AND " ;
         if ($first) {
            $LINK  = " ";
            $first = false;
         }
         $COMMONWHERE .= $LINK."`$itemtable`.`is_deleted` = '".$p['is_deleted']."' ";
      }

      // Remove template items
      if ($item && $item->maybeTemplate()) {
         $LINK = " AND " ;
         if ($first) {
            $LINK  = " ";
            $first = false;
         }
         $COMMONWHERE .= $LINK."`$itemtable`.`is_template` = '0' ";
      }

      // Add Restrict to current entities
      if ($entity_restrict) {
         $LINK = " AND " ;
         if ($first) {
            $LINK  = " ";
            $first = false;
         }

         if ($itemtype == 'Entity') {
            $COMMONWHERE .= getEntitiesRestrictRequest($LINK, $itemtable, 'id', '', true);

         } else if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
            // Will be replace below in Union/Recursivity Hack
            $COMMONWHERE .= $LINK." ENTITYRESTRICT ";
         } else {
            $COMMONWHERE .= getEntitiesRestrictRequest($LINK, $itemtable, '', '',
                                                       $item->maybeRecursive());
         }
      }
      $WHERE  = "";
      $HAVING = "";

      // Add search conditions
      // If there is search items
      if (($_SESSION["glpisearchcount"][$itemtype] > 0)
          && (count($p['contains']) > 0)) {

         for ($key=0 ; $key<$_SESSION["glpisearchcount"][$itemtype] ; $key++) {
            // if real search (strlen >0) and not all and view search
            if (isset($p['contains'][$key]) && (strlen($p['contains'][$key]) > 0)) {
               // common search
               if (($p['field'][$key] != "all") && ($p['field'][$key] != "view")) {
                  $LINK    = " ";
                  $NOT     = 0;
                  $tmplink = "";
                  if (is_array($p['link']) && isset($p['link'][$key])) {
                     if (strstr($p['link'][$key],"NOT")) {
                        $tmplink = " ".str_replace(" NOT","",$p['link'][$key]);
                        $NOT     = 1;
                     } else {
                        $tmplink = " ".$p['link'][$key];
                     }
                  } else {
                     $tmplink = " AND ";
                  }

                  if (isset($searchopt[$itemtype][$p['field'][$key]]["usehaving"])) {
                     // Manage Link if not first item
                     if (!empty($HAVING)) {
                        $LINK = $tmplink;
                     }
                     // Find key
                     $item_num = array_search($p['field'][$key], $toview);
                     $HAVING  .= self::addHaving($LINK, $NOT,$itemtype, $p['field'][$key],
                                                 $p['searchtype'][$key], $p['contains'][$key], 0,
                                                 $item_num);
                  } else {
                     // Manage Link if not first item
                     if (!empty($WHERE)) {
                        $LINK = $tmplink;
                     }
                     $WHERE .= self::addWhere($LINK, $NOT, $itemtype, $p['field'][$key],
                                              $p['searchtype'][$key], $p['contains'][$key]);
                  }

               // view and all search
               } else {
                  $LINK       = " OR ";
                  $NOT        = 0;
                  $globallink = " AND ";

                  if (is_array($p['link']) && isset($p['link'][$key])) {
                     switch ($p['link'][$key]) {
                        case "AND" :
                           $LINK       = " OR ";
                           $globallink = " AND ";
                           break;

                        case "AND NOT" :
                           $LINK       = " AND ";
                           $NOT        = 1;
                           $globallink = " AND ";
                           break;

                        case "OR" :
                           $LINK       = " OR ";
                           $globallink = " OR ";
                           break;

                        case "OR NOT" :
                           $LINK       = " AND ";
                           $NOT        = 1;
                           $globallink = " OR ";
                           break;
                     }

                  } else {
                     $tmplink =" AND ";
                  }

                  // Manage Link if not first item
                  if (!empty($WHERE)) {
                     $WHERE .= $globallink;
                  }
                  $WHERE .= " ( ";
                  $first2 = true;

                  $items = array();

                  if ($p['field'][$key] == "all") {
                     $items = $searchopt[$itemtype];
                  } else { // toview case : populate toview
                     foreach ($toview as $key2 => $val2) {
                        $items[$val2] = $searchopt[$itemtype][$val2];
                     }
                  }

                  foreach ($items as $key2 => $val2) {
                     if (isset($val2['nosearch']) && $val2['nosearch']) {
                        continue;
                     }
                     if (is_array($val2)) {
                        // Add Where clause if not to be done in HAVING CLAUSE
                        if (!isset($val2["usehaving"])) {
                           $tmplink = $LINK;
                           if ($first2) {
                              $tmplink = " ";
                              $first2  = false;
                           }
                           $WHERE .= self::addWhere($tmplink, $NOT, $itemtype, $key2,
                                                    $p['searchtype'][$key], $p['contains'][$key]);
                        }
                     }
                  }
                  $WHERE .= " ) ";
               }
            }
         }
      }


      //// 4 - ORDER
      $ORDER = " ORDER BY `id` ";
      foreach ($toview as $key => $val) {
         if ($p['sort'] == $val) {
            $ORDER = self::addOrderBy($itemtype, $p['sort'], $p['order'], $key);
         }
      }


      //// 5 - META SEARCH
      // Preprocessing
      if (($_SESSION["glpisearchcount2"][$itemtype] > 0)
          && is_array($p['itemtype2'])) {

         // a - SELECT
         for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
            if (isset($p['itemtype2'][$i]) && !empty($p['itemtype2'][$i])
                && isset($p['contains2'][$i]) && (strlen($p['contains2'][$i]) > 0)) {

               $SELECT .= self::addSelect($p['itemtype2'][$i], $p['field2'][$i], $i, 1,
                                          $p['itemtype2'][$i]);
            }
         }

         // b - ADD LEFT JOIN
         // Already link meta table in order not to linked a table several times
         $already_link_tables2 = array();
         // Link reference tables
         for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
            if (isset($p['itemtype2'][$i]) && !empty($p['itemtype2'][$i])
                && isset($p['contains2'][$i]) && (strlen($p['contains2'][$i]) > 0)) {

               if (!in_array(getTableForItemType($p['itemtype2'][$i]), $already_link_tables2)) {
                  $FROM .= self::addMetaLeftJoin($itemtype, $p['itemtype2'][$i],
                                                 $already_link_tables2,
                                                 (($p['contains2'][$i] == "NULL")
                                                  || (strstr($p['link2'][$i], "NOT"))));
               }
            }
         }
         // Link items tables
         for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
            if (isset($p['itemtype2'][$i]) && !empty($p['itemtype2'][$i])
                && isset($p['contains2'][$i]) && (strlen($p['contains2'][$i]) > 0)) {

               if (!isset($searchopt[$p['itemtype2'][$i]])) {
                  $searchopt[$p['itemtype2'][$i]] = &self::getOptions($p['itemtype2'][$i]);
               }
               if (!in_array($searchopt[$p['itemtype2'][$i]][$p['field2'][$i]]["table"]."_".$p['itemtype2'][$i],
                             $already_link_tables2)) {

                  $FROM .= self::addLeftJoin($p['itemtype2'][$i],
                                             getTableForItemType($p['itemtype2'][$i]),
                                             $already_link_tables2,
                                             $searchopt[$p['itemtype2'][$i]][$p['field2'][$i]]["table"],
                                             $searchopt[$p['itemtype2'][$i]][$p['field2'][$i]]["linkfield"],
                                             1, $p['itemtype2'][$i],
                                             $searchopt[$p['itemtype2'][$i]][$p['field2'][$i]]["joinparams"]);
               }
            }
         }
      }


      //// 6 - Add item ID
      // Add ID to the select
      if (!empty($itemtable)) {
         $SELECT .= "`$itemtable`.`id` AS id ";
      }


      //// 7 - Manage GROUP BY
      $GROUPBY = "";
      // Meta Search / Search All / Count tickets
      if (($_SESSION["glpisearchcount2"][$itemtype] > 0)
          || !empty($HAVING)
          || in_array('all', $p['field'])) {

         $GROUPBY = " GROUP BY `$itemtable`.`id`";
      }

      if (empty($GROUPBY)) {
         foreach ($toview as $key2 => $val2) {
            if (!empty($GROUPBY)) {
               break;
            }
            if (isset($searchopt[$itemtype][$val2]["forcegroupby"])) {
               $GROUPBY = " GROUP BY `$itemtable`.`id`";
            }
         }
      }

      // Specific search for others item linked  (META search)
      if (is_array($p['itemtype2'])) {
         for ($key=0 ; $key<$_SESSION["glpisearchcount2"][$itemtype] ; $key++) {
            if (isset($p['itemtype2'][$key]) && !empty($p['itemtype2'][$key])
                && isset($p['contains2'][$key]) && (strlen($p['contains2'][$key]) > 0)) {

               $LINK = "";

               // For AND NOT statement need to take into account all the group by items
               if (strstr($p['link2'][$key],"AND NOT")
                   || isset($searchopt[$p['itemtype2'][$key]][$p['field2'][$key]]["usehaving"])) {

                  $NOT = 0;
                  if (strstr($p['link2'][$key],"NOT")) {
                     $tmplink = " ".str_replace(" NOT","",$p['link2'][$key]);
                     $NOT     = 1;
                  } else {
                     $tmplink = " ".$p['link2'][$key];
                  }
                  if (!empty($HAVING)) {
                     $LINK = $tmplink;
                  }
                  $HAVING .= self::addHaving($LINK, $NOT, $p['itemtype2'][$key],
                                             $p['field2'][$key], $p['searchtype2'][$key],
                                             $p['contains2'][$key], 1, $key);
               } else { // Meta Where Search
                  $LINK = " ";
                  $NOT  = 0;
                  // Manage Link if not first item
                  if (is_array($p['link2'])
                      && isset($p['link2'][$key])
                      && strstr($p['link2'][$key],"NOT")) {

                     $tmplink = " ".str_replace(" NOT", "", $p['link2'][$key]);
                     $NOT     = 1;

                  } else if (is_array($p['link2']) && isset($p['link2'][$key])) {
                     $tmplink = " ".$p['link2'][$key];

                  } else {
                     $tmplink = " AND ";
                  }

                  if (!empty($WHERE)) {
                     $LINK = $tmplink;
                  }
                  $WHERE .= self::addWhere($LINK, $NOT, $p['itemtype2'][$key], $p['field2'][$key],
                                           $p['searchtype2'][$key], $p['contains2'][$key], 1);
               }
            }
         }
      }

      // Use a ReadOnly connection if available and configured to be used
      $DBread = DBConnection::getReadConnection();

      // If no research limit research to display item and compute number of item using simple request
      $nosearch = true;
      for ($i=0 ; $i<$_SESSION["glpisearchcount"][$itemtype] ; $i++) {
         if (isset($p['contains'][$i]) && (strlen($p['contains'][$i]) > 0)) {
            $nosearch = false;
         }
      }

      if ($_SESSION["glpisearchcount2"][$itemtype] > 0) {
         $nosearch = false;
      }

      $LIMIT   = "";
      $numrows = 0;
      //No search : count number of items using a simple count(ID) request and LIMIT search
      if ($nosearch) {
         $LIMIT = " LIMIT ".$p['start'].", ".$LIST_LIMIT;

         // Force group by for all the type -> need to count only on table ID
         if (!isset($searchopt[$itemtype][1]['forcegroupby'])) {
            $count = "count(*)";
         } else {
            $count = "count(DISTINCT `$itemtable`.`id`)";
         }
         // request currentuser for SQL supervision, not displayed
         $query_num = "SELECT $count,
                              '".Toolbox::addslashes_deep($_SESSION['glpiname'])."' AS currentuser
                       FROM `$itemtable`".
                       $COMMONLEFTJOIN;

         $first     = true;

         if (!empty($COMMONWHERE)) {
            $LINK = " AND " ;
            if ($first) {
               $LINK  = " WHERE ";
               $first = false;
            }
            $query_num .= $LINK.$COMMONWHERE;
         }
         // Union Search :
         if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
            $tmpquery = $query_num;
            $numrows  = 0;

            foreach ($CFG_GLPI[$CFG_GLPI["union_search_type"][$itemtype]] as $ctype) {
               $ctable = getTableForItemType($ctype);
               if (($citem = getItemForItemtype($ctype))
                   && $citem->canView()) {
                  // State case
                  if ($itemtype == 'AllAssets') {
                     $query_num = str_replace($CFG_GLPI["union_search_type"][$itemtype],
                                              $ctable, $tmpquery);
                     $query_num = str_replace($itemtype, $ctype, $query_num);
                     $query_num .= " AND `$ctable`.`id` IS NOT NULL ";

                     // Add deleted if item have it
                     if ($citem && $citem->maybeDeleted()) {
                        $query_num .= " AND `$ctable`.`is_deleted` = '0' ";
                     }

                     // Remove template items
                     if ($citem && $citem->maybeTemplate()) {
                        $query_num .= " AND `$ctable`.`is_template` = '0' ";
                     }

                  } else {// Ref table case
                     $reftable = getTableForItemType($itemtype);
                     if ($item && $item->maybeDeleted()) {
                        $tmpquery = str_replace("`".$CFG_GLPI["union_search_type"][$itemtype]."`.`is_deleted`",
                                                "`$reftable`.`is_deleted`", $tmpquery);
                     }
                     $replace  = "FROM `$reftable`
                                  INNER JOIN `$ctable`
                                       ON (`$reftable`.`items_id` =`$ctable`.`id`
                                           AND `$reftable`.`itemtype` = '$ctype')";

                     $query_num = str_replace("FROM `".$CFG_GLPI["union_search_type"][$itemtype]."`",
                                              $replace, $tmpquery);
                     $query_num = str_replace($CFG_GLPI["union_search_type"][$itemtype], $ctable,
                                              $query_num);

                  }
                  $query_num = str_replace("ENTITYRESTRICT",
                                           getEntitiesRestrictRequest('', $ctable, '', '',
                                                                      $citem->maybeRecursive()),
                                           $query_num);
                  $result_num = $DBread->query($query_num);
                  $numrows   += $DBread->result($result_num, 0, 0);
               }
            }

         } else {
            $result_num = $DBread->query($query_num);
            $numrows    = $DBread->result($result_num,0,0);
         }
      }

      // If export_all reset LIMIT condition
      if ($p['export_all']) {
         $LIMIT = "";
      }

      if (!empty($WHERE) || !empty($COMMONWHERE)) {
         if (!empty($COMMONWHERE)) {
            $WHERE = ' WHERE '.$COMMONWHERE.(!empty($WHERE)?' AND ( '.$WHERE.' )':'');
         } else {
            $WHERE = ' WHERE '.$WHERE.' ';
         }
         $first = false;
      }

      if (!empty($HAVING)) {
         $HAVING = ' HAVING '.$HAVING;
      }


      // Create QUERY
      if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
         $first = true;
         $QUERY = "";
         foreach ($CFG_GLPI[$CFG_GLPI["union_search_type"][$itemtype]] as $ctype) {
            $ctable = getTableForItemType($ctype);
            if (($citem = getItemForItemtype($ctype))
                && $citem->canView()) {
               if ($first) {
                  $first = false;
               } else {
                  $QUERY .= " UNION ";
               }
               $tmpquery = "";
               // AllAssets case
               if ($itemtype == 'AllAssets') {
                  $tmpquery = $SELECT.", '$ctype' AS TYPE ".
                              $FROM.
                              $WHERE;

                  if ($itemtype == 'AllAssets') {
                     $tmpquery .= " AND `$ctable`.`id` IS NOT NULL ";
                  }

                  // Add deleted if item have it
                  if ($citem && $citem->maybeDeleted()) {
                     $tmpquery .= " AND `$ctable`.`is_deleted` = '0' ";
                  }

                  // Remove template items
                  if ($citem && $citem->maybeTemplate()) {
                     $tmpquery .= " AND `$ctable`.`is_template` = '0' ";
                  }

                  $tmpquery.= $GROUPBY.
                              $HAVING;

                  $tmpquery = str_replace($CFG_GLPI["union_search_type"][$itemtype],
                                          $ctable, $tmpquery);
                  $tmpquery = str_replace($itemtype, $ctype, $tmpquery);

               } else {// Ref table case
                  $reftable = getTableForItemType($itemtype);

                  $tmpquery = $SELECT.", '$ctype' AS TYPE,
                                      `$reftable`.`id` AS refID, "."
                                      `$ctable`.`entities_id` AS ENTITY ".
                              $FROM.
                              $WHERE;
                  if ($item->maybeDeleted()) {
                     $tmpquery = str_replace("`".$CFG_GLPI["union_search_type"][$itemtype]."`.`is_deleted`",
                                             "`$reftable`.`is_deleted`", $tmpquery);
                  }


                  $replace = "FROM `$reftable`"."
                              INNER JOIN `$ctable`"."
                                 ON (`$reftable`.`items_id`=`$ctable`.`id`"."
                                     AND `$reftable`.`itemtype` = '$ctype')";
                  $tmpquery = str_replace("FROM `".$CFG_GLPI["union_search_type"][$itemtype]."`",
                                          $replace, $tmpquery);
                  $tmpquery = str_replace($CFG_GLPI["union_search_type"][$itemtype], $ctable,
                                          $tmpquery);
               }
               $tmpquery = str_replace("ENTITYRESTRICT",
                                       getEntitiesRestrictRequest('', $ctable, '', '',
                                                                  $citem->maybeRecursive()),
                                       $tmpquery);

               // SOFTWARE HACK
               if ($ctype == 'Software') {
                  $tmpquery = str_replace("glpi_softwares.serial", "''", $tmpquery);
                  $tmpquery = str_replace("glpi_softwares.otherserial", "''", $tmpquery);
               }
               $QUERY .= $tmpquery;
            }
         }
         if (empty($QUERY)) {
            echo self::showError($output_type);
            return;
         }
         $QUERY .= str_replace($CFG_GLPI["union_search_type"][$itemtype].".", "", $ORDER) . $LIMIT;
      } else {
         $QUERY = $SELECT.
                  $FROM.
                  $WHERE.
                  $GROUPBY.
                  $HAVING.
                  $ORDER.
                  $LIMIT;
      }

      $DBread->query("SET SESSION group_concat_max_len = 4096;");
      $result = $DBread->query($QUERY);
      /// Check group concat limit : if warning : increase limit
      if ($result2 = $DBread->query('SHOW WARNINGS')) {
         if ($DBread->numrows($result2) > 0) {
            $data = $DBread->fetch_assoc($result2);
            if ($data['Code'] == 1260) {
               $DBread->query("SET SESSION group_concat_max_len = 4194304;");
               $result = $DBread->query($QUERY);
            }
         }
      }


      // Get it from database and DISPLAY
      if ($result) {

         // if real search or complete export : get numrows from request
         if (!$nosearch
             || $p['export_all']) {
            $numrows = $DBread->numrows($result);
         }
         // Contruct Pager parameters
         $globallinkto = self::getArrayUrlLink("field", $p['field']).
                         self::getArrayUrlLink("link", $p['link']).
                         self::getArrayUrlLink("contains", $p['contains']).
                         self::getArrayUrlLink("searchtype", $p['searchtype']).
                         self::getArrayUrlLink("field2", $p['field2']).
                         self::getArrayUrlLink("contains2", $p['contains2']).
                         self::getArrayUrlLink("itemtype2", $p['itemtype2']).
                         self::getArrayUrlLink("searchtype2", $p['searchtype2']).
                         self::getArrayUrlLink("link2", $p['link2']);

         $parameters = "sort=".$p['sort']."&amp;order=".$p['order'].$globallinkto;

         // Not more used : clean pages : try to comment it
         /*
         $tmp=explode('?',$target,2);
         if (count($tmp)>1) {
            $target = $tmp[0];
            $parameters = $tmp[1].'&amp;'.$parameters;
         }
         */
         if ($output_type == self::GLOBAL_SEARCH) {
            if ($item = getItemForItemtype($itemtype)) {
               echo "<div class='center'><h2>".$item->getTypeName();
               // More items
               if ($numrows > ($p['start'] + self::GLOBAL_DISPLAY_COUNT)) {
                  echo " <a href='$target?$parameters'>".__('All')."</a>";
               }
               echo "</h2></div>\n";
            } else {
               return false;
            }
         }

         // If the begin of the view is before the number of items
         if ($p['start'] < $numrows) {
            // Display pager only for HTML
            if ($output_type == self::HTML_OUTPUT) {
               // For plugin add new parameter if available
               if ($plug = isPluginItemType($itemtype)) {
                  $function = 'plugin_'.$plug['plugin'].'_addParamFordynamicReport';

                  if (function_exists($function)) {
                     $out = $function($itemtype);
                     if (is_array($out) && count($out)) {
                        foreach ($out as $key => $val) {
                           if (is_array($val)) {
                              $parameters .= self::getArrayUrlLink($key, $val);
                           } else {
                              $parameters .= "&amp;$key=$val";
                           }
                        }
                     }
                  }
               }
               $search_config = "";
               if (Session::haveRight("search_config","w")
                   || Session::haveRight("search_config_global","w")) {

//                   Ajax::createModalWindow('searchconfig_window',
//                         $CFG_GLPI['root_doc']."/ajax/searchconfig.php?itemtype=$itemtype",
//                         array('title'    => __('Select default items to show'),
//                               'width'    => '1000'));

//                   $tmp = " class='pointer' onClick=\"searchconfig_window.show()\"";

                  $tmp = " class='pointer' onClick=\"var w = window.open('".$CFG_GLPI["root_doc"].
                         "/front/popup.php?popup=search_config&amp;itemtype=$itemtype' ,'glpipopup', ".
                         "'height=400, width=1000, top=100, left=100, scrollbars=yes'); w.focus();\"";

                  $search_config  = "<img alt=\"".__s('Select default items to show')."\" title=\"".
                                      __s('Select default items to show')."\" src='".
                                      $CFG_GLPI["root_doc"]."/pics/options_search.png' ";
                  $search_config .= $tmp.">";
               }

               Html::printPager($p['start'], $numrows, $target, $parameters, $itemtype, 0,
                                $search_config);
            }

            // Define begin and end var for loop
            // Search case
            $begin_display = $p['start'];
            $end_display   = min($numrows, $p['start']+$LIST_LIMIT);
            // No search Case
            if ($nosearch) {
               $begin_display = 0;
               $end_display   = min($numrows-$p['start'], $LIST_LIMIT);
            }

            // Export All case
            if ($p['export_all']) {
               $begin_display = 0;
               $end_display   = $numrows;
            }

            // Form to massive actions
            $isadmin = ($item && $item->canUpdate());
            if (!$isadmin
                && in_array($itemtype, $CFG_GLPI["infocom_types"])) {
               $isadmin = (Infocom::canUpdate() || Infocom::canCreate());
            }
            $showmassiveactions = false;
            if ($itemtype != 'AllAssets') {
               $showmassiveactions = count($item->getAllMassiveActions($p['is_deleted']));
               if ($showmassiveactions
                   && ($output_type == self::HTML_OUTPUT)) {
                  Html::openMassiveActionsForm('massform'.$itemtype);
                  $massiveactionparams = array('num_displayed' => $end_display-$begin_display,
                                               'fixed'         => false,
                                               'is_deleted'    => $p['is_deleted']);
                  Html::showMassiveActions($itemtype, $massiveactionparams);
               }
            }

            // Compute number of columns to display
            // Add toview elements
            $nbcols          = $toview_count;
            $already_printed = array();
            // Add meta search elements if real search (strlen>0) or only NOT search
            if (($_SESSION["glpisearchcount2"][$itemtype] > 0)
                && is_array($p['itemtype2'])) {

               for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
                  if (isset($p['itemtype2'][$i]) && !empty($p['itemtype2'][$i])
                      && isset($p['contains2'][$i]) && (strlen($p['contains2'][$i]) > 0)
                      && (!isset($p['link2'][$i]) || !strstr($p['link2'][$i],"NOT"))) {

                     if (!isset($already_printed[$p['itemtype2'][$i].$p['field2'][$i]])) {
                        $nbcols++;
                        $already_printed[$p['itemtype2'][$i].$p['field2'][$i]] = 1;
                     }
                  }
               }
            }

            if ($output_type == self::HTML_OUTPUT) { // HTML display - massive modif
               $nbcols++;
            }


            // Display List Header
            echo self::showHeader($output_type, $end_display-$begin_display+1, $nbcols);

            // New Line for Header Items Line
            $headers_line        = '';
            $headers_line_top    = '';
            $headers_line_bottom = '';
            echo self::showNewLine($output_type);
            $header_num = 1;
            if (($output_type == self::HTML_OUTPUT)
                && $showmassiveactions) { // HTML display - massive modif
               $headers_line_top    .= self::showHeaderItem($output_type,
                                                            Html::getCheckAllAsCheckbox('massform'.$itemtype),
                                                            $header_num, "", 0, $p['order']);
               $headers_line_bottom .= self::showHeaderItem($output_type,
                                                            Html::getCheckAllAsCheckbox('massform'.$itemtype),
                                                            $header_num, "", 0, $p['order']);
            }

            // Display column Headers for toview items
            foreach ($toview as $key => $val) {
               $linkto = '';
               if (!isset($searchopt[$itemtype][$val]['nosort'])
                   || !$searchopt[$itemtype][$val]['nosort']) {

                  $linkto = "$target?itemtype=$itemtype&amp;sort=".$val."&amp;order=".
                             (($p['order'] == "ASC") ?"DESC":"ASC")."&amp;start=".$p['start'].
                             $globallinkto;
               }
               $headers_line .= self::showHeaderItem($output_type,
                                                     $searchopt[$itemtype][$val]["name"],
                                                     $header_num, $linkto, ($p['sort'] == $val),
                                                     $p['order']);
            }

            // Display columns Headers for meta items
            $already_printed = array();
            if (($_SESSION["glpisearchcount2"][$itemtype] > 0)
                && is_array($p['itemtype2'])) {

               for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
                  if (isset($p['itemtype2'][$i]) && !empty($p['itemtype2'][$i])
                      && isset($p['contains2'][$i]) && (strlen($p['contains2'][$i]) > 0)) {

                     if (!isset($already_printed[$p['itemtype2'][$i].$p['field2'][$i]])) {
                        if (!isset($metanames[$p['itemtype2'][$i]])) {
                           if ($metaitem = getItemForItemtype($p['itemtype2'][$i])) {
                              $metanames[$p['itemtype2'][$i]] = $metaitem->getTypeName();
                           }
                        }

                        $headers_line .= self::showHeaderItem($output_type,
                                                              sprintf(__('%1$s - %2$s'),
                                                                      $metanames[$p['itemtype2'][$i]],
                                                                      $searchopt[$p['itemtype2'][$i]][$p['field2'][$i]]["name"]),
                                                              $header_num);
                        $already_printed[$p['itemtype2'][$i].$p['field2'][$i]] = 1;
                     }
                  }
               }
            }

            // Add specific column Header
            if ($itemtype == 'CartridgeItem') {
               $headers_line .= self::showHeaderItem($output_type, _n('Cartridge','Cartridges',2),
                                                     $header_num);
            }
            if ($itemtype == 'ConsumableItem') {
               $headers_line .= self::showHeaderItem($output_type, _n('Consumable','Consumables',2),
                                                     $header_num);
            }
            if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
               $headers_line .= self::showHeaderItem($output_type, __('Item type'), $header_num);
            }
            if (($itemtype == 'ReservationItem')
                && ($output_type == self::HTML_OUTPUT)) {
               $headers_line .= self::showHeaderItem($output_type, "&nbsp;", $header_num);
            }
            // End Line for column headers
            $headers_line        .= self::showEndLine($output_type);

            $headers_line_top    .= $headers_line;
            $headers_line_bottom .= $headers_line;

            echo $headers_line_top;

            // if real search seek to begin of items to display (because of complete search)
            if (!$nosearch) {
               $DBread->data_seek($result, $p['start']);
            }

            // Define begin and end var for loop
            // Search case
            $i = $begin_display;

            // Init list of items displayed
            if ($output_type == self::HTML_OUTPUT) {
               Session::initNavigateListItems($itemtype);
            }

            // Num of the row (1=header_line)
            $row_num = 1;

            $massiveaction_field = 'id';
            if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
               $massiveaction_field = 'refID';
            }

            // Display Loop
            while (($i < $numrows) && ($i < $end_display)) {
            // Column num
               $item_num = 1;
               // Get data and increment loop variables
               $data = $DBread->fetch_assoc($result);
               $i++;
               $row_num++;
               // New line
               echo self::showNewLine($output_type, ($i%2), $p['is_deleted']);

               // Add item in item list
               Session::addToNavigateListItems($itemtype, $data["id"]);

               if (($output_type == self::HTML_OUTPUT)
                   && $showmassiveactions) { // HTML display - massive modif
                  $tmpcheck = "";
                  if (($itemtype == 'Entity')
                      && !in_array($data["id"], $_SESSION["glpiactiveentities"])) {

                     $tmpcheck = "&nbsp;";

                  } else if ($item->maybeRecursive()
                              && !in_array($data["entities_id"], $_SESSION["glpiactiveentities"])) {
                     $tmpcheck = "&nbsp;";

                  } else {
                     $tmpcheck = Html::getMassiveActionCheckBox($itemtype, $data[$massiveaction_field]);
                  }
                  echo self::showItem($output_type, $tmpcheck, $item_num, $row_num, "width='10'");
               }

               // Print other toview items
               foreach ($toview as $key => $val) {
                  echo self::showItem($output_type, self::giveItem($itemtype, $val, $data, $key),
                                      $item_num, $row_num,
                                      self::displayConfigItem($itemtype, $val, $data, $key));
               }

               // Print Meta Item
               $already_printed = array();
               if (($_SESSION["glpisearchcount2"][$itemtype] > 0)
                   && is_array($p['itemtype2'])) {

                  for ($j=0 ; $j<$_SESSION["glpisearchcount2"][$itemtype] ; $j++) {
                     if (isset($p['itemtype2'][$j]) && !empty($p['itemtype2'][$j])
                         && isset($p['contains2'][$j]) && (strlen($p['contains2'][$j])  >0)) {

                        if (!isset($already_printed[$p['itemtype2'][$j].$p['field2'][$j]])) {
                           // General case
                           if (strpos($data["META_$j"],"$$$$") === false) {
                              $out = self::giveItem($p['itemtype2'][$j], $p['field2'][$j], $data,
                                                    $j, 1);
                              echo self::showItem($output_type, $out, $item_num, $row_num);

                           // Case of GROUP_CONCAT item : split item and multilline display
                           } else {
                              $split         = explode("$$$$", $data["META_$j"]);
                              $count_display = 0;
                              $out           = "";
                              $unit          = "";
                              $separate      = '<br>';

                              if (isset($searchopt[$p['itemtype2'][$j]][$p['field2'][$j]]['splititems'])
                                  && $searchopt[$p['itemtype2'][$j]][$p['field2'][$j]]['splititems']) {

                                 $separate = '<hr>';
                              }

                              if (isset($searchopt[$p['itemtype2'][$j]][$p['field2'][$j]]['unit'])) {
                                 $unit = $searchopt[$p['itemtype2'][$j]][$p['field2'][$j]]['unit'];
                              }

                              for ($k=0 ; $k<count($split) ; $k++) {
                                 if (($p['contains2'][$j] == "NULL")
                                     || (strlen($p['contains2'][$j]) == 0)
                                     || preg_match('/'.$p['contains2'][$j].'/i',$split[$k])
                                     || isset($searchopt[$p['itemtype2'][$j]][$p['field2'][$j]]['forcegroupby'])) {

                                    if ($count_display) {
                                       $out .= $separate;
                                    }
                                    $count_display++;

                                    // Manage Link to item
                                    $split2 = self::explodeWithID("$$", $split[$k]);
                                    if (isset($split2[1])) {
                                       if (isset($searchopt[$p['itemtype2'][$j]][$p['field2'][$j]]['datatype'])
                                           && ($searchopt[$p['itemtype2'][$j]][$p['field2'][$j]]['datatype'] == 'itemlink')) {
                                          $out .= "<a id='".$p['itemtype2'][$j].'_'.$data["id"].'_'.
                                                   $split2[1]."' ";
                                          $out .= "href=\"".
                                                   Toolbox::getItemTypeFormURL($p['itemtype2'][$j]).
                                                   "?id=".$split2[1]."\">";
                                          $out .= Dropdown::getValueWithUnit($split2[0],$unit);
                                          $linkout = $out;
                                          if ($_SESSION["glpiis_ids_visible"]
                                              || empty($split2[0])) {
                                             $linkout = sprintf(__('%1$s (%2$s)'), $linkout,
                                                                $split2[1]);
                                          }
                                          $out = $linkout."</a>";
                                       } else {
                                          $out .= Dropdown::getValueWithUnit($split2[0],$unit);
                                       }
                                    } else {
                                       $out .= Dropdown::getValueWithUnit($split[$k],$unit);
                                    }
                                 }
                              }
                              echo self::showItem($output_type, $out, $item_num, $row_num);
                           }
                           $already_printed[$p['itemtype2'][$j].$p['field2'][$j]] = 1;
                        }
                     }
                  }
               }
               // Specific column display
               if ($itemtype == 'CartridgeItem') {
                  echo self::showItem($output_type,
                                      Cartridge::getCount($data["id"], $data["ALARM"],
                                                          $output_type != self::HTML_OUTPUT),
                                      $item_num, $row_num);
               }
               if ($itemtype == 'ConsumableItem') {
                  echo self::showItem($output_type,
                                      Consumable::getCount($data["id"], $data["ALARM"],
                                                           $output_type != self::HTML_OUTPUT),
                                      $item_num, $row_num);
               }
               if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
                  $typename = $data["TYPE"];
                  if ($itemtmp = getItemForItemtype($data["TYPE"])) {
                     $typename = $itemtmp->getTypeName();
                  }
                  echo self::showItem($output_type, $typename, $item_num, $row_num);
               }
               if (($itemtype == 'ReservationItem')
                   && ($output_type == self::HTML_OUTPUT)) {
                  if ($data["ACTIVE"]) {
                     echo self::showItem($output_type,
                                         "<a href='reservation.php?reservationitems_id=".
                                           $data["refID"]."' title=\"".__s('See planning')."\">".
                                           "<img src=\"".$CFG_GLPI["root_doc"].
                                             "/pics/reservation-3.png\" alt='' title=''></a>",
                                         $item_num, $row_num, "class='center'");
                  } else {
                     echo self::showItem($output_type, "&nbsp;", $item_num, $row_num);
                  }
               }
               // End Line
               echo self::showEndLine($output_type);
            }

            $title = "";
            // Create title
            if (($output_type == self::PDF_OUTPUT_LANDSCAPE)
                || ($output_type == self::PDF_OUTPUT_PORTRAIT)) {

               if (($_SESSION["glpisearchcount"][$itemtype] > 0)
                   && (count($p['contains']) > 0)) {

                  for ($key=0 ; $key<$_SESSION["glpisearchcount"][$itemtype] ; $key++) {
                     $titlecontain = '';
                     if (strlen($p['contains'][$key]) > 0) {
                        if (isset($p["link"][$key])) {
                           $titlecontain = " ".$p["link"][$key]." ";
                        }
                        switch ($p['field'][$key]) {
                           case "all" :
                              $titlecontain = sprintf(__('%1$s %2$s'), $titlecontain, __('All'));
                              break;

                           case "view" :
                              $titlecontain = sprintf(__('%1$s %2$s'), $titlecontain,
                                                      __('Items seen'));
                              break;

                           default :
                              $titlecontain = sprintf(__('%1$s %2$s'), $titlecontain,
                                                      $searchopt[$itemtype][$p['field'][$key]]["name"]);
                        }

                        switch ($p['searchtype'][$key]) {
                           case "equals" :
                              if (in_array($searchopt[$itemtype][$p['field'][$key]]["field"],
                                           array('name', 'completename'))) {
                                 $titlecontain = sprintf(__('%1$s = %2$s'), $titlecontain,
                                                         Dropdown::getDropdownName($searchopt[$itemtype][$p['field'][$key]]["table"],
                                                                                   $p['contains'][$key]));
                              } else {
                                 $titlecontain = sprintf(__('%1$s = %2$s'), $titlecontain,
                                                         $p['contains'][$key]);
                              }
                              break;

                           case "notequals" :
                              if (in_array($searchopt[$itemtype][$p['field'][$key]]["field"],
                                           array('name', 'completename'))) {
                                 $titlecontain = sprintf(__('%1$s <> %2$s'), $titlecontain,
                                                         Dropdown::getDropdownName($searchopt[$itemtype][$p['field'][$key]]["table"],
                                                                                   $p['contains'][$key]));
                              } else {
                                 $titlecontain = sprintf(__('%1$s <> %2$s'), $titlecontain,
                                                         $p['contains'][$key]);
                              }
                              break;

                           case "lessthan" :
                              $titlecontain = sprintf(__('%1$s < %2$s'), $titlecontain,
                                                      $p['contains'][$key]);
                              break;

                           case "morethan" :
                              $titlecontain = sprintf(__('%1$s > %2$s'), $titlecontain,
                                                      $p['contains'][$key]);
                              break;

                           case "contains" :
                              $titlecontain = sprintf(__('%1$s = %2$s'), $titlecontain,
                                                      '%'.$p['contains'][$key].'%');
                              break;

                           case "under" :
                              $titlecontain = sprintf(__('%1$s %2$s'), $titlecontain,
                                                      sprintf(__('%1$s %2$s'), __('under'),
                                                              Dropdown::getDropdownName($searchopt[$itemtype][$p['field'][$key]]["table"],
                                                                                        $p['contains'][$key])));
                              break;

                           case "notunder" :
                              $titlecontain = sprintf(__('%1$s %2$s'), $titlecontain,
                                                      sprintf(__('%1$s %2$s'), __('not under'),
                                                              Dropdown::getDropdownName($searchopt[$itemtype][$p['field'][$key]]["table"],
                                                                                        $p['contains'][$key])));
                              break;

                           default :
                              $titlecontain = sprintf(__('%1$s = %2$s'), $titlecontain,
                                                      $p['contains'][$key]);
                              break;
                        }
                     }
                     $title .= $titlecontain;
                  }
               }
               if (($_SESSION["glpisearchcount2"][$itemtype] > 0)
                   && (count($p['contains2']) > 0)) {
                  for ($key=0 ; $key<$_SESSION["glpisearchcount2"][$itemtype] ; $key++) {
                     $titlecontain2 = '';
                     if (strlen($p['contains2'][$key]) > 0) {
                        if (isset($p['link2'][$key])) {
                           $titlecontain2 = sprintf(__('%1$s %2$s'), $titlecontain2,
                                                    $p['link2'][$key]);
                        }
                        $titlecontain2 = sprintf(__('%1$s %2$s'), $titlecontain2,
                                                 sprintf(__('%1$s / %2$s'),
                                                         $metanames[$p['itemtype2'][$key]],
                                                         $searchopt[$p['itemtype2'][$key]][$p['field2'][$key]]["name"]));

                        switch ($p['searchtype2'][$key]) {
                           case "equals" :
                              if (in_array($searchopt[$p['itemtype2'][$key]][$p['field2'][$key]]["field"],
                                           array('name', 'completename'))) {
                                 $titlecontain2 = sprintf(__('%1$s = %2$s'), $titlecontain2,
                                                          Dropdown::getDropdownName($searchopt[$p['itemtype2'][$key]][$p['field2'][$key]]["table"],
                                                                                    $p['contains2'][$key]));
                              } else {
                                 $titlecontain2 = sprintf(__('%1$s = %2$s'), $titlecontain2,
                                                          $p['contains2'][$key]);
                              }
                              break;

                           case "notequals" :
                              if (in_array($searchopt[$p['itemtype2'][$key]][$p['field2'][$key]]["field"],
                                           array('name', 'completename'))) {
                                 $titlecontain2 = sprintf(__('%1$s <> %2$s'), $titlecontain2,
                                                          Dropdown::getDropdownName($searchopt[$p['itemtype2'][$key]][$p['field2'][$key]]["table"],
                                                                                    $p['contains2'][$key]));
                              } else {
                                 $titlecontain2 = sprintf(__('%1$s <> %2$s'), $titlecontain2,
                                                          $p['contains2'][$key]);
                              }
                              break;

                           case "lessthan" :
                              $titlecontain2 = sprintf(__('%1$s < %2$s'), $titlecontain2,
                                                       $p['contains2'][$key]);
                              break;

                           case "morethan" :
                              $titlecontain2 = sprintf(__('%1$s > %2$s'), $titlecontain2,
                                                       $p['contains2'][$key]);
                              break;

                           case "contains" :
                              $titlecontain2 = sprintf(__('%1$s = %2$s'), $titlecontain2,
                                                       '%'.$p['contains2'][$key].'%');
                              break;

                           case "under" :
                              $titlecontain2 = sprintf(__('%1$s %2$s'), $titlecontain2,
                                                       sprintf(__('%1$s %2$s'), __('under'),
                                                               Dropdown::getDropdownName($searchopt[$p['itemtype2'][$key]][$p['field2'][$key]]["table"],
                                                                                         $p['contains2'][$key])));
                              break;

                           case "notunder" :
                              $titlecontain2 = sprintf(__('%1$s %2$s'), $titlecontain2,
                                                       sprintf(__('%1$s %2$s'), __('not under'),
                                                               Dropdown::getDropdownName($searchopt[$p['itemtype2'][$key]][$p['field2'][$key]]["table"],
                                                                                         $p['contains2'][$key])));
                              break;

                           default :
                              $titlecontain2 = sprintf(__('%1$s = %2$s'), $titlecontain2,
                                                       $p['contains2'][$key]);
                              break;
                        }
                     }
                     $title .= $titlecontain2;
                  }
               }
            }

            if ($output_type == self::HTML_OUTPUT) {
               echo $headers_line_bottom;
            }
            // Display footer
            echo self::showFooter($output_type, $title);

            // Delete selected item
            if ($output_type == self::HTML_OUTPUT) {
               if ($showmassiveactions) {
                  $massiveactionparams['ontop'] = false;
                  Html::showMassiveActions($itemtype, $massiveactionparams);
                  // End form for delete item
                  Html::closeForm();
               } else {
                  echo "<br>";
               }
            }
            if ($output_type == self::HTML_OUTPUT) { // In case of HTML display
               Html::printPager($p['start'], $numrows, $target, $parameters, '', 0, $search_config);

            }
         } else {
            echo self::showError($output_type);
         }
      } else {
         echo $DBread->error();
      }
   }

   /**
    * Get meta types available for search engine
    *
    * @param $itemtype type to display the form
    *
    * @return Array of available itemtype
   **/
   static function getMetaItemtypeAvailable ($itemtype) {

      // Display meta search items
      $linked = array();
      // Define meta search items to linked
      switch ($itemtype) {
         case 'Computer' :
            $linked = array('Monitor', 'Peripheral', 'Phone', 'Printer', 'Software');
            break;

         case 'Ticket' :
            if (Session::haveRight("show_all_ticket","1")) {
               $linked = array_keys(Ticket::getAllTypesForHelpdesk());
            }
            break;

         case 'Printer' :
         case 'Monitor' :
         case 'Peripheral' :
         case 'Software' :
         case 'Phone' :
            $linked = array('Computer');
            break;
      }
      return $linked;
   }


   /**
    * Print generic search form
    *
    * @param $itemtype        type to display the form
    * @param $params    array of parameters may include field, contains, sort, is_deleted, link,
    *                         link2, contains2, field2, type2
    *
    * @return nothing (displays)
   **/
   static function showGenericSearch($itemtype, array $params) {
      global $CFG_GLPI;

      // Default values of parameters
      $p['link']        = array();//
      $p['field']       = array();
      $p['contains']    = array();
      $p['searchtype']  = array();
      $p['sort']        = '';
      $p['is_deleted']  = 0;
      $p['link2']       = '';//
      $p['contains2']   = '';
      $p['field2']      = '';
      $p['itemtype2']   = '';
      $p['searchtype2'] = '';

      foreach ($params as $key => $val) {
         $p[$key] = $val;
      }

      $options = self::getCleanedOptions($itemtype);
      $target  = Toolbox::getItemTypeSearchURL($itemtype);

      // Instanciate an object to access method
      $item = NULL;
      if ($itemtype != 'AllAssets') {
         $item = getItemForItemtype($itemtype);
      }

      $linked =  self::getMetaItemtypeAvailable($itemtype);

      echo "<form name='searchform$itemtype' method='get' action=\"$target\">";
      echo "<div id='searchcriterias'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";

      if (($_SESSION["glpisearchcount"][$itemtype] + $_SESSION["glpisearchcount2"][$itemtype]) > 1) {
         echo "<td width='10' class='center'>";
         echo "<a href=\"javascript:toggleTableDisplay('searchcriteriastable','searchcriteriasimg',
                                                       '".$CFG_GLPI["root_doc"]."/pics/deplier_down.png',
                                                       '".$CFG_GLPI["root_doc"]."/pics/deplier_up.png')\">";
         echo "<img alt='' name='searchcriteriasimg' src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\">";
         echo "</td>";
      }
      echo "<td>";

      echo "<table id='searchcriteriastable'>";
      // Display normal search parameters
      for ($i=0 ; $i<$_SESSION["glpisearchcount"][$itemtype] ; $i++) {
         echo "<tr ".($i==0?"class='headerRow'":'')."><td class='left' width='50%'>";
         // First line display add / delete images for normal and meta search items
         if ($i == 0) {
            echo "<input type='hidden' disabled id='add_search_count' name='add_search_count'
                   value='1'>";
            echo "<a href='#' onClick = \"document.getElementById('add_search_count').disabled=false;
                   document.forms['searchform$itemtype'].submit();\">";
            echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title=\"".
                   __s('Add a search criterion')."\"></a>&nbsp;&nbsp;&nbsp;&nbsp;";
            if ($_SESSION["glpisearchcount"][$itemtype] > 1) {
               echo "<input type='hidden' disabled id='delete_search_count'
                      name='delete_search_count' value='1'>";
               echo "<a href='#' onClick = \"document.getElementById('delete_search_count').disabled=false;
                      document.forms['searchform$itemtype'].submit();\">";
               echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/moins.png\" alt='-' title=\"".
                      __s('Delete a search criterion')."\"></a>&nbsp;&nbsp;&nbsp;&nbsp;";
            }
            if (is_array($linked) && (count($linked) > 0)) {
               echo "<input type='hidden' disabled id='add_search_count2' name='add_search_count2'
                      value='1'>";
               echo "<a href='#' onClick=\"document.getElementById('add_search_count2').disabled=false;
                      document.forms['searchform$itemtype'].submit();\">";
               echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/meta_plus.png\" alt='+' title=\"".
                      __s('Add a global search criterion')."\"></a>&nbsp;&nbsp;&nbsp;&nbsp;";

               if ($_SESSION["glpisearchcount2"][$itemtype] > 0) {
                  echo "<input type='hidden' disabled id='delete_search_count2'
                         name='delete_search_count2' value='1'>";
                  echo "<a href='#' onClick=\"document.getElementById('delete_search_count2').disabled=false;
                         document.forms['searchform$itemtype'].submit();\">";
                  echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/meta_moins.png\" alt='-' title=\"".
                         __s('Delete a global search criterion')."\"></a>&nbsp;&nbsp;&nbsp;&nbsp;";
               }
            }

            $itemtable = getTableForItemType($itemtype);
            if ($item && $item->maybeDeleted()) {
               echo "<input type='hidden' id='is_deleted' name='is_deleted' value='".
                      $p['is_deleted']."'>";
               echo "<a href='#' onClick = \"toogle('is_deleted','','','');
                      document.forms['searchform$itemtype'].submit();\">
                      <img src=\"".$CFG_GLPI["root_doc"]."/pics/showdeleted".
                       (!$p['is_deleted']?'_no':'').".png\" name='img_deleted' alt=\"".
                       (!$p['is_deleted']?__s('Show the dustbin'):__s("Don't show deleted items")).
                      "\" title=\"".
                       (!$p['is_deleted']?__s('Show the dustbin'):__s("Don't show deleted items")).
                      "\"></a>";
               echo '&nbsp;&nbsp;';
            }
         }



         // Display link item
         if ($i > 0) {
            echo "<select name='link[$i]'>";
            echo "<option value = 'AND' ";
            if (is_array($p["link"])
                && isset($p["link"][$i]) && ($p["link"][$i] == "AND")) {
               echo "selected";
            }
            echo ">AND</option>\n";

            echo "<option value='OR' ";
            if (is_array($p["link"])
                && isset($p["link"][$i]) && ($p["link"][$i] == "OR")) {
               echo "selected";
            }
            echo ">OR</option>\n";

            echo "<option value='AND NOT' ";
            if (is_array($p["link"])
                && isset($p["link"][$i]) && ($p["link"][$i] == "AND NOT")) {
               echo "selected";
            }
            echo ">AND NOT</option>\n";

            echo "<option value='OR NOT' ";
            if (is_array($p["link"])
                && isset($p["link"][$i]) && ($p["link"][$i] == "OR NOT")) {
               echo "selected";
            }
            echo ">OR NOT</option>";
            echo "</select>&nbsp;";
         }


         $selected = $first = '';
         // display select box to define search item
         echo "<select id='Search$itemtype$i' name=\"field[$i]\" size='1'>";
         if ($CFG_GLPI['allow_search_view'] == 2) {
            echo "<option value='view' ";
            if (is_array($p['field'])
                && isset($p['field'][$i]) && ($p['field'][$i] == "view")) {
               echo "selected";
               $selected = 'view';
            }
            echo ">".__('Items seen')."</option>\n";
         }

         reset($options);
         $first_group = true;
         $str_limit   = 28; // Not $_SESSION['glpidropdown_chars_limit'] because must be really to short (5 or 10) and search engine will be unusable
         $nb_in_group = 0;
         $group       = '';

         foreach ($options as $key => $val) {
            // print groups
            if (!is_array($val)) {
               if (!$first_group) {
                  $group .= "</optgroup>\n";
               } else {
                  $first_group = false;
               }
               if ($nb_in_group) {
                  echo $group;
               }
               $group       = '';
               $nb_in_group = 0;

               $group .= "<optgroup label=\"".Toolbox::substr($val,0,$str_limit)."\">";
            } else {
               if (!isset($val['nosearch']) || ($val['nosearch'] == false)) {
                  $nb_in_group ++;
                  $group .= "<option title=\"".Html::cleanInputText($val["name"]).
                                           "\" value='$key'";
                  if (is_array($p['field'])
                      && isset($p['field'][$i]) && ($key == $p['field'][$i])) {
                     $group   .= "selected";
                     $selected = $key;
                  } else if (empty($first)) {
                     $first = $key;
                  }
                  $group .= ">". Toolbox::substr($val["name"], 0, $str_limit) ."</option>\n";
               }
            }
         }
         if (!$first_group) {
            $group .= "</optgroup>\n";
         }
         if ($nb_in_group) {
            echo $group;
         }
         if ($CFG_GLPI['allow_search_view'] == 1) {
            echo "<option value='view' ";
            if (is_array($p['field'])
                && isset($p['field'][$i]) && ($p['field'][$i] == "view")) {
               echo "selected";
               $selected = 'view';
            }
            echo ">".__('Items seen')."</option>\n";
         }
         if ($CFG_GLPI['allow_search_all']) {
            echo "<option value='all' ";
            if (is_array($p['field'])
                && isset($p['field'][$i]) && ($p['field'][$i] == "all")) {
               echo "selected";
               $selected = 'all';
            }
            echo ">".__('All')."</option>";
         }
         if (empty($selected)) {
            $selected = $first;
         }
         echo "</select>\n";

         echo "</td><td class='left'>";
         echo "<div id='SearchSpan$itemtype$i'>\n";

         $used_itemtype = $itemtype;

         // Force Computer itemtype for AllAssets to permit to show specific items
         if ($itemtype == 'AllAssets') {
            $used_itemtype = 'Computer';
         }

         $_POST['itemtype']   = $used_itemtype;

         $_POST['num']        = $i;
         $_POST['field']      = $selected;
         $_POST['searchtype'] = (is_array($p['searchtype'])
                                 && isset($p['searchtype'][$i])?$p['searchtype'][$i]:"" );
         $_POST['value']      = (is_array($p['contains'])
                                 && isset($p['contains'][$i])?stripslashes($p['contains'][$i]):"" );
         include (GLPI_ROOT."/ajax/searchoption.php");
         echo "</div>\n";

         $params = array('field'      => '__VALUE__',
                         'itemtype'   => $used_itemtype,
                         'num'        => $i,
                         'value'      => $_POST["value"],
                         'searchtype' => $_POST["searchtype"]);
         Ajax::updateItemOnSelectEvent("Search$itemtype$i", "SearchSpan$itemtype$i",
                                       $CFG_GLPI["root_doc"]."/ajax/searchoption.php", $params);

         echo "</td></tr>\n";
      }


      $metanames = array();

      if (is_array($linked) && (count($linked) > 0)) {
         for ($i=0 ; $i<$_SESSION["glpisearchcount2"][$itemtype] ; $i++) {
            echo "<tr><td class='left' colspan='2'>";
            $rand = mt_rand();

            echo "<table width='100%'><tr class='left'><td width='35%'>";
            // Display link item (not for the first item)
            echo "<select name='link2[$i]'>";
            echo "<option value='AND' ";
            if (is_array($p['link2'])
                && isset($p['link2'][$i]) && ($p['link2'][$i] == "AND")) {
               echo "selected";
            }
            echo ">AND</option>\n";

            echo "<option value='OR' ";
            if (is_array($p['link2'])
                && isset($p['link2'][$i]) && ($p['link2'][$i] == "OR")) {
               echo "selected";
            }
            echo ">OR</option>\n";

            echo "<option value='AND NOT' ";
            if (is_array($p['link2'])
                && isset($p['link2'][$i]) && ($p['link2'][$i] == "AND NOT")) {
               echo "selected";
            }
            echo ">AND NOT</option>\n";

            echo "<option value='OR NOT' ";
            if (is_array($p['link2'] )
                && isset($p['link2'][$i]) && ($p['link2'][$i] == "OR NOT")) {
               echo "selected";
            }
            echo ">OR NOT</option>\n";
            echo "</select>&nbsp;";

            // Display select of the linked item type available
            echo "<select name='itemtype2[$i]' id='itemtype2_".$itemtype."_".$i."_$rand'>";
            echo "<option value=''>".Dropdown::EMPTY_VALUE."</option>";
            foreach ($linked as $key) {
               if (!isset($metanames[$key])) {
                  if ($linkitem = getItemForItemtype($key)) {
                     $metanames[$key] = $linkitem->getTypeName();
                  }
               }
               echo "<option value='$key'>".Toolbox::substr($metanames[$key], 0, 20)."</option>\n";
            }
            echo "</select>&nbsp;";

            echo "</td><td>";
            // Ajax script for display search met& item
            echo "<span id='show_".$itemtype."_".$i."_$rand'>&nbsp;</span>\n";

            $params = array('itemtype'    => '__VALUE__',
                            'num'         => $i,
                            'field'       => (is_array($p['field2'])
                                              && isset($p['field2'][$i])?$p['field2'][$i]:""),
                            'value'       => (is_array($p['contains2'])
                                              && isset($p['contains2'][$i])?$p['contains2'][$i]:""),
                            'searchtype2' => (is_array($p['searchtype2'])
                                              && isset($p['searchtype2'][$i])?$p['searchtype2'][$i]:""));

            Ajax::updateItemOnSelectEvent("itemtype2_".$itemtype."_".$i."_$rand",
                                          "show_".$itemtype."_".$i."_$rand",
                                          $CFG_GLPI["root_doc"]."/ajax/updateMetaSearch.php",
                                          $params);

            if (is_array($p['itemtype2'])
                && isset($p['itemtype2'][$i])
                && !empty($p['itemtype2'][$i])) {

               $params['itemtype'] = $p['itemtype2'][$i];
               Ajax::updateItem("show_".$itemtype."_".$i."_$rand",
                                $CFG_GLPI["root_doc"]."/ajax/updateMetaSearch.php", $params);
               echo "<script type='text/javascript' >";
               echo "window.document.getElementById('itemtype2_".$itemtype."_".$i."_$rand').value='".
                                                    $p['itemtype2'][$i]."';";
               echo "</script>\n";
            }
            echo "</td></tr></table>";

            echo "</td></tr>\n";
         }
      }
      echo "</table>\n";
      echo "</td>\n";

      echo "<td width='150px'>";
      echo "<table width='100%'>";
      // Display sort selection
/*      echo "<tr><td colspan='2'>".__('sorted by');
      echo "&nbsp;<select name='sort' size='1'>";
      reset($options);
      $first_group=true;
      foreach ($options as $key => $val) {
         if (!is_array($val)) {
            if (!$first_group) {
               echo "</optgroup>\n";
            } else {
               $first_group=false;
            }
            echo "<optgroup label=\"$val\">";
         } else {
            echo "<option value='$key'";
            if ($key == $p['sort']) {
               echo " selected";
            }
            echo ">".Toolbox::substr($val["name"],0,20)."</option>\n";
         }
      }
      if (!$first_group) {
         echo "</optgroup>\n";
      }
      echo "</select> ";
      echo "</td></tr>\n";
*/
      // Display deleted selection

      echo "<tr>";

      // Display submit button
      echo "<td width='80' class='center'>";
      echo "<input type='submit' value=\""._sx('button', 'Search')."\" class='submit' >";
      echo "</td><td>";
      Bookmark::showSaveButton(Bookmark::SEARCH, $itemtype);
      echo "<a href='$target?reset=reset' >";
      echo "&nbsp;&nbsp;<img title=\"".__s('Blank')."\" alt=\"".__s('Blank')."\" src='".
            $CFG_GLPI["root_doc"]."/pics/reset.png' class='calendrier'></a>";

      echo "</td></tr></table>\n";

      echo "</td></tr>";
      echo "</table>\n";

      // For dropdown
      echo "<input type='hidden' name='itemtype' value='$itemtype'>";

      // Reset to start when submit new search
      echo "<input type='hidden' name='start' value='0'>";
      echo "</div>";
      Html::closeForm();
   }


   /**
    * Generic Function to add GROUP BY to a request
    *
    * @param $LINK           link to use
    * @param $NOT            is is a negative search ?
    * @param $itemtype       item type
    * @param $ID             ID of the item to search
    * @param $searchtype     search type ('contains' or 'equals')
    * @param $val            value search
    * @param $meta           is it a meta item ?
    * @param $num            item number
    *
    * @return select string
   **/
   static function addHaving($LINK, $NOT, $itemtype, $ID, $searchtype, $val, $meta, $num) {

      $searchopt  = &self::getOptions($itemtype);
      $table      = $searchopt[$ID]["table"];
      $field      = $searchopt[$ID]["field"];

      $NAME = "ITEM_";
      if ($meta) {
         $NAME = "META_";
      }

      // Plugin can override core definition for its type
      if ($plug = isPluginItemType($itemtype)) {
         $function = 'plugin_'.$plug['plugin'].'_addHaving';
         if (function_exists($function)) {
            $out = $function($LINK, $NOT, $itemtype, $ID, $val, $num);
            if (!empty($out)) {
               return $out;
            }
         }
      }

      //// Default cases
      // Link with plugin tables
      if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
         if (count($matches) == 2) {
            $plug     = $matches[1];
            $function = 'plugin_'.$plug.'_addHaving';
            if (function_exists($function)) {
               $out = $function($LINK, $NOT, $itemtype, $ID, $val, $num);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }

      // Preformat items
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "number" :
            case "decimal" :
            case "timestamp" :
               $search  = array("/\&lt;/","/\&gt;/");
               $replace = array("<",">");
               $val     = preg_replace($search, $replace, $val);
               if (preg_match("/([<>])([=]*)[[:space:]]*([0-9]+)/",$val,$regs)) {
                  if ($NOT) {
                     if ($regs[1] == '<') {
                        $regs[1] = '>';
                     } else {
                        $regs[1] = '<';
                     }
                  }
                  $regs[1] .= $regs[2];
                  return " $LINK (`$NAME$num` ".$regs[1]." ".$regs[3]." ) ";
               }

               if (is_numeric($val)) {
                  if (isset($searchopt[$ID]["width"])) {
                     if (!$NOT) {
                        return " $LINK (`$NAME$num` < ".(intval($val) + $searchopt[$ID]["width"])."
                                        AND `$NAME$num` > ".(intval($val) - $searchopt[$ID]["width"]).") ";
                     }
                     return " $LINK (`$NAME$num` > ".(intval($val) + $searchopt[$ID]["width"])."
                                     OR `$NAME$num` < ".(intval($val) - $searchopt[$ID]["width"])." ) ";
                  }
                  // Exact search
                  if (!$NOT) {
                     return " $LINK (`$NAME$num` = ".(intval($val)).") ";
                  }
                  return " $LINK (`$NAME$num` <> ".(intval($val)).") ";
               }
               break;
         }
      }

/*
      $ADD="";
      if (($NOT && $val!="NULL")
         || $val=='^$') {

         $ADD = " OR `$NAME$num` IS NULL";
      }

      return " $LINK (`$NAME$num`".self::makeTextSearch($val,$NOT)."
                     $ADD ) ";
*/
      return self::makeTextCriteria("`$NAME$num`",$val,$NOT,$LINK);
   }


   /**
    * Generic Function to add ORDER BY to a request
    *
    * @param $itemtype  ID of the device type
    * @param $ID        field to add
    * @param $order     order define
    * @param $key       item number (default 0)
    *
    * @return select string
    *
   **/
   static function addOrderBy($itemtype, $ID, $order, $key=0) {
      global $CFG_GLPI;

      // Security test for order
      if ($order != "ASC") {
         $order = "DESC";
      }
      $searchopt = &self::getOptions($itemtype);

      $table     = $searchopt[$ID]["table"];
      $field     = $searchopt[$ID]["field"];


      $addtable = '';

      if (($table != getTableForItemType($itemtype))
          && ($searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table))) {
         $addtable .= "_".$searchopt[$ID]["linkfield"];
      }

      if (isset($searchopt[$ID]['joinparams'])) {
         $complexjoin = self::computeComplexJoinID($searchopt[$ID]['joinparams']);

         if (!empty($complexjoin)) {
            $addtable .= "_".$complexjoin;
         }
      }

      if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
         return " ORDER BY ITEM_$key $order ";
      }

      // Plugin can override core definition for its type
      if ($plug = isPluginItemType($itemtype)) {
         $function = 'plugin_'.$plug['plugin'].'_addOrderBy';
         if (function_exists($function)) {
            $out = $function($itemtype, $ID, $order, $key);
            if (!empty($out)) {
               return $out;
            }
         }
      }

      switch($table.".".$field) {
         case "glpi_auth_tables.name" :
            $user_searchopt = self::getOptions('User');
            return " ORDER BY `glpi_users`.`authtype` $order,
                              `glpi_authldaps".$addtable."_".
                                 self::computeComplexJoinID($user_searchopt[30]['joinparams'])."`.`name` $order,
                              `glpi_authmails".$addtable."_".
                                 self::computeComplexJoinID($user_searchopt[31]['joinparams'])."`.`name` $order ";

         case "glpi_users.name" :
            if ($itemtype!='User') {
               return " ORDER BY ".$table.$addtable.".`realname` $order,
                                 ".$table.$addtable.".`firstname` $order,
                                 ".$table.$addtable.".`name` $order";
            }
            return " ORDER BY `".$table."`.`name` $order";

         case "glpi_networkequipments.ip" :
         case "glpi_ipaddresses.name" :
            return " ORDER BY INET_ATON($table$addtable.$field) $order ";
      }

      //// Default cases

      // Link with plugin tables
      if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
         if (count($matches) == 2) {
            $plug     = $matches[1];
            $function = 'plugin_'.$plug.'_addOrderBy';
            if (function_exists($function)) {
               $out = $function($itemtype, $ID, $order, $key);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }

      // Preformat items
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "date_delay" :
               $interval = "MONTH";
               if (isset($searchopt[$ID]['delayunit'])) {
                  $interval = $searchopt[$ID]['delayunit'];
               }

               $add_minus = '';
               if (isset($searchopt[$ID]["datafields"][3])) {
                  $add_minus = "- `$table$addtable`.`".$searchopt[$ID]["datafields"][3]."`";
               }
               return " ORDER BY ADDDATE(`$table$addtable`.`".$searchopt[$ID]["datafields"][1]."`,
                                         INTERVAL (`$table$addtable`.`".$searchopt[$ID]["datafields"][2].
                                                   "` $add_minus)
                                         $interval) $order ";
         }
      }

      //return " ORDER BY $table.$field $order ";
      return " ORDER BY ITEM_$key $order ";

   }


   /**
    * Generic Function to add default columns to view
    *
    * @param $itemtype device type
    *
    * @return select string
   **/
   static function addDefaultToView($itemtype) {
      global $CFG_GLPI;

      $toview = array();
      $item   = NULL;
      if ($itemtype != 'AllAssets') {
         $item = getItemForItemtype($itemtype);
      }
      // Add first element (name)
      array_push($toview, 1);

      // Add entity view :
      if (Session::isMultiEntitiesMode()
          && (isset($CFG_GLPI["union_search_type"][$itemtype])
              || ($item && $item->maybeRecursive())
              || (count($_SESSION["glpiactiveentities"]) > 1))) {
         array_push($toview, 80);
      }
      return $toview;
   }


   /**
    * Generic Function to add default select to a request
    *
    * @param $itemtype device type
    *
    * @return select string
   **/
   static function addDefaultSelect($itemtype) {

      $itemtable = getTableForItemType($itemtype);
      $item      = NULL;
      $mayberecursive = false;
      if ($itemtype != 'AllAssets') {
         $item           = getItemForItemtype($itemtype);
         $mayberecursive = $item->maybeRecursive();
      }
      $ret = "";
      switch ($itemtype) {
         case 'ReservationItem' :
            $ret = "`glpi_reservationitems`.`is_active` AS ACTIVE, ";
            break;

         case 'CartridgeItem' :
            $ret = "`glpi_cartridgeitems`.`alarm_threshold` AS ALARM, ";
            break;

         case 'ConsumableItem' :
            $ret = "`glpi_consumableitems`.`alarm_threshold` AS ALARM, ";
            break;

         case 'FieldUnicity' :
            $ret = "`glpi_fieldunicities`.`itemtype` AS ITEMTYPE,";
            break;

         default :
            // Plugin can override core definition for its type
            if ($plug = isPluginItemType($itemtype)) {
               $function = 'plugin_'.$plug['plugin'].'_addDefaultSelect';
               if (function_exists($function)) {
                  $ret = $function($itemtype);
               }
            }
      }
      if ($itemtable == 'glpi_entities') {
         $ret .= "`$itemtable`.`id` AS entities_id, '1' AS is_recursive, ";
      } else if ($mayberecursive) {
         $ret .= "`$itemtable`.`entities_id`, `$itemtable`.`is_recursive`, ";
      }
      return $ret;
   }


   /**
    * Generic Function to add select to a request
    *
    * @param $itemtype     item type
    * @param $ID           ID of the item to add
    * @param $num          item num in the reque (default 0)
    * @param $meta         boolean is a meta
    * @param $meta_type    meta type table ID (default 0)
    *
    * @return select string
   **/
   static function addSelect($itemtype, $ID, $num, $meta=0, $meta_type=0) {
      global $CFG_GLPI;

      $searchopt   = &self::getOptions($itemtype);
      $table       = $searchopt[$ID]["table"];
      $field       = $searchopt[$ID]["field"];
      $addtable    = "";
      $NAME        = "ITEM";
      $complexjoin = '';

      if (isset($searchopt[$ID]['joinparams'])) {
         $complexjoin = self::computeComplexJoinID($searchopt[$ID]['joinparams']);
      }

      if (((($table != getTableForItemType($itemtype))
            && (!isset($CFG_GLPI["union_search_type"][$itemtype])
                || ($CFG_GLPI["union_search_type"][$itemtype] != $table)))
           || !empty($complexjoin))
          && ($searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table))) {
         $addtable .= "_".$searchopt[$ID]["linkfield"];
      }

      if (!empty($complexjoin)) {
         $addtable .= "_".$complexjoin;
      }

      if ($meta) {
         $NAME = "META";
         if (getTableForItemType($meta_type)!=$table) {
            $addtable .= "_".$meta_type;
         }
      }

      // Plugin can override core definition for its type
      if ($plug = isPluginItemType($itemtype)) {
         $function = 'plugin_'.$plug['plugin'].'_addSelect';
         if (function_exists($function)) {
            $out = $function($itemtype,$ID,$num);
            if (!empty($out)) {
               return $out;
            }
         }
      }

      $ADDITONALFIELDS = '';
      if (isset($searchopt[$ID]["additionalfields"])
          && count($searchopt[$ID]["additionalfields"])) {
         foreach ($searchopt[$ID]["additionalfields"] as $key) {
            $ADDITONALFIELDS .= "`$table$addtable`.`$key` AS ".$NAME."_".$num."_$key, ";
         }
      }

      switch ($table.".".$field) {
         case "glpi_tickets.due_date" :
            return " `$table$addtable`.`$field` AS ".$NAME."_$num,
                     `$table$addtable`.`status` AS ".$NAME."_".$num."_2,
                      $ADDITONALFIELDS";

         case "glpi_tickets.is_late" :
            return " IF(`$table$addtable`.`due_date` IS NOT NULL
                        AND (`$table$addtable`.`solvedate` > `$table$addtable`.`due_date`
                             OR (`$table$addtable`.`solvedate` IS NULL
                                 AND `$table$addtable`.`due_date` < NOW())),
                        1, 0) AS ".$NAME."_$num,
                     $ADDITONALFIELDS";

         case "glpi_contacts.completename" :
            // Contact for display in the enterprise item
            if ($_SESSION["glpinames_format"] == User::FIRSTNAME_BEFORE) {
               $name1 = 'firstname';
               $name2 = 'name';
            } else {
               $name1 = 'name';
               $name2 = 'firstname';
            }
            return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`$name1`, ' ',
                                                  `$table$addtable`.`$name2`, '$$',
                                                  `$table$addtable`.`id`)
                                  SEPARATOR '$$$$') AS ".$NAME."_$num,
                     $ADDITONALFIELDS";

         case "glpi_users.name" :
            if ($itemtype != 'User') {
               if ((isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])) {
                  $addaltemail = "";
                  if ((($itemtype == 'Ticket') || ($itemtype == 'Problem'))
                      && isset($searchopt[$ID]['joinparams']['beforejoin']['table'])
                      && (($searchopt[$ID]['joinparams']['beforejoin']['table'] == 'glpi_tickets_users')
                          || ($searchopt[$ID]['joinparams']['beforejoin']['table']
                                == 'glpi_problems_users'))) { // For tickets_users

                     $ticket_user_table
                        = $searchopt[$ID]['joinparams']['beforejoin']['table'].
                          "_".self::computeComplexJoinID($searchopt[$ID]['joinparams']['beforejoin']['joinparams']);
                     $addaltemail
                        = "GROUP_CONCAT(DISTINCT CONCAT(`$ticket_user_table`.`users_id`, ' ',
                                                        `$ticket_user_table`.`alternative_email`)
                                                        SEPARATOR '$$$$') AS ".$NAME."_".$num."_2, ";
                  }
                  return " GROUP_CONCAT(DISTINCT `$table$addtable`.`id` SEPARATOR '$$$$')
                                       AS ".$NAME."_".$num.",
                           $addaltemail
                           $ADDITONALFIELDS";

               }
               return " `$table$addtable`.`$field` AS ".$NAME."_$num,
                        `$table$addtable`.`realname` AS ".$NAME."_".$num."_2,
                        `$table$addtable`.`id`  AS ".$NAME."_".$num."_3,
                        `$table$addtable`.`firstname` AS ".$NAME."_".$num."_4,
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_groups.name" :
            if (($itemtype != 'Group') && ($itemtype != 'User')) {
               if ($meta
                   || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])) {
                  return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`$field`,'$$',
                                                        `$table$addtable`.`id`) SEPARATOR '$$$$')
                                       AS ".$NAME."_$num, ";
               }
               return " `$table$addtable`.`$field` AS ".$NAME."_$num,
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_softwarelicenses.number" :
            return " FLOOR(SUM(`$table$addtable`.`$field`)
                           * COUNT(DISTINCT `$table$addtable`.`id`)
                           / COUNT(`$table$addtable`.`id`)) AS ".$NAME."_".$num.",
                     MIN(`$table$addtable`.`$field`) AS ".$NAME."_".$num."_2,
                      $ADDITONALFIELDS";

         case "glpi_documents_items.count" :
            return " COUNT(DISTINCT `glpi_documents_items`.`id`) AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_contracts_items.count" :
            return " COUNT(DISTINCT `glpi_contracts_items`.`id`) AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_contractcosts.totalcost" :
            return " SUM(`glpi_contractcosts$addtable`.`cost`)
                     / COUNT(`glpi_contractcosts$addtable`.`id`)
                     * COUNT(DISTINCT `glpi_contractcosts$addtable`.`id`)
                     AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_computers_softwareversions.count" :
            return " COUNT(DISTINCT `glpi_computers_softwareversions$addtable`.`id`)
                          AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_items_deviceharddrives.capacity" :
            if ($itemtype != 'DeviceHardDrive') {
               return " SUM(`glpi_items_deviceharddrives`.`capacity`)
                        / COUNT(`glpi_items_deviceharddrives`.`id`)
                        * COUNT(DISTINCT `glpi_items_deviceharddrives`.`id`) AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_items_devicememories.size" :
            if ($itemtype != 'DeviceMemory') {
               return " SUM(`glpi_items_devicememories`.`size`)
                        / COUNT(`glpi_items_devicememories`.`id`)
                        * COUNT(DISTINCT `glpi_items_devicememories`.`id`) AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_items_deviceprocessors.frequency" :
            if ($itemtype != 'DeviceProcessor') {
               return " SUM(`glpi_items_deviceprocessors`.`frequency`)
                        / COUNT(`glpi_items_deviceprocessors`.`id`) AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_problems.count" :
         case "glpi_problemtasks.count" :
         case "glpi_tickets.count" :
         case "glpi_ticketfollowups.count" :
         case "glpi_tickettasks.count" :
         case "glpi_tickets_tickets.count" :
         case "glpi_items_problems.count" :
         case "glpi_problems_tickets.count" :
            return " COUNT(DISTINCT `$table$addtable`.`id`) AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_ticketcosts.cost_time" :
         case "glpi_ticketcosts.cost_fixed" :
         case "glpi_ticketcosts.cost_material" :
            return " SUM(`glpi_ticketcosts$addtable`.`$field`)
                     / COUNT(`glpi_ticketcosts$addtable`.`id`)
                     * COUNT(DISTINCT `glpi_ticketcosts$addtable`.`id`)
                     AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_ticketcosts.totalcost" :
            return " SUM(`glpi_ticketcosts$addtable`.`actiontime`
                         * `glpi_ticketcosts$addtable`.`cost_time`/".HOUR_TIMESTAMP."
                         + `glpi_ticketcosts$addtable`.`cost_fixed`
                         + `glpi_ticketcosts$addtable`.`cost_material`)
                     / COUNT(`glpi_ticketcosts$addtable`.`id`)
                     * COUNT(DISTINCT `glpi_ticketcosts$addtable`.`id`)
                     AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_tickets_tickets.tickets_id_1" :
            return " GROUP_CONCAT(`$table$addtable`.`tickets_id_1` SEPARATOR '$$$$')
                                 AS ".$NAME."_$num,
                     GROUP_CONCAT(`$table$addtable`.`tickets_id_2` SEPARATOR '$$$$')
                                 AS ".$NAME."_".$num."_2,
                     $ADDITONALFIELDS";

         case "glpi_networkports.mac" :
            $port = " GROUP_CONCAT(`$table$addtable`.`$field` SEPARATOR '$$$$')
                                  AS ".$NAME."_$num, ";
            if ($itemtype == 'Computer') {
               $port .= " GROUP_CONCAT(`glpi_items_devicenetworkcards`.`mac` SEPARATOR '$$$$')
                                      AS ".$NAME."_".$num."_2, ";
            }
            return $port.$ADDITONALFIELDS;

         case "glpi_profiles.name" :
            if (($itemtype == 'User')
                && ($ID == 20)) {
               return " GROUP_CONCAT(`$table$addtable`.`$field` SEPARATOR '$$$$') AS ".$NAME."_$num,
                        GROUP_CONCAT(`glpi_profiles_users`.`entities_id` SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num."_2,
                        GROUP_CONCAT(`glpi_profiles_users`.`is_recursive` SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num."_3,
                        GROUP_CONCAT(`glpi_profiles_users`.`is_dynamic` SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num."_4,
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_entities.completename" :
            if (($itemtype == 'User')
                && ($ID == 80)) {
               return " GROUP_CONCAT(`$table$addtable`.`completename` SEPARATOR '$$$$')
                                    AS ".$NAME."_$num,
                        GROUP_CONCAT(`glpi_profiles_users`.`profiles_id` SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num."_2,
                        GROUP_CONCAT(`glpi_profiles_users`.`is_recursive` SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num."_3,
                        GROUP_CONCAT(`glpi_profiles_users`.`is_dynamic` SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num."_4,
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_auth_tables.name":
            $user_searchopt = self::getOptions('User');
            return " `glpi_users`.`authtype` AS ".$NAME."_".$num.",
                     `glpi_users`.`auths_id` AS ".$NAME."_".$num."_2,
                     `glpi_authldaps".$addtable."_".
                           self::computeComplexJoinID($user_searchopt[30]['joinparams'])."`.`$field`
                              AS ".$NAME."_".$num."_3,
                     `glpi_authmails".$addtable."_".
                           self::computeComplexJoinID($user_searchopt[31]['joinparams'])."`.`$field`
                              AS ".$NAME."_".$num."_4,
                     $ADDITONALFIELDS";

         case "glpi_softwarelicenses.name" :
         case "glpi_softwareversions.name" :
            if ($meta) {
               return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                     `$table$addtable`.`$field`, '$$',
                                                     `$table$addtable`.`id`) SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            }
            break;

         case "glpi_softwarelicenses.serial" :
         case "glpi_softwarelicenses.otherserial" :
         case "glpi_softwarelicenses.comment" :
         case "glpi_softwareversions.comment" :
            if ($meta) {
               return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                     `$table$addtable`.`$field`,'$$',
                                                     `$table$addtable`.`id`) SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            }
            return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`name`, ' - ',
                                                  `$table$addtable`.`$field`, '$$',
                                                  `$table$addtable`.`id`) SEPARATOR '$$$$')
                                 AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case "glpi_states.name" :
            if ($meta && ($meta_type == 'Software')) {
               return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                     `glpi_softwareversions$addtable`.`name`, ' - ',
                                                     `$table$addtable`.`$field`, '$$',
                                                     `$table$addtable`.`id`) SEPARATOR '$$$$')
                                     AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            } else if ($itemtype == 'Software') {
               return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwareversions`.`name`, ' - ',
                                                     `$table$addtable`.`$field`,'$$',
                                                     `$table$addtable`.`id`) SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            }
            break;

         case 'glpi_crontasks.description' :
            return " `glpi_crontasks`.`name` AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case 'glpi_notifications.event' :
            return " `glpi_notifications`.`itemtype` AS `itemtype`,
                     `glpi_notifications`.`event` AS ".$NAME."_".$num.",
                     $ADDITONALFIELDS";

         case 'glpi_tickets.name' :
            if (isset($searchopt[$ID]['forcegroupby']) && $searchopt[$ID]['forcegroupby']) {
               return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`$field`,'$$',
                                                     `$table$addtable`.`id`) SEPARATOR '$$$$')
                                    AS ".$NAME."_".$num.",
                        $ADDITONALFIELDS";
            }
            return " `$table$addtable`.`$field` AS ".$NAME."_$num,
                     `$table$addtable`.`id` AS ".$NAME."_".$num."_2,
                     `$table$addtable`.`content` AS ".$NAME."_".$num."_3,
                     `$table$addtable`.`status` AS ".$NAME."_".$num."_4,
                     $ADDITONALFIELDS";

         case 'glpi_tickets.items_id':
            return " `$table$addtable`.`$field` AS ".$NAME."_$num,
                     `$table$addtable`.`itemtype` AS ".$NAME."_".$num."_2,
                     $ADDITONALFIELDS";
      }

      //// Default cases
      // Link with plugin tables
      if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
         if (count($matches) == 2) {
            $plug     = $matches[1];
            $function = 'plugin_'.$plug.'_addSelect';
            if (function_exists($function)) {
               $out = $function($itemtype, $ID, $num);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }

      $tocompute   = "`$table$addtable`.`$field`";
      $tocomputeid = "`$table$addtable`.`id`";

      if (isset($searchopt[$ID]["computation"])) {
         $tocompute = $searchopt[$ID]["computation"];
         $tocompute = str_replace("TABLE", "`$table$addtable`", $tocompute);
      }

      // Preformat items
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "date_delay" :
               $interval = "MONTH";
               if (isset($searchopt[$ID]['delayunit'])) {
                  $interval = $searchopt[$ID]['delayunit'];
               }

               $add_minus = '';
               if (isset($searchopt[$ID]["datafields"][3])) {
                  $add_minus = "-`$table$addtable`.`".$searchopt[$ID]["datafields"][3]."`";
               }
               if ($meta
                   || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])) {
                  return " GROUP_CONCAT(DISTINCT ADDDATE(`$table$addtable`.`".
                                                            $searchopt[$ID]["datafields"][1]."`,
                                                         INTERVAL (`$table$addtable`.`".
                                                                     $searchopt[$ID]["datafields"][2].
                                                                     "` $add_minus) $interval)
                                         SEPARATOR '$$$$') AS ".$NAME."_$num,
                           $ADDITONALFIELDS";
               }
               return "ADDDATE(`$table$addtable`.`".$searchopt[$ID]["datafields"][1]."`,
                               INTERVAL (`$table$addtable`.`".$searchopt[$ID]["datafields"][2].
                                          "` $add_minus) $interval) AS ".$NAME."_$num,
                       $ADDITONALFIELDS";

            case "itemlink" :
               if ($meta
                  || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])) {
                  return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`$field`, '$$' ,
                                                        `$table$addtable`.`id`) SEPARATOR '$$$$')
                                       AS ".$NAME."_$num,
                           $ADDITONALFIELDS";
               }
               return " $tocompute AS ".$NAME."_$num,
                        `$table$addtable`.`id` AS ".$NAME."_".$num."_2,
                        $ADDITONALFIELDS";
         }
      }
      // Default case
      if ($meta
         || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])) {
         return " GROUP_CONCAT(DISTINCT CONCAT($tocompute,'$$',$tocomputeid) SEPARATOR '$$$$')
                              AS ".$NAME."_$num,
                  $ADDITONALFIELDS";
      }
      return "$tocompute AS ".$NAME."_$num,
              $ADDITONALFIELDS";
   }


   /**
    * Generic Function to add default where to a request
    *
    * @param $itemtype device type
    *
    * @return select string
   **/
   static function addDefaultWhere($itemtype) {
      global $CFG_GLPI;

      switch ($itemtype) {
         case 'Reminder' :
            return Reminder::addVisibilityRestrict();

         case 'RSSFeed' :
            return RSSFeed::addVisibilityRestrict();

         case 'Notification' :
            if (!Session::haveRight('config','w')) {
               return " `glpi_notifications`.`itemtype` NOT IN ('Crontask', 'DBConnection') ";
            }
            break;

         // No link
         case 'User' :
            // View all entities
            if (Session::isViewAllEntities()) {
               return "";
            }
            return getEntitiesRestrictRequest("","glpi_profiles_users");

         case 'Ticket' :
            // Same structure in addDefaultJoin
            $condition = '';
            if (!Session::haveRight("show_all_ticket","1")) {

               $searchopt
                  = &self::getOptions($itemtype);
               $requester_table
                  = '`glpi_tickets_users_'.
                     self::computeComplexJoinID($searchopt[4]['joinparams']['beforejoin']['joinparams']).'`';
               $requestergroup_table
                  = '`glpi_groups_tickets_'.
                     self::computeComplexJoinID($searchopt[71]['joinparams']['beforejoin']['joinparams']).'`';

               $assign_table
                  = '`glpi_tickets_users_'.
                     self::computeComplexJoinID($searchopt[5]['joinparams']['beforejoin']['joinparams']).'`';
               $assigngroup_table
                  = '`glpi_groups_tickets_'.
                     self::computeComplexJoinID($searchopt[8]['joinparams']['beforejoin']['joinparams']).'`';

               $observer_table
                  = '`glpi_tickets_users_'.
                     self::computeComplexJoinID($searchopt[66]['joinparams']['beforejoin']['joinparams']).'`';
               $observergroup_table
                  = '`glpi_groups_tickets_'.
                     self::computeComplexJoinID($searchopt[65]['joinparams']['beforejoin']['joinparams']).'`';

               $condition = "(";

               $condition .= " $requester_table.users_id = '".Session::getLoginUserID()."'
                              OR $observer_table.users_id = '".Session::getLoginUserID()."'
                              OR `glpi_tickets`.`users_id_recipient` = '".Session::getLoginUserID()."' ";


               if (Session::haveRight("show_group_ticket",1)) {
                  if (count($_SESSION['glpigroups'])) {
                     $condition .= " OR $requestergroup_table.`groups_id`
                                             IN ('".implode("','",$_SESSION['glpigroups'])."')";
                     $condition .= " OR $observergroup_table.`groups_id`
                                             IN ('".implode("','",$_SESSION['glpigroups'])."')";
                  }
               }

               if (Session::haveRight("own_ticket","1")) {// Can own ticket : show assign to me
                  $condition .= " OR $assign_table.users_id = '".Session::getLoginUserID()."' ";
               }

               if (Session::haveRight("show_assign_ticket","1")) { // show mine + assign to me

                  $condition .=" OR $assign_table.`users_id` = '".Session::getLoginUserID()."'";
                  if (count($_SESSION['glpigroups'])) {
                     $condition .= " OR $assigngroup_table.`groups_id`
                                             IN ('".implode("','",$_SESSION['glpigroups'])."')";
                  }
                  if (Session::haveRight('assign_ticket',1)) {
                     $condition .= " OR `glpi_tickets`.`status`='".CommonITILObject::INCOMING."'";
                  }
               }

               if (Session::haveRight('validate_incident', 1)
                   || Session::haveRight('validate_request', 1)) {
                  $condition .= " OR `glpi_ticketvalidations`.`users_id_validate`
                                          = '".Session::getLoginUserID()."'";
               }
               $condition .= ") ";
            }
            return $condition;

         default :
            // Plugin can override core definition for its type
            if ($plug = isPluginItemType($itemtype)) {
               $function = 'plugin_'.$plug['plugin'].'_addDefaultWhere';
               if (function_exists($function)) {
                  $out = $function($itemtype);
                  if (!empty($out)) {
                     return $out;
                  }
               }
            }
            return "";
      }
   }


   /**
    * Generic Function to add where to a request
    *
    * @param $link         link string
    * @param $nott         is it a negative search ?
    * @param $itemtype     item type
    * @param $ID           ID of the item to search
    * @param $searchtype   searchtype used (equals or contains)
    * @param $val          item num in the request
    * @param $meta         is a meta search (meta=2 in search.class.php) (default 0)
    *
    * @return select string
   **/
   static function addWhere($link, $nott, $itemtype, $ID, $searchtype, $val, $meta=0) {

      $searchopt = &self::getOptions($itemtype);
      $table     = $searchopt[$ID]["table"];
      $field     = $searchopt[$ID]["field"];

      $inittable = $table;
      $addtable  = '';

      if ($table != 'asset_types'
          && ($table != getTableForItemType($itemtype))
          && ($searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table))) {
         $addtable = "_".$searchopt[$ID]["linkfield"];
         $table   .= $addtable;
      }

      if (isset($searchopt[$ID]['joinparams'])) {
         $complexjoin = self::computeComplexJoinID($searchopt[$ID]['joinparams']);

         if (!empty($complexjoin)) {
            $table .= "_".$complexjoin;
         }
      }

      if ($meta
          && (getTableForItemType($itemtype) != $table)) {
         $table .= "_".$itemtype;
      }

      // Hack to allow search by ID on every sub-table
      if (preg_match('/^\$\$\$\$([0-9]+)$/',$val,$regs)) {
         return $link." (`$table`.`id` ".($nott?"<>":"=").$regs[1]." ".
                         (($regs[1] == 0)?" OR `$table`.`id` IS NULL":'').") ";
      }

      // Preparse value
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "datetime" :
            case "date" :
            case "date_delay" :
               $force_day = true;
               if ($searchopt[$ID]["datatype"] == 'datetime') {
                  $force_day = false;
               }
               if (strstr($val,'BEGIN') || strstr($val,'LAST')) {
                  $force_day = true;
               }

               $val = Html::computeGenericDateTimeSearch($val, $force_day);

               break;
         }
      }
      switch ($searchtype) {
         case "contains" :
            $SEARCH = self::makeTextSearch($val, $nott);
            break;

         case "equals" :
            if ($nott) {
               $SEARCH = " <> '$val'";
            } else {
               $SEARCH = " = '$val'";
            }
            break;

         case "notequals" :
            if ($nott) {
               $SEARCH = " = '$val'";
            } else {
               $SEARCH = " <> '$val'";
            }
            break;

         case "under" :
            if ($nott) {
               $SEARCH = " NOT IN ('".implode("','",getSonsOf($inittable, $val))."')";
            } else {
               $SEARCH = " IN ('".implode("','",getSonsOf($inittable, $val))."')";
            }
            break;

         case "notunder" :
            if ($nott) {
               $SEARCH = " IN ('".implode("','",getSonsOf($inittable, $val))."')";
            } else {
               $SEARCH = " NOT IN ('".implode("','",getSonsOf($inittable, $val))."')";
            }
            break;

      }

      // Plugin can override core definition for its type
      if ($plug = isPluginItemType($itemtype)) {
         $function = 'plugin_'.$plug['plugin'].'_addWhere';
         if (function_exists($function)) {
            $out = $function($link,$nott,$itemtype,$ID,$val);
            if (!empty($out)) {
               return $out;
            }
         }
         $function = 'plugin_'.$plug['plugin'].'_addSpecificWhere';
         if (function_exists($function)) {
            $out = $function($link,$nott,$itemtype,$ID,$val,$searchtype);
            if (!empty($out)) {
               return $out;
            }
         }
      }

      switch ($inittable.".".$field) {
//          case "glpi_users_validation.name" :

         case "glpi_users.name" :
            if ($itemtype == 'User') { // glpi_users case / not link table
               if (in_array($searchtype, array('equals', 'notequals'))) {
                  return " $link `$table`.`id`".$SEARCH;
               }
               return self::makeTextCriteria("`$table`.`$field`", $val, $nott, $link);
            }
            if ($_SESSION["glpinames_format"] == User::FIRSTNAME_BEFORE) {
               $name1 = 'firstname';
               $name2 = 'realname';
            } else {
               $name1 = 'realname';
               $name2 = 'firstname';
            }

            if (in_array($searchtype, array('equals', 'notequals'))) {
               return " $link (`$table`.`id`".$SEARCH.
                               (($val == 0)?" OR `$table`.`id` IS NULL":'').') ';
            }
            $toadd   = '';

            $tmplink = 'OR';
            if ($nott) {
               $tmplink = 'AND';
            }

            if (($itemtype == 'Ticket') || ($itemtype == 'Problem')) {
               if (isset($searchopt[$ID]["joinparams"]["beforejoin"]["table"])
                   && isset($searchopt[$ID]["joinparams"]["beforejoin"]["joinparams"])
                   && (($searchopt[$ID]["joinparams"]["beforejoin"]["table"] == 'glpi_tickets_users')
                       || ($searchopt[$ID]["joinparams"]["beforejoin"]["table"] == 'glpi_problems_users'))) {

                  $bj        = $searchopt[$ID]["joinparams"]["beforejoin"];
                  $linktable = $bj['table'].'_'.self::computeComplexJoinID($bj['joinparams']);
                  //$toadd     = "`$linktable`.`alternative_email` $SEARCH $tmplink ";
                  $toadd     = self::makeTextCriteria("`$linktable`.`alternative_email`", $val,
                                                      $nott, $tmplink);
               }
            }
            $toadd2 = '';
            if ($nott
                && ($val != 'NULL') && ($val != 'null')) {
               $toadd2 = " OR `$table`.`$field` IS NULL";
            }
            return $link." (((`$table`.`$name1` $SEARCH
                            $tmplink `$table`.`$name2` $SEARCH
                            $tmplink `$table`.`$field` $SEARCH
                            $tmplink CONCAT(`$table`.`$name1`, ' ', `$table`.`$name2`) $SEARCH )
                            $toadd2) $toadd)";


         case "glpi_groups.completename" :
            if ($val == 'mygroups') {
               switch ($searchtype) {
                  case 'equals' :
                     return " $link (`$table`.`id` IN ('".implode("','",$_SESSION['glpigroups'])."')) ";
                     break;

                  case 'notequals' :
                     return " $link (`$table`.`id` NOT IN ('".implode("','",$_SESSION['glpigroups'])."')) ";
                     break;

                  case 'under' :
                     $groups = $_SESSION['glpigroups'];
                     foreach ($_SESSION['glpigroups'] as $g) {
                        $groups += getSonsOf($inittable, $g);
                     }
                     $groups = array_unique($groups);
                     return " $link (`$table`.`id` IN ('".implode("','", $groups)."')) ";
                     break;

                  case 'notunder' :
                     $groups = $_SESSION['glpigroups'];
                     foreach ($_SESSION['glpigroups'] as $g) {
                        $groups += getSonsOf($inittable, $g);
                     }
                     $groups = array_unique($groups);
                     return " $link (`$table`.`id` NOT IN ('".implode("','", $groups)."')) ";
                     break;
               }
            }
            break;

         case "glpi_networkports.mac" :
            if ($itemtype == 'Computer') {
               return "$link (".self::makeTextCriteria("`glpi_items_devicenetworkcards`.`mac`",
                                                       $val, $nott,'').
                              self::makeTextCriteria("`$table`.`$field`", $val ,$nott, 'OR').")";
            }
            return self::makeTextCriteria("`$table`.`$field`", $val, $nott, $link);

         case "glpi_infocoms.sink_type" :
            $ADD = "";
            if ($nott
                && ($val != 'NULL') && ($val != 'null')) {
               $ADD = " OR `$table`.`$field` IS NULL";
            }

            if (stristr($val,Infocom::getAmortTypeName(1))) {
               $val = 1;
            } else if (stristr($val,Infocom::getAmortTypeName(2))) {
               $val = 2;
            }

            if (is_int($val) && ($val > 0)) {
               if ($nott) {
                  return $link." (`$table`.`$field` <> '$val' ".
                                  $ADD." ) ";
               }
               return $link." (`$table`.`$field` = '$val' ".
                               $ADD." ) ";
            }
            break;

         case "glpi_contacts.completename" :
            if (in_array($searchtype, array('equals', 'notequals'))) {
               return " $link `$table`.`id`".$SEARCH;
            }
            if ($_SESSION["glpinames_format"] == User::FIRSTNAME_BEFORE) {
               $name1 = 'firstname';
               $name2 = 'name';
            } else {
               $name1 = 'name';
               $name2 = 'firstname';
            }

            $tmplink = 'OR';
            if ($nott) {
               $tmplink = 'AND';
            }
            return $link." (`$table`.`$name1` $SEARCH
                            $tmplink `$table`.`$name2` $SEARCH
                            $tmplink CONCAT(`$table`.`$name1`,' ',`$table`.`$name2`) $SEARCH) ";

         case "glpi_auth_tables.name" :
            $user_searchopt = self::getOptions('User');
            $tmplink        = 'OR';
            if ($nott) {
               $tmplink = 'AND';
            }
            return $link." (`glpi_authmails".$addtable."_".
                              self::computeComplexJoinID($user_searchopt[31]['joinparams'])."`.`name`
                           $SEARCH
                           $tmplink `glpi_authldaps".$addtable."_".
                              self::computeComplexJoinID($user_searchopt[30]['joinparams'])."`.`name`
                           $SEARCH ) ";

         case "glpi_contracts.renewal" :
            $valid = Contract::getContractRenewalIDByName($val);
            if ($valid > 0) {
               return $link." `$table`.`$field`"."="."'$valid'";
            }
            return "";

         case "glpi_profiles.interface" :
            if (stristr(Profile::getInterfaceName('central'),$val)) {
               return $link." `$table`.`$field`='central'";
            }
            if (stristr(Profile::getInterfaceName('helpdesk'),$val)) {
               return $link." `$table`.`$field`='helpdesk'";
            }
            return "";

         case "glpi_ipaddresses.name" :
            $search  = array("/\&lt;/","/\&gt;/");
            $replace = array("<",">");
            $val     = preg_replace($search, $replace, $val);
            if (preg_match("/^\s*([<>])([=]*)[[:space:]]*([0-9\.]+)/",$val,$regs)) {
               if ($nott) {
                  if ($regs[1] == '<') {
                     $regs[1] = '>';
                  } else {
                     $regs[1] = '<';
                  }
               }
               $regs[1] .= $regs[2];
               return $link." (INET_ATON(`$table`.`$field`) ".$regs[1]." INET_ATON('".$regs[3]."')) ";
            }
//             return self::makeTextCriteria("`$table`.`$field`", $val, $nott, $link);
            break;

         case "glpi_tickets.status" :
         case "glpi_problems.status" :
         case "glpi_changes.status" :
            if ($val == 'all') {
               return "";
            }
            $tocheck = array();
            if ($item = getItemForItemtype($itemtype)) {
               switch ($val) {
                  case 'process' :
                     $tocheck = $item->getProcessStatusArray();
                     break;

                  case 'notclosed' :
                     $tocheck = $item->getAllStatusArray();
                     foreach ($item->getClosedStatusArray() as $status) {
                        if (isset($tocheck[$status])) {
                           unset($tocheck[$status]);
                        }
                     }
                     $tocheck = array_keys($tocheck);
                     break;

                  case 'old' :
                     $tocheck = array_merge($item->getSolvedStatusArray(),
                                            $item->getClosedStatusArray());
                     break;

                  case 'notold' :
                     $tocheck = $item->getAllStatusArray();
                     foreach ($item->getSolvedStatusArray() as $status) {
                        if (isset($tocheck[$status])) {
                           unset($tocheck[$status]);
                        }
                     }
                     foreach ($item->getClosedStatusArray() as $status) {
                        if (isset($tocheck[$status])) {
                           unset($tocheck[$status]);
                        }
                     }
                     $tocheck = array_keys($tocheck);
                     break;
               }
            }
            if (count($tocheck) == 0) {
               $statuses = $item->getAllStatusArray();
               if (isset($statuses[$val])) {
                  $tocheck = array($val);
               }
            }

            if (count($tocheck)) {
               if ($nott) {
                  return $link." `$table`.`$field` NOT IN ('".implode("','",$tocheck)."')";
               }
               return $link." `$table`.`$field` IN ('".implode("','",$tocheck)."')";
            }
            break;

         case "glpi_tickets_tickets.tickets_id_1" :
            $tmplink = 'OR';
            $compare = '=';
            if ($nott) {
               $tmplink = 'AND';
               $compare = '<>';
            }
            $toadd2 = '';
            if ($nott
                && ($val != 'NULL') && ($val != 'null')) {
               $toadd2 = " OR `$table`.`$field` IS NULL";
            }

            return $link." (((`$table`.`tickets_id_1` $compare '$val'
                              $tmplink `$table`.`tickets_id_2` $compare '$val')
                             AND `glpi_tickets`.`id` <> '$val')
                            $toadd2)";

         case "glpi_tickets.priority" :
         case "glpi_tickets.impact" :
         case "glpi_tickets.urgency" :
         case "glpi_projects.priority" :
         case "glpi_projects.impact" :
         case "glpi_projects.urgency" :
            if (is_numeric($val)) {
               if ($val > 0) {
                  return $link." `$table`.`$field` = '$val'";
               }
               if ($val < 0) {
                  return $link." `$table`.`$field` >= '".abs($val)."'";
               }
               // Show all
               return $link." `$table`.`$field` >= '0' ";
            }
            return "";

         case "glpi_tickets.global_validation" :
         case "glpi_ticketvalidations.status" :
            $tocheck = array('none'     => array('none'),
                             'waiting'  => array('waiting'),
                             'rejected' => array('rejected'),
                             'accepted' => array('accepted'),
                             'can'      => array('none', 'accepted'),
                             'all'      => array('none', 'waiting', 'rejected', 'accepted'));
            if (isset($tocheck[$val])) {
               foreach ($tocheck[$val] as $key => $nval) {
                  $tocheck[$val][$key] = " `$table`.`$field` = '$nval' ";
               }
               return $link.'('.implode(' OR ', $tocheck[$val]).')';
            }
            if ($val == 'all') {
               return "";
            }
            break;

         case "glpi_ticketsatisfactions.type" :
            return $link." `$table`.`$field` = '$val' ";

         case "glpi_tickets.is_late" :
            return " $link IF(`$table$addtable`.`due_date` IS NOT NULL
                              AND (`$table$addtable`.`solvedate` > `$table$addtable`.`due_date`
                                   OR (`$table$addtable`.`solvedate` IS NULL
                                       AND `$table$addtable`.`due_date` < NOW())),
                              1, 0)
                     $SEARCH ";

      }

      //// Default cases

      // Link with plugin tables
      if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $inittable, $matches)) {
         if (count($matches) == 2) {
            $plug     = $matches[1];
            $function = 'plugin_'.$plug.'_addWhere';
            if (function_exists($function)) {
               $out = $function($link, $nott, $itemtype, $ID, $val);
               if (!empty($out)) {
                  return $out;
               }
            }
            $function = 'plugin_'.$plug.'_addSpecificWhere';
            if (function_exists($function)) {
               $out = $function($link, $nott, $itemtype, $ID, $val, $searchtype);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }

      $tocompute = "`$table`.`$field`";
      if (isset($searchopt[$ID]["computation"])) {
         $tocompute = $searchopt[$ID]["computation"];
         $tocompute = str_replace("TABLE", "`$table`", $tocompute);
      }

      // Preformat items
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "itemtypename" :
               if (in_array($searchtype, array('equals', 'notequals'))) {
                  return " $link (`$table`.`$field`".$SEARCH.') ';
               }
               break;

            case "itemlink" :
               if (in_array($searchtype, array('equals', 'notequals'))) {
                  return " $link (`$table`.`id`".$SEARCH.') ';
               }
               break;

            case "datetime" :
            case "date" :
            case "date_delay" :
               if ($searchopt[$ID]["datatype"] == 'datetime') {
                  // Specific search for datetime
                  if (in_array($searchtype, array('equals', 'notequals'))) {
                     $val = preg_replace("/:00$/",'',$val);
                     $val = '^'.$val;
                     if ($searchtype == 'notequals') {
                        $nott = !$nott;
                     }
                     return self::makeTextCriteria("`$table`.`$field`", $val, $nott, $link);
                  }
               }
               if ($searchtype == 'lessthan') {
                 $val = '<'.$val;
               }
               if ($searchtype == 'morethan') {
                 $val = '>'.$val;
               }
               if ($searchtype) {
                  $date_computation = $tocompute;
               }
               $search_unit = ' MONTH ';
               if (isset($searchopt[$ID]['searchunit'])) {
                  $search_unit = $searchopt[$ID]['searchunit'];
               }
               if ($searchopt[$ID]["datatype"]=="date_delay") {
                  $delay_unit = ' MONTH ';
                  if (isset($searchopt[$ID]['delayunit'])) {
                     $delay_unit = $searchopt[$ID]['delayunit'];
                  }
                  $add_minus = '';
                  if (isset($searchopt[$ID]["datafields"][3])) {
                     $add_minus = "-`$table`.`".$searchopt[$ID]["datafields"][3]."`";
                  }
                  $date_computation = "ADDDATE(`$table`.".$searchopt[$ID]["datafields"][1].",
                                               INTERVAL (`$table`.".$searchopt[$ID]["datafields"][2]."
                                                 $add_minus) $delay_unit)";
               }
               if (in_array($searchtype, array('equals', 'notequals'))) {
                  return " $link ($date_computation ".$SEARCH.') ';
               }
               $search  = array("/\&lt;/","/\&gt;/");
               $replace = array("<",">");
               $val     = preg_replace($search,$replace,$val);
               if (preg_match("/^\s*([<>=]+)(.*)/",$val,$regs)) {
                  if (is_numeric($regs[2])) {
                     return $link." $date_computation ".$regs[1]."
                            ADDDATE(NOW(), INTERVAL ".$regs[2]." $search_unit) ";
                  }
                  // ELSE Reformat date if needed
                  $regs[2] = preg_replace('@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@','\5-\3-\1',
                                          $regs[2]);
                  if (preg_match('/[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}/', $regs[2])) {
                     return $link." $date_computation ".$regs[1]." '".$regs[2]."'";
                  }
                  return "";
               }
               // ELSE standard search
               // Date format modification if needed
               $val = preg_replace('@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@','\5-\3-\1', $val);
               return self::makeTextCriteria($date_computation, $val, $nott, $link);

            case "right" :
               if (($val == 'NULL') || ($val == 'null')) {
                  return $link." $tocompute IS ".($nott?'NOT':'')." NULL ";
               }
               return $link." $tocompute = '$val' ";

            case "bool" :
               if (!is_numeric($val)) {
                  if (strcasecmp($val,__('No')) == 0) {
                     $val = 0;
                  } else if (strcasecmp($val,__('Yes')) == 0) {
                     $val = 1;
                  }
               }
               if ($searchtype == 'notequals') {
                  $nott = !$nott;
               }
               // No break here : use number comparaison case

            case "number" :
            case "decimal" :
            case "timestamp" :
               $search  = array("/\&lt;/", "/\&gt;/");
               $replace = array("<", ">");
               $val     = preg_replace($search, $replace, $val);

               if (preg_match("/([<>])([=]*)[[:space:]]*([0-9]+)/", $val, $regs)) {
                  if ($nott) {
                     if ($regs[1] == '<') {
                        $regs[1] = '>';
                     } else {
                        $regs[1] = '<';
                     }
                  }
                  $regs[1] .= $regs[2];
                  return $link." ($tocompute ".$regs[1]." ".$regs[3].") ";
               }
               if (is_numeric($val)) {
                  if (isset($searchopt[$ID]["width"])) {
                     $ADD = "";
                     if ($nott
                         && ($val != 'NULL') && ($val != 'null')) {
                        $ADD = " OR $tocompute IS NULL";
                     }
                     if ($nott) {
                        return $link." ($tocompute < ".(intval($val) - $searchopt[$ID]["width"])."
                                        OR $tocompute > ".(intval($val) + $searchopt[$ID]["width"])."
                                        $ADD) ";
                     }
                     return $link." (($tocompute >= ".(intval($val) - $searchopt[$ID]["width"])."
                                      AND $tocompute <= ".(intval($val) + $searchopt[$ID]["width"]).")
                                     $ADD) ";
                  }
                  if (!$nott) {
                     return " $link ($tocompute = ".(intval($val)).") ";
                  }
                  return " $link ($tocompute <> ".(intval($val)).") ";
               }
               break;
         }
      }

      // Default case
      if (in_array($searchtype, array('equals', 'notequals','under', 'notunder'))) {

         if ((!isset($searchopt[$ID]['searchequalsonfield'])
               || !$searchopt[$ID]['searchequalsonfield'])
            && (($table != getTableForItemType($itemtype))
               || ($itemtype == 'AllAssets'))) {
            $out = " $link (`$table`.`id`".$SEARCH;
         } else {
            $out = " $link (`$table`.`$field`".$SEARCH;
         }
         if ($searchtype == 'notequals') {
            $nott = !$nott;
         }
         // Add NULL if $val = 0 and not negative search
         // Or negative search on real value
         if ((!$nott && ($val == 0))
             || ($nott && ($val != 0))) {
            $out .= " OR `$table`.`id` IS NULL";
         }
         $out .= ')';
         return $out;
      }
      return self::makeTextCriteria($tocompute,$val,$nott,$link);
   }


   /**
    * Generic Function to add Default left join to a request
    *
    * @param $itemtype                    reference ID
    * @param $ref_table                   reference table
    * @param &$already_link_tables  array of tables already joined
    *
    * @return Left join string
   **/
   static function addDefaultJoin($itemtype, $ref_table, array &$already_link_tables) {

      switch ($itemtype) {
         // No link
          case 'User' :
             return self::addLeftJoin($itemtype, $ref_table, $already_link_tables,
                                      "glpi_profiles_users", "profiles_users_id", 0, 0,
                                      array('jointype' => 'child'));

         case 'RSSFeed' :
            return RSSFeed::addVisibilityJoins();

         case 'Reminder' :
            return Reminder::addVisibilityJoins();
            /*
            $out  = self::addLeftJoin($itemtype, $ref_table, $already_link_tables,
                                      "glpi_reminders_users", "reminders_users_id", 0, 0,
                                       array('jointype' => 'child'));
            $out .= self::addLeftJoin($itemtype, $ref_table, $already_link_tables,
                                      "glpi_groups_reminders", "groups_reminders_id", 0, 0,
                                       array('jointype' => 'child'));
            $out .= self::addLeftJoin($itemtype, $ref_table, $already_link_tables,
                                      "glpi_entities_reminders", "entities_reminders_id", 0, 0,
                                       array('jointype' => 'child'));
            $out .= self::addLeftJoin($itemtype, $ref_table, $already_link_tables,
                                      "glpi_profiles_reminders", "profiles_reminders_id", 0, 0,
                                       array('jointype' => 'child'));
            return $out;
            */
         case 'Ticket' :
            // Same structure in addDefaultWhere
            $out = '';
            if (!Session::haveRight("show_all_ticket","1")) {
               $searchopt = &self::getOptions($itemtype);

//                $requester_table      = '`glpi_tickets_users_'.self::computeComplexJoinID($searchopt[4]['joinparams']['beforejoin']['joinparams']).'`';
//                $requestergroup_table = '`glpi_groups_tickets_'.self::computeComplexJoinID($searchopt[71]['joinparams']['beforejoin']['joinparams']).'`';
//                $assign_table      = '`glpi_tickets_users_'.self::computeComplexJoinID($searchopt[5]['joinparams']['beforejoin']['joinparams']).'`';
//                $assigngroup_table = '`glpi_groups_tickets_'.self::computeComplexJoinID($searchopt[8]['joinparams']['beforejoin']['joinparams']).'`';
//               $observer_table      = '`glpi_tickets_users_'.self::computeComplexJoinID($searchopt[66]['joinparams']['beforejoin']['joinparams']).'`';
//               $observergroup_table = '`glpi_groups_tickets_'.self::computeComplexJoinID($searchopt[65]['joinparams']['beforejoin']['joinparams']).'`';

               // show mine : requester
               $out .= self::addLeftJoin($itemtype, $ref_table, $already_link_tables,
                                         "glpi_tickets_users", "tickets_users_id", 0, 0,
                                         $searchopt[4]['joinparams']['beforejoin']['joinparams']);

               if (Session::haveRight("show_group_ticket",1)) {
                  if (count($_SESSION['glpigroups'])) {
                     $out .= self::addLeftJoin($itemtype, $ref_table, $already_link_tables,
                                               "glpi_groups_tickets", "groups_tickets_id", 0, 0,
                                               $searchopt[71]['joinparams']['beforejoin']['joinparams']);
                  }
               }

               // show mine : observer
               $out .= self::addLeftJoin($itemtype, $ref_table, $already_link_tables,
                                         "glpi_tickets_users", "tickets_users_id", 0, 0,
                                         $searchopt[66]['joinparams']['beforejoin']['joinparams']);

               if (count($_SESSION['glpigroups'])) {
                  $out .= self::addLeftJoin($itemtype, $ref_table, $already_link_tables,
                                            "glpi_groups_tickets", "groups_tickets_id", 0, 0,
                                            $searchopt[65]['joinparams']['beforejoin']['joinparams']);
               }

               if (Session::haveRight("own_ticket","1")) { // Can own ticket : show assign to me
                  $out .= self::addLeftJoin($itemtype, $ref_table, $already_link_tables,
                                            "glpi_tickets_users", "tickets_users_id", 0, 0,
                                            $searchopt[5]['joinparams']['beforejoin']['joinparams']);
               }

               if (Session::haveRight("show_assign_ticket","1")) { // show mine + assign to me
                  $out .= self::addLeftJoin($itemtype, $ref_table, $already_link_tables,
                                            "glpi_tickets_users", "tickets_users_id", 0, 0,
                                            $searchopt[5]['joinparams']['beforejoin']['joinparams']);

                  if (count($_SESSION['glpigroups'])) {
                     $out .= self::addLeftJoin($itemtype, $ref_table, $already_link_tables,
                                               "glpi_groups_tickets", "groups_tickets_id", 0, 0,
                                               $searchopt[8]['joinparams']['beforejoin']['joinparams']);
                  }
               }

               if (Session::haveRight('validate_incident', 1)
                   || Session::haveRight('validate_request', 1)) {
                  $out .= self::addLeftJoin($itemtype, $ref_table, $already_link_tables,
                                            "glpi_ticketvalidations", "ticketvalidations_id", 0, 0,
                                            $searchopt[58]['joinparams']['beforejoin']['joinparams']);
               }
            }
            return $out;

         default :
            // Plugin can override core definition for its type
            if ($plug = isPluginItemType($itemtype)) {
               $function = 'plugin_'.$plug['plugin'].'_addDefaultJoin';
               if (function_exists($function)) {
                  $out = $function($itemtype, $ref_table, $already_link_tables);
                  if (!empty($out)) {
                     return $out;
                  }
               }
            }


            return "";
      }
   }


   /**
    * Generic Function to add left join to a request
    *
    * @param $itemtype                    item type
    * @param $ref_table                   reference table
    * @param $already_link_tables   array of tables already joined
    * @param $new_table                   new table to join
    * @param $linkfield                   linkfield for LeftJoin
    * @param $meta                        is it a meta item ? (default 0)
    * @param $meta_type                   meta type table (default 0)
    * @param $joinparams            array join parameters (condition / joinbefore...)
    *
    * @return Left join string
   **/
   static function addLeftJoin($itemtype, $ref_table, array &$already_link_tables, $new_table,
                                $linkfield, $meta=0, $meta_type=0, $joinparams=array()) {
      global $CFG_GLPI;

      // Rename table for meta left join
      $AS = "";
      $nt = $new_table;
      $cleannt    = $nt;

      // Multiple link possibilies case
//       if ($new_table=="glpi_users"
//           || $new_table=="glpi_groups"
//           || $new_table=="glpi_users_validation") {

      if (!empty($linkfield) && ($linkfield != getForeignKeyFieldForTable($new_table))) {
         $nt .= "_".$linkfield;
         $AS  = " AS ".$nt;
      }

      $complexjoin = self::computeComplexJoinID($joinparams);

      if (!empty($complexjoin)) {
         $nt .= "_".$complexjoin;
         $AS  = " AS ".$nt;
      }

//       }

      $addmetanum = "";
      $rt         = $ref_table;
      $cleanrt    = $rt;
      if ($meta) {
         $addmetanum = "_".$meta_type;
         $AS         = " AS $nt$addmetanum";
         $nt         = $nt.$addmetanum;
      }


      // Auto link
      if (($ref_table == $new_table)
          && empty($complexjoin)) {
         return "";
      }

      // Do not take into account standard linkfield
      $tocheck = $nt.".".$linkfield;
      if ($linkfield == getForeignKeyFieldForTable($new_table)) {
         $tocheck = $nt;
      }
//       echo '->'.$tocheck.'<br>';

      if (in_array($tocheck,$already_link_tables)) {
         return "";
      }
      array_push($already_link_tables, $tocheck);

//        echo "DONE<br>";
      $specific_leftjoin = '';

      // Plugin can override core definition for its type
      if ($plug = isPluginItemType($itemtype)) {
         $function = 'plugin_'.$plug['plugin'].'_addLeftJoin';
         if (function_exists($function)) {
            $specific_leftjoin = $function($itemtype, $ref_table, $new_table, $linkfield,
                                           $already_link_tables);
         }
      }

      // Link with plugin tables : need to know left join structure
      if (empty($specific_leftjoin)
          && preg_match("/^glpi_plugin_([a-z0-9]+)/", $new_table, $matches)) {
         if (count($matches) == 2) {
            $function = 'plugin_'.$matches[1].'_addLeftJoin';
            if (function_exists($function)) {
               $specific_leftjoin = $function($itemtype, $ref_table, $new_table, $linkfield,
                                              $already_link_tables);
            }
         }
      }
      if (!empty($linkfield)) {
         $before = '';
//          Html::printCleanArray($joinparams);
         if (isset($joinparams['beforejoin']) && is_array($joinparams['beforejoin']) ) {

            if (isset($joinparams['beforejoin']['table'])) {
               $joinparams['beforejoin'] = array($joinparams['beforejoin']);
            }

            foreach ($joinparams['beforejoin'] as $tab) {
               if (isset($tab['table'])) {
                  $intertable = $tab['table'];
                  if (isset($tab['linkfield'])) {
                     $interlinkfield = $tab['linkfield'];
                  } else {
                     $interlinkfield = getForeignKeyFieldForTable($intertable);
                  }

                  $interjoinparams = array();
                  if (isset($tab['joinparams'])) {
                     $interjoinparams = $tab['joinparams'];
                  }
//                   echo "BEFORE ";
                  $before .= self::addLeftJoin($itemtype, $rt, $already_link_tables, $intertable,
                                               $interlinkfield, $meta, $meta_type, $interjoinparams);
//                   echo "END BEFORE ".'<br>';
               }

               // No direct link with the previous joins
               if (!isset($tab['joinparams']['nolink']) || !$tab['joinparams']['nolink']) {
                  $cleanrt     = $intertable;
                  $complexjoin = self::computeComplexJoinID($interjoinparams);
                  if (!empty($complexjoin)) {
                     $intertable .= "_".$complexjoin;
                  }
                  $rt = $intertable.$addmetanum;
               }
            }
         }

         $addcondition = '';
         if (isset($joinparams['condition'])) {
            $from         = array("`REFTABLE`", "REFTABLE", "`NEWTABLE`", "NEWTABLE");
            $to           = array("`$rt`", "`$rt`", "`$nt`", "`$nt`");
            $addcondition = str_replace($from, $to, $joinparams['condition']);
            $addcondition = $addcondition." ";
         }

         if (!isset($joinparams['jointype'])) {
            $joinparams['jointype'] = 'standard';
         }

         if (empty($specific_leftjoin)) {
            switch ($new_table) {
               // No link
               case "glpi_auth_tables" :
                     $user_searchopt     = self::getOptions('User');

                     $specific_leftjoin  = self::addLeftJoin($itemtype, $rt, $already_link_tables,
                                                             "glpi_authldaps", 'auths_id', 0, 0,
                                                             $user_searchopt[30]['joinparams']);
                     $specific_leftjoin .= self::addLeftJoin($itemtype, $rt, $already_link_tables,
                                                             "glpi_authmails", 'auths_id', 0, 0,
                                                             $user_searchopt[31]['joinparams']);
                     break;
            }
         }

         if (empty($specific_leftjoin)) {
            switch ($joinparams['jointype']) {
               case 'child' :
                  $linkfield = getForeignKeyFieldForTable($cleanrt);
                  if (isset($joinparams['linkfield'])) {
                     $linkfield = $joinparams['linkfield'];
                  }

                  // Child join
                  $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                             ON (`$rt`.`id` = `$nt`.`$linkfield`
                                                 $addcondition)";
                  break;

               case 'item_item' :
                  // Item_Item join
                  $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON ((`$rt`.`id`
                                                   = `$nt`.`".getForeignKeyFieldForTable($cleanrt)."_1`
                                               OR `$rt`.`id`
                                                   = `$nt`.`".getForeignKeyFieldForTable($cleanrt)."_2`)
                                              $addcondition)";
                  break;

               case 'item_item_revert' :
                  // Item_Item join reverting previous item_item
                  $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON ((`$nt`.`id`
                                                   = `$rt`.`".getForeignKeyFieldForTable($cleannt)."_1`
                                               OR `$nt`.`id`
                                                   = `$rt`.`".getForeignKeyFieldForTable($cleannt)."_2`)
                                              $addcondition)";
                  break;

               case "itemtype_item" :
                  $used_itemtype = $itemtype;
                  if (isset($joinparams['specific_itemtype'])
                      && !empty($joinparams['specific_itemtype'])) {
                     $used_itemtype = $joinparams['specific_itemtype'];
                  }
                  // Itemtype join
                  $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON (`$rt`.`id` = `$nt`.`items_id`
                                              AND `$nt`.`itemtype` = '$used_itemtype'
                                              $addcondition) ";
                  break;

               default :
                  // Standard join
                  $specific_leftjoin = "LEFT JOIN `$new_table` $AS
                                          ON (`$rt`.`$linkfield` = `$nt`.`id`
                                              $addcondition)";
                  break;
            }
         }
//          echo $before.$specific_leftjoin.'<br>';
         return $before.$specific_leftjoin;
      }
 //     return '';
   }


   /**
    * Generic Function to add left join for meta items
    *
    * @param $from_type                   reference item type ID
    * @param $to_type                     item type to add
    * @param $already_link_tables2  array of tables already joined
    * @param $nullornott                  Used LEFT JOIN (null generation)
    *                                     or INNER JOIN for strict join
    *
    * @return Meta Left join string
   **/
   static function addMetaLeftJoin($from_type, $to_type, array &$already_link_tables2,
                                   $nullornott) {

      $LINK = " INNER JOIN ";
      if ($nullornott) {
         $LINK = " LEFT JOIN ";
      }

      switch ($from_type) {
         case 'Ticket' :
            $totable = getTableForItemType($to_type);
            array_push($already_link_tables2,$totable);
            return " $LINK `$totable`
                        ON (`$totable`.`id` = `glpi_tickets`.`items_id`
                            AND `glpi_tickets`.`itemtype` = '$to_type')";

         case 'Computer' :
            switch ($to_type) {
               case 'Printer' :
                  array_push($already_link_tables2, getTableForItemType($to_type));
                  array_push($already_link_tables2, "glpi_computers_items_$to_type");
                  return " $LINK `glpi_computers_items` AS glpi_computers_items_$to_type
                              ON (`glpi_computers_items_$to_type`.`computers_id`
                                       = `glpi_computers`.`id`
                                  AND `glpi_computers_items_$to_type`.`itemtype` = '$to_type')
                           $LINK `glpi_printers`
                              ON (`glpi_computers_items_$to_type`.`items_id` = `glpi_printers`.`id`) ";

               case 'Monitor' :
                  array_push($already_link_tables2, getTableForItemType($to_type));
                  array_push($already_link_tables2, "glpi_computers_items_$to_type");
                  return " $LINK `glpi_computers_items` AS glpi_computers_items_$to_type
                              ON (`glpi_computers_items_$to_type`.`computers_id`
                                       = `glpi_computers`.`id`
                                  AND `glpi_computers_items_$to_type`.`itemtype` = '$to_type')
                           $LINK `glpi_monitors`
                              ON (`glpi_computers_items_$to_type`.`items_id` = `glpi_monitors`.`id`) ";

               case 'Peripheral' :
                  array_push($already_link_tables2, getTableForItemType($to_type));
                  array_push($already_link_tables2, "glpi_computers_items_$to_type");
                  return " $LINK `glpi_computers_items` AS glpi_computers_items_$to_type
                              ON (`glpi_computers_items_$to_type`.`computers_id`
                                       = `glpi_computers`.`id`
                                  AND `glpi_computers_items_$to_type`.`itemtype` = '$to_type')
                           $LINK `glpi_peripherals`
                              ON (`glpi_computers_items_$to_type`.`items_id`
                                       = `glpi_peripherals`.`id`) ";

               case 'Phone' :
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  array_push($already_link_tables2, "glpi_computers_items_$to_type");
                  return " $LINK `glpi_computers_items` AS glpi_computers_items_$to_type
                              ON (`glpi_computers_items_$to_type`.`computers_id`
                                       = `glpi_computers`.`id`
                                  AND `glpi_computers_items_$to_type`.`itemtype` = '$to_type')
                           $LINK `glpi_phones`
                              ON (`glpi_computers_items_$to_type`.`items_id` = `glpi_phones`.`id`) ";

               case 'Software' :
                  /// TODO: link licenses via installed software OR by affected/computers_id ???
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  array_push($already_link_tables2,"glpi_softwareversions_$to_type");
                  array_push($already_link_tables2,"glpi_softwarelicenses_$to_type");
                  return " $LINK `glpi_computers_softwareversions`
                                    AS glpi_computers_softwareversions_$to_type
                              ON (`glpi_computers_softwareversions_$to_type`.`computers_id`
                                       = `glpi_computers`.`id`
                                  AND `glpi_computers_softwareversions_$to_type`.`is_deleted` = '0')
                           $LINK `glpi_softwareversions` AS glpi_softwareversions_$to_type
                              ON (`glpi_computers_softwareversions_$to_type`.`softwareversions_id`
                                       = `glpi_softwareversions_$to_type`.`id`)
                           $LINK `glpi_softwares`
                              ON (`glpi_softwareversions_$to_type`.`softwares_id`
                                       = `glpi_softwares`.`id`)
                           LEFT JOIN `glpi_softwarelicenses` AS glpi_softwarelicenses_$to_type
                              ON (`glpi_softwares`.`id`
                                       = `glpi_softwarelicenses_$to_type`.`softwares_id`".
                                  getEntitiesRestrictRequest(' AND',
                                                             "glpi_softwarelicenses_$to_type",
                                                             '', '', true).") ";
            }
            break;

         case 'Monitor' :
            switch ($to_type) {
               case 'Computer' :
                  array_push($already_link_tables2, getTableForItemType($to_type));
                  array_push($already_link_tables2, "glpi_computers_items_$to_type");
                  return " $LINK `glpi_computers_items` AS glpi_computers_items_$to_type
                              ON (`glpi_computers_items_$to_type`.`items_id` = `glpi_monitors`.`id`
                                  AND `glpi_computers_items_$to_type`.`itemtype` = '$from_type')
                           $LINK `glpi_computers`
                              ON (`glpi_computers_items_$to_type`.`computers_id`
                                       = `glpi_computers`.`id`) ";
            }
            break;

         case 'Printer' :
            switch ($to_type) {
               case 'Computer' :
                  array_push($already_link_tables2, getTableForItemType($to_type));
                  array_push($already_link_tables2, "glpi_computers_items_$to_type");
                  return " $LINK `glpi_computers_items` AS glpi_computers_items_$to_type
                              ON (`glpi_computers_items_$to_type`.`items_id` = `glpi_printers`.`id`
                                  AND `glpi_computers_items_$to_type`.`itemtype` = '$from_type')
                           $LINK `glpi_computers`
                              ON (`glpi_computers_items_$to_type`.`computers_id`
                                       = `glpi_computers`.`id` ".
                                  getEntitiesRestrictRequest("AND", 'glpi_computers').") ";
            }
            break;

         case 'Peripheral' :
            switch ($to_type) {
               case 'Computer' :
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  array_push($already_link_tables2, "glpi_computers_items_$to_type");
                  return " $LINK `glpi_computers_items` AS glpi_computers_items_$to_type
                              ON (`glpi_computers_items_$to_type`.`items_id`
                                       = `glpi_peripherals`.`id`
                                  AND `glpi_computers_items_$to_type`.`itemtype` = '$from_type')
                           $LINK `glpi_computers`
                              ON (`glpi_computers_items_$to_type`.`computers_id`
                                       = `glpi_computers`.`id`) ";
            }
            break;

         case 'Phone' :
            switch ($to_type) {
               case 'Computer' :
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  array_push($already_link_tables2, "glpi_computers_items_$to_type");
                  return " $LINK `glpi_computers_items` AS glpi_computers_items_$to_type
                              ON (`glpi_computers_items_$to_type`.`items_id` = `glpi_phones`.`id`
                                  AND `glpi_computers_items_$to_type`.`itemtype` = '$from_type')
                           $LINK `glpi_computers`
                              ON (`glpi_computers_items_$to_type`.`computers_id`
                                       = `glpi_computers.id`) ";
            }
            break;

         case 'Software' :
            switch ($to_type) {
               case 'Computer' :
                  array_push($already_link_tables2,getTableForItemType($to_type));
                  array_push($already_link_tables2,"glpi_softwareversions_$to_type");
                  array_push($already_link_tables2,"glpi_softwareversions_$to_type");
                  return " $LINK `glpi_softwareversions` AS glpi_softwareversions_$to_type
                              ON (`glpi_softwareversions_$to_type`.`softwares_id`
                                       = `glpi_softwares`.`id`)
                           $LINK `glpi_computers_softwareversions`
                                    AS glpi_computers_softwareversions_$to_type
                              ON (`glpi_computers_softwareversions_$to_type`.`softwareversions_id`
                                       = `glpi_softwareversions_$to_type`.`id`
                                  AND `glpi_computers_softwareversions_$to_type`.`is_deleted` = '0')
                           $LINK `glpi_computers`
                              ON (`glpi_computers_softwareversions_$to_type`.`computers_id`
                                       = `glpi_computers`.`id` ".
                                  getEntitiesRestrictRequest("AND", 'glpi_computers').") ";
            }
            break;
      }
   }


   /**
    * Generic Function to display Items
    *
    * @param $itemtype           item type
    * @param $ID                 ID of the SEARCH_OPTION item
    * @param $data         array retrieved data array
    * @param $num                number of the displayed item (default 0)
    *
    * @return string to print
   **/
   static function displayConfigItem($itemtype, $ID, $data=array(), $num=0) {

      $searchopt  = &self::getOptions($itemtype);

      $NAME       = "ITEM_";
      $table      = $searchopt[$ID]["table"];
      $field      = $searchopt[$ID]["field"];

      // Plugin can override core definition for its type
      if ($plug = isPluginItemType($itemtype)) {
         $function = 'plugin_'.$plug['plugin'].'_displayConfigItem';
         if (function_exists($function)) {
            $out = $function($itemtype, $ID, $data, $num);
            if (!empty($out)) {
               return $out;
            }
         }
      }


      switch ($table.".".$field) {
//          case "glpi_computers.date_mod" :
//          case "glpi_printers.date_mod" :
//          case "glpi_networkequipments.date_mod" :
//          case "glpi_peripherals.date_mod" :
//          case "glpi_phones.date_mod" :
//          case "glpi_softwares.date_mod" :
//          case "glpi_monitors.date_mod" :
//          case "glpi_documents.date_mod" :
//          case "glpi_users.last_login" :
//          case "glpi_users.date_mod" :
//             return " class='center'";

         case "glpi_tickets.priority" :
         case "glpi_problems.priority" :
            return " style=\"background-color:".$_SESSION["glpipriority_".$data[$NAME.$num]].";\" ";

         case "glpi_tickets.due_date" :
            if (($ID <> 151) && !empty($data[$NAME.$num])
                && ($data[$NAME.$num.'_2'] != CommonITILObject::WAITING)
                && ($data[$NAME.$num] < $_SESSION['glpi_currenttime'])) {
               return " class='tab_bg_2_2' ";
            }

         default :
            return "";
      }
   }


   /**
    * Generic Function to display Items
    *
    * @param $itemtype              item type
    * @param $ID                    ID of the SEARCH_OPTION item
    * @param $data            array containing data results
    * @param $num                   item num in the request
    * @param $meta                  is a meta item ? (default 0)
    * @param $addobjectparams array added parameters for union search
    *
    * @return string to print
   **/
   static function giveItem($itemtype, $ID, array $data, $num, $meta=0,
                            array $addobjectparams=array()) {
      global $CFG_GLPI;

      $showuserlink = 0;
      if (Session::haveRight('user','r')) {
         $showuserlink = 1;
      }

      $searchopt = &self::getOptions($itemtype);
      if (isset($CFG_GLPI["union_search_type"][$itemtype])
          && ($CFG_GLPI["union_search_type"][$itemtype] == $searchopt[$ID]["table"])) {

         if (isset($searchopt[$ID]['addobjectparams'])
             && $searchopt[$ID]['addobjectparams']) {
            return self::giveItem($data["TYPE"], $ID, $data, $num, $meta,
                                  $searchopt[$ID]['addobjectparams']);
         }
         return self::giveItem($data["TYPE"], $ID, $data, $num, $meta);
      }

      if (count($addobjectparams)) {
         $searchopt[$ID] = array_merge($searchopt[$ID], $addobjectparams);
      }
      // Plugin can override core definition for its type
      if ($plug = isPluginItemType($itemtype)) {
         $function = 'plugin_'.$plug['plugin'].'_giveItem';
         if (function_exists($function)) {
            $out = $function($itemtype, $ID, $data, $num);
            if (!empty($out)) {
               return $out;
            }
         }
      }

      $NAME = "ITEM_";
      if ($meta) {
         $NAME = "META_";
      }

      $table     = $searchopt[$ID]["table"];
      $field     = $searchopt[$ID]["field"];
      $linkfield = $searchopt[$ID]["linkfield"];

      /// TODO try to clean all specific cases using SpecificToDisplay
      switch ($table.'.'.$field) {
         case "glpi_users.name" :
            // USER search case
            if (($itemtype != 'User')
                && isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"]) {

               $out           = "";
               $split         = explode("$$$$",$data[$NAME.$num]);
               $count_display = 0;
               $added         = array();
               for ($k=0 ; $k<count($split) ; $k++) {
                  if ($split[$k] > 0) {
                     if ($count_display) {
                        $out .= "<br>";
                     }
                     $count_display++;

                     if ($itemtype == 'Ticket') {
                        $userdata = getUserName($split[$k],2);
                        $tooltip = "";
                        if (Session::haveRight('user','r')) {
                           $tooltip = Html::showToolTip($userdata["comment"],
                                                        array('link'    => $userdata["link"],
                                                              'display' => false));
                        }
                        $out     .= sprintf(__('%1$s %2$s'), $userdata['name'],
                                            $tooltip);
                     } else {
                        $out .= getUserName($split[$k], $showuserlink);
                     }
                  }
               }

               // Manage alternative_email for tickets_users
               if (($itemtype == 'Ticket')
                   && isset($data[$NAME.$num.'_2'])) {
                  $split = explode("$$$$",$data[$NAME.$num.'_2']);
                  for ($k=0 ; $k<count($split) ; $k++) {
                     $split2 = explode(" ",$split[$k]);
                     if ((count($split2) == 2) && ($split2[0] == 0) && !empty($split2[1])) {
                        if ($count_display) {
                           $out .= "<br>";
                        }
                        $count_display++;
                        $out .= "<a href='mailto:".$split2[1]."'>".$split2[1]."</a>";
                     }
                  }
               }

               return $out;
            }
            if ($itemtype != 'User') {
               $toadd = '';
               if (($itemtype == 'Ticket')
                   && ($data[$NAME.$num."_3"] > 0)) {
                  $userdata = getUserName($data[$NAME.$num."_3"], 2);
                  $toadd    = Html::showToolTip($userdata["comment"],
                                                array('link'    => $userdata["link"],
                                                      'display' => false));
               }
               if (Session::haveRight('user','r')) {
                  $usernameformat = formatUserName($data[$NAME.$num."_3"], $data[$NAME.$num],
                                                   $data[$NAME.$num."_2"], $data[$NAME.$num."_4"],
                                                   1);
               } else {
                  $usernameformat = formatUserName($data[$NAME.$num."_3"], $data[$NAME.$num],
                                                   $data[$NAME.$num."_2"], $data[$NAME.$num."_4"],
                                                   0);
               }
               return sprintf(__('%1$s %2$s'), $usernameformat, $toadd);
            }
            break;

         case "glpi_profiles.interface" :
            return Profile::getInterfaceName($data[$NAME.$num]);

         case "glpi_profiles.name" :
            if (($itemtype == 'User')
                && ($ID == 20)) {
               $out           = "";
               $split         = explode("$$$$",$data[$NAME.$num]);
               $split2        = explode("$$$$",$data[$NAME.$num."_2"]);
               $split3        = explode("$$$$",$data[$NAME.$num."_3"]);
               $split4        = explode("$$$$",$data[$NAME.$num."_4"]);
               $count_display = 0;
               $added         = array();
               for ($k=0 ; $k<count($split) ; $k++) {
                  if (strlen(trim($split[$k])) > 0) {
                     $text = sprintf(__('%1$s - %2$s'), $split[$k],
                                     Dropdown::getDropdownName('glpi_entities', $split2[$k]));
                     $comp = '';
                     if ($split3[$k]) {
                        $comp = __('R');
                        if ($split4[$k]) {
                           $comp = sprintf(__('%1$s%2$s'), $comp, ", ");
                        }
                     }
                     if ($split4[$k]) {
                        $comp = sprintf(__('%1$s%2$s'), $comp, __('D'));
                     }
                     if (!empty($comp)) {
                        $text = sprintf(__('%1$s %2$s'), $text, "(".$comp.")");
                     }

                     if (!in_array($text,$added)) {
                        if ($count_display) {
                           $out .= "<br>";
                        }
                        $count_display++;
                        $out     .= $text;
                        $added[]  = $text;
                     }
                  }
               }
               return $out;
            }
            break;

         case "glpi_entities.completename" :
            if ($itemtype == 'User') {
               $out           = "";
               $split         = explode("$$$$",$data[$NAME.$num]);
               $split2        = explode("$$$$",$data[$NAME.$num."_2"]);
               $split3        = explode("$$$$",$data[$NAME.$num."_3"]);
               $split4        = explode("$$$$",$data[$NAME.$num."_4"]);
               $added         = array();
               $count_display = 0;
               for ($k=0 ; $k<count($split) ; $k++) {
                  if (strlen(trim($split[$k])) > 0) {
                     $text = sprintf(__('%1$s - %2$s'), $split[$k],
                                     Dropdown::getDropdownName('glpi_profiles', $split2[$k]));
                        $comp = '';
                        if ($split3[$k]) {
                           $comp = __('R');
                           if ($split4[$k]) {
                              $comp = sprintf(__('%1$s%2$s'), $comp, ", ");
                           }
                        }
                        if ($split4[$k]) {
                           $comp = sprintf(__('%1$s%2$s'), $comp, __('D'));
                        }
                        if (!empty($comp)) {
                           $text = sprintf(__('%1$s %2$s'), $text, "(".$comp.")");
                        }
                     if (!in_array($text,$added)) {
                        if ($count_display) {
                           $out .= "<br>";
                        }
                        $count_display++;
                        $out    .= $text;
                        $added[] = $text;
                     }
                  }
               }
               return $out;
            }
            break;

         case "glpi_documenttypes.icon" :
            if (!empty($data[$NAME.$num])) {
               return "<img class='middle' alt='' src='".$CFG_GLPI["typedoc_icon_dir"]."/".
                        $data[$NAME.$num]."'>";
            }
            return "&nbsp;";

         case "glpi_documents.filename" :
            $doc = new Document();
            if ($doc->getFromDB($data['id'])) {
               return $doc->getDownloadLink();
            }
            return NOT_AVAILABLE;

         case "glpi_deviceharddrives.specificity" :
         case "glpi_devicememories.specificity" :
         case "glpi_deviceprocessors.specificity" :
            return $data[$NAME.$num];

         case "glpi_networkports.mac" :
            $out = "";
            if ($itemtype == 'Computer') {
               $displayed = array();
               if (!empty($data[$NAME.$num."_2"])) {
                  $split         = explode("$$$$",$data[$NAME.$num."_2"]);
                  $count_display = 0;
                  for ($k=0 ; $k<count($split) ; $k++) {
                     $lowstr = Toolbox::strtolower($split[$k]);
                     if ((strlen(trim($split[$k])) > 0)
                         && !in_array($lowstr, $displayed)) {
                        if ($count_display) {
                           $out .= "<br>";
                        }
                        $count_display++;
                        $out        .= $split[$k];
                        $displayed[] = $lowstr;
                     }
                  }
                  if (!empty($data[$NAME.$num])) {
                     $out .= "<br>";
                  }
               }
               if (!empty($data[$NAME.$num])) {
                  $split         = explode("$$$$",$data[$NAME.$num]);
                  $count_display = 0;
                  for ($k=0 ; $k<count($split) ; $k++) {
                     $lowstr = Toolbox::strtolower($split[$k]);
                     if ((strlen(trim($split[$k])) > 0)
                         && !in_array($lowstr, $displayed)) {
                        if ($count_display) {
                           $out .= "<br>";
                        }
                        $count_display++;
                        $out        .= $split[$k];
                        $displayed[] = $lowstr;
                     }
                  }
               }
               return $out;
            }
            break;

         case "glpi_contracts.duration" :
         case "glpi_contracts.notice" :
         case "glpi_contracts.periodicity" :
         case "glpi_contracts.billing" :
            if (!empty($data[$NAME.$num])) {
               $split  = explode('$$$$', $data[$NAME.$num]);
               $output = "";
               foreach ($split as $duration) {
                  $output .= (empty($output)?'':'<br>') .
                              sprintf(_n('%d month', '%d months', $duration), $duration);
               }
               return $output;
            }
            return "&nbsp;";

         case "glpi_contracts.renewal" :
            return Contract::getContractRenewalName($data[$NAME.$num]);

//          case "glpi_infocoms.sink_time" :
//             if (!empty($data[$NAME.$num])) {
//                $split = explode("$$$$", $data[$NAME.$num]);
//                $out   = '';
//                foreach ($split as $val) {
//                   $out .= (empty($out)?'':'<br>');
//                   if ($val > 0) {
//                      //TRANS: %d is a number of years
//                      $out .= sprintf(_n('%d year', '%d years', $val), $val);
//                   }
//                }
//                return $out;
//             }
//             return "&nbsp;";
//
//          case "glpi_infocoms.warranty_duration" :
//             if (!empty($data[$NAME.$num])) {
//                $split = explode("$$$$", $data[$NAME.$num]);
//                $out   = '';
//                foreach ($split as $val) {
//                   $out .= (empty($out)?'':'<br>');
//                   if ($val > 0) {
//                      $out .= sprintf(_n('%d month', '%d months', $val), $val);
//                   }
//                   if ($val < 0) {
//                      $out .= __('Lifelong');
//                   }
//                }
//                return $out;
//             }
//             return "&nbsp;";

         case "glpi_infocoms.sink_type" :
            $split = explode("$$$$", $data[$NAME.$num]);
            $out   = '';
            foreach ($split as $val) {
               $out .= (empty($out)?'':'<br>').Infocom::getAmortTypeName($val);
            }
            return $out;

         case "glpi_infocoms.alert" :
            if ($data[$NAME.$num] == pow(2,Alert::END)) {
               return __s('Warranty expiration date');
            }
            return "";

         case "glpi_contracts.alert" :
            switch ($data[$NAME.$num]) {
               case pow(2,Alert::END) :
                  return __('End');

               case pow(2,Alert::NOTICE) :
                  return __('Notice');

               case pow(2,Alert::END) + pow(2,Alert::NOTICE) :
                  return __('End + Notice');
            }
            return "";

         case "glpi_tickets_tickets.tickets_id_1" :
               $out        = "";
               $split      = explode("$$$$",$data[$NAME.$num]);
               $split2     = explode("$$$$",$data[$NAME.$num."_2"]);
               $displayed  = array();
               for ($k=0 ; $k<count($split) ; $k++) {
                  $linkid = ($split[$k] == $data['id']) ? $split2[$k] : $split[$k];
                  if (($linkid > 0) && !isset($displayed[$linkid])) {
                     $text  = "<a ";
                     $text .= "href=\"".$CFG_GLPI["root_doc"]."/front/ticket.form.php?id=$linkid\">";
                     $text .= Dropdown::getDropdownName('glpi_tickets', $linkid)."</a>";
                     if (count($displayed)) {
                        $out .= "<br>";
                     }
                     $displayed[$linkid] = $linkid;
                     $out               .= $text;
                  }
               }
               return $out;

         case "glpi_problems.count" :
            if (($data[$NAME.$num] > 0)
                && Session::haveRight("show_all_problem","1")) {
               if ($itemtype == 'ITILCategory') {
                  $options['field'][0]      = 7;
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = $data['id'];
                  $options['link'][0]       = 'AND';
               }

               $options['reset'] = 'reset';

               $out  = "<a id='problem$itemtype".$data['id']."' ";
               $out .= "href=\"".$CFG_GLPI["root_doc"]."/front/problem.php?".
                             Toolbox::append_params($options, '&amp;')."\">";
               $out .= $data[$NAME.$num]."</a>";

            } else {
               $out = $data[$NAME.$num];
            }
            return $out;

         case "glpi_tickets.count" :
            if (($data[$NAME.$num] > 0)
                && Session::haveRight("show_all_ticket","1")) {

               if ($itemtype == 'User') {
                  $options['field'][0]      = 4;
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = $data['id'];
                  $options['link'][0]       = 'AND';

                  $options['field'][1]      = 22;
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = $data['id'];
                  $options['link'][1]       = 'OR';

                  $options['field'][2]      = 5;
                  $options['searchtype'][2] = 'equals';
                  $options['contains'][2]   = $data['id'];
                  $options['link'][2]       = 'OR';

               } else if ($itemtype == 'ITILCategory') {
                  $options['field'][0]      = 7;
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = $data['id'];
                  $options['link'][0]       = 'AND';

               } else {
                  $options['field'][0]       = 12;
                  $options['searchtype'][0]  = 'equals';
                  $options['contains'][0]    = 'all';
                  $options['link'][0]        = 'AND';

                  $options['itemtype2'][0]   = $itemtype;
                  $options['field2'][0]      = self::getOptionNumber($itemtype, 'name');
                  $options['searchtype2'][0] = 'equals';
                  $options['contains2'][0]   = $data['id'];
                  $options['link2'][0]       = 'AND';
               }

               $options['reset'] = 'reset';

               $out  = "<a id='ticket$itemtype".$data['id']."' ";
               $out .= "href=\"".$CFG_GLPI["root_doc"]."/front/ticket.php?".
                             Toolbox::append_params($options, '&amp;')."\">";
               $out .= $data[$NAME.$num]."</a>";

            } else {
               $out = $data[$NAME.$num];
            }
            return $out;

         case "glpi_tickets.due_date" :
            // Due date + progress
            if ($ID == 151) {
                  $out = Html::convDate($data[$NAME.$num]);
               // No due date in waiting status
               if ($data[$NAME.$num.'_2'] == CommonITILObject::WAITING) {
                  return '';
               }
               if (empty($data[$NAME.$num])) {
                  return '';
               }
               if (($data[$NAME.$num.'_2'] == Ticket::SOLVED)
                   || ($data[$NAME.$num.'_2'] == Ticket::CLOSED)) {
                  return $data[$NAME.$num];
               }
               $ticket = new Ticket();
               $ticket->getFromDB($data['ITEM_0']);
               $percentage = 0;
               $totaltime = 0;
               $currenttime = 0;
               if ($ticket->fields['slas_id'] != 0) { // Have SLA
                  $sla = new SLA();
                  $sla->getFromDB($ticket->fields['slas_id']);
                  $currenttime = $sla->getActiveTimeBetween($ticket->fields['date'],
                                                            date('Y-m-d H:i:s'));
                  $totaltime   = $sla->getActiveTimeBetween($ticket->fields['date'],
                                                            $data[$NAME.$num]);
               } else {
                  $calendars_id = Entity::getUsedConfig('calendars_id',
                                                        $ticket->fields['entities_id']);
                  if ($calendars_id != 0) { // Ticket entity have calendar
                     $calendar = new Calendar();
                     $calendar->getFromDB($calendars_id);
                     $currenttime = $calendar->getActiveTimeBetween($ticket->fields['date'],
                                                                    date('Y-m-d H:i:s'));
                     $totaltime   = $calendar->getActiveTimeBetween($ticket->fields['date'],
                                                                    $data[$NAME.$num]);
                  } else { // No calendar
                     $currenttime = strtotime(date('Y-m-d H:i:s'))
                                                - strtotime($ticket->fields['date']);
                     $totaltime   = strtotime($data[$NAME.$num])
                                                - strtotime($ticket->fields['date']);
                  }
               }
               if ($totaltime != 0)  {
                  $percentage  = round((100 * $currenttime) / $totaltime);
               } else {
                  // Total time is null : no active time
                  $percentage = 100;
               }

               if ($percentage > 100) {
                  $percentage = 100;
               }
               $percentage_text = $percentage;
               if ($_SESSION['glpiduedatewarning_unit'] == '%') {
                  $less_warn_limit = $_SESSION['glpiduedatewarning_less'];
                  $less_warn       = (100 - $percentage);
               } else if ($_SESSION['glpiduedatewarning_unit'] == 'hours') {
                  $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * HOUR_TIMESTAMP;
                  $less_warn       = ($totaltime - $currenttime);
               } else if ($_SESSION['glpiduedatewarning_unit'] == 'days') {
                  $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * DAY_TIMESTAMP;
                  $less_warn       = ($totaltime - $currenttime);
               }

               if ($_SESSION['glpiduedatecritical_unit'] == '%') {
                  $less_crit_limit = $_SESSION['glpiduedatecritical_less'];
                  $less_crit       = (100 - $percentage);
               } else if ($_SESSION['glpiduedatecritical_unit'] == 'hours') {
                  $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * HOUR_TIMESTAMP;
                  $less_crit       = ($totaltime - $currenttime);
               } else if ($_SESSION['glpiduedatecritical_unit'] == 'days') {
                  $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * DAY_TIMESTAMP;
                  $less_crit       = ($totaltime - $currenttime);
               }

               $color = $_SESSION['glpiduedateok_color'];
               if ($less_crit < $less_crit_limit) {
                  $color = $_SESSION['glpiduedatecritical_color'];
               } else if ($less_warn < $less_warn_limit) {
                  $color = $_SESSION['glpiduedatewarning_color'];
               }

               //Calculate bar progress
               $out  .= "<div class='center' style='background-color: #ffffff; width: 100%;
                         border: 1px solid #9BA563;' >";
               $out .= "<div style='position:absolute;'>&nbsp;".$percentage_text."%</div>";
               $out .= "<div class='center' style='background-color: ".$color.";
                         width: ".$percentage."%; height: 12px' ></div>";
               $out .= "</div>";
               return $out;
            }
            break;

         case "glpi_softwarelicenses.number" :
            if ($data[$NAME.$num."_2"] == -1) {
               return __('Unlimited');
            }
            if (empty($data[$NAME.$num])) {
               return 0;
            }
            return $data[$NAME.$num];

         case "glpi_auth_tables.name" :
            return Auth::getMethodName($data[$NAME.$num], $data[$NAME.$num."_2"], 1,
                                       $data[$NAME.$num."_3"].$data[$NAME.$num."_4"]);

         case "glpi_reservationitems.comment" :
            if (empty($data[$NAME.$num])) {
               return "<a title=\"".__s('Modify the comment')."\"
                        href='".$CFG_GLPI["root_doc"]."/front/reservationitem.form.php?id=".
                        $data["refID"]."' >".__('None')."</a>";
            }
            return "<a title=\"".__s('Modify the comment')."\"
                     href='".$CFG_GLPI["root_doc"]."/front/reservationitem.form.php?id=".
                     $data['refID']."' >".Html::resume_text($data[$NAME.$num])."</a>";

         case 'glpi_notifications.mode' :
               return Notification::getMode($data[$NAME.$num]);

         case 'glpi_notifications.event' :
               $item = NotificationTarget::getInstanceByType($data['itemtype']);
               if ($item) {
                  $events = $item->getAllEvents();
                  if (isset($events[$data[$NAME.$num]])) {
                     return $events[$data[$NAME.$num]];
                  }
               }
               return '';

         case 'glpi_reminders.state' :
            return Planning::getState($data[$NAME.$num]);

         case 'glpi_crontasks.description' :
            $tmp = new CronTask();
            return $tmp->getDescription($data['id']);

         case 'glpi_crontasks.state' :
            return CronTask::getStateName($data[$NAME.$num]);

         case 'glpi_crontasks.mode' :
            return CronTask::getModeName($data[$NAME.$num]);

         case 'glpi_crontasks.itemtype' :
            if ($plug = isPluginItemType($data[$NAME.$num])) {
               return $plug['plugin'];
            }
            return '';

         case 'glpi_changes.status':
            $status = Change::getStatus($data[$NAME.$num]);
            return "<img src=\"".Change::getStatusIconURL($data[$NAME.$num])."\"
                     alt=\"$status\" title=\"$status\">&nbsp;$status";

         case 'glpi_problems.status':
            $status = Problem::getStatus($data[$NAME.$num]);
            return "<img src=\"".Problem::getStatusIconURL($data[$NAME.$num])."\"
                     alt=\"$status\" title=\"$status\">&nbsp;$status";

         case 'glpi_tickets.status':
            $status = Ticket::getStatus($data[$NAME.$num]);
            return "<img src=\"".Ticket::getStatusIconURL($data[$NAME.$num])."\"
                     alt=\"$status\" title=\"$status\">&nbsp;$status";

         case 'glpi_tickets.items_id' :
            if (!empty($data[$NAME.$num."_2"])
                && ($item = getItemForItemtype($data[$NAME.$num."_2"]))) {
               if ($item->getFromDB($data[$NAME.$num])) {
                  return $item->getLink(array('comments' => true));
               }
            }
            return '&nbsp;';

         case 'glpi_tickets.name' :
            if (isset($searchopt[$ID]['forcegroupby'])
                && $searchopt[$ID]['forcegroupby']) {
               $split = explode("$$$$",$data[$NAME.$num]);
               $out   = '';
               $link  = Toolbox::getItemTypeFormURL('Ticket');
               foreach ($split as $val) {
                  if (!empty($val)) {
                     $split2 = self::explodeWithID("$$", $val);
                     $out   .= "<a id='ticket".$split2[1]."' href=\"".$link;
                     $out   .= (strstr($link,'?') ?'&amp;' :  '?');
                     $out   .= 'id='.$split2[1];
                     $out   .= "\">";
                     $name   = $split2[0];
                     if ($_SESSION["glpiis_ids_visible"] || empty($split2[0])) {
                        $name = sprintf(__('%1$s (%2$s)'), $name, $split2[1]);
                     }
                     $out .= $name."</a><br>";
                  }
               }
               return $out;
            }
            $link = Toolbox::getItemTypeFormURL('Ticket');
            $out  = "<a id='ticket".$data[$NAME.$num."_2"]."' href=\"".$link;
            $out .= (strstr($link,'?') ?'&amp;' :  '?');
            $out .= 'id='.$data[$NAME.$num."_2"];
            // Force solution tab if solved
            if ($data[$NAME.$num."_4"] == CommonITILObject::SOLVED) {
               $out .= "&amp;forcetab=Ticket$2";
            }
            $out .= "\">";
            $name = $data[$NAME.$num];
            if ($_SESSION["glpiis_ids_visible"] || empty($data[$NAME.$num])) {
               $name = sprintf(__('%1$s (%2$s)'), $name, $data[$NAME.$num."_2"]);
            }
            $out .= $name."</a>";

            $out = sprintf(__('%1$s %2$s'), $out,
                           Html::showToolTip(nl2br($data[$NAME.$num."_3"]),
                                                   array('applyto' => 'ticket'.$data[$NAME.$num."_2"],
                                                         'display' => false)));
            return $out;



         case 'glpi_ticketvalidations.status' :
            $split = explode("$$$$",$data[$NAME.$num]);
            $out   = '';
            foreach ($split as $val) {
               if (!empty($val)) {
                  $split2 = self::explodeWithID("$$", $val);
                  $status  = TicketValidation::getStatus($split2[0]);
                  $bgcolor = TicketValidation::getStatusColor($split2[0]);
                  $out    .= (empty($out)?'':'<br>').
                           "<div style=\"background-color:".$bgcolor.";\">".$status.'</div>';
               }
            }
            return $out;

         case 'glpi_ticketsatisfactions.type' :
            return TicketSatisfaction::getTypeInquestName($data[$NAME.$num]);

         case 'glpi_ticketsatisfactions.satisfaction' :
            return TicketSatisfaction::displaySatisfaction($data[$NAME.$num]);

         case 'glpi_notimportedemails.reason' :
            return NotImportedEmail::getReason($data[$NAME.$num]);

         case 'glpi_notimportedemails.messageid' :
            $clean = array('<' => '',
                           '>' => '');
            return strtr($data[$NAME.$num], $clean);
      }


      //// Default case

      // Link with plugin tables : need to know left join structure
      if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table.'.'.$field, $matches)) {
         if (count($matches) == 2) {
            $plug     = $matches[1];
            $function = 'plugin_'.$plug.'_giveItem';
            if (function_exists($function)) {
               $out = $function($itemtype,$ID,$data,$num);
               if (!empty($out)) {
                  return $out;
               }
            }
         }
      }
      $unit = '';
      if (isset($searchopt[$ID]['unit'])) {
         $unit = $searchopt[$ID]['unit'];
      }

      /// TODO try to use getvalueToDisplay instead of redefine display system
      // Preformat items
      if (isset($searchopt[$ID]["datatype"])) {
         switch ($searchopt[$ID]["datatype"]) {
            case "itemlink" :
               $linkitemtype = getItemTypeForTable($searchopt[$ID]["table"]);
               if (isset($data[$NAME.$num."_2"]) && strlen($data[$NAME.$num."_2"])) {
                  $link = Toolbox::getItemTypeFormURL($linkitemtype);

                  $out  = "<a id='".$itemtype."_".$data[$NAME.$num."_2"]."' href=\"".$link;
                  $out .= (strstr($link,'?') ?'&amp;' :  '?');
                  $out .= 'id='.$data[$NAME.$num."_2"];

                  if (isset($searchopt[$ID]['forcetab'])) {
                  $out .= "&amp;forcetab=".$searchopt[$ID]['forcetab'];
                  }
                  $out .= "\">";
                  $name = Dropdown::getValueWithUnit($data[$NAME.$num],$unit);
                  if ($_SESSION["glpiis_ids_visible"] || empty($data[$NAME.$num])) {
                     $name = sprintf(__('%1$s (%2$s)'), $name, $data[$NAME.$num."_2"]);
                  }
                  $out .= $name."</a>";
                  return $out;
               }

               $out           = "";
               $split         = explode("$$$$", $data[$NAME.$num]);
               $count_display = 0;
               $separate      = '<br>';
               if (isset($searchopt[$ID]['splititems']) && $searchopt[$ID]['splititems']) {
                  $separate = '<hr>';
               }

               for ($k=0 ; $k<count($split) ; $k++) {
                  if (strlen(trim($split[$k])) > 0) {
                     $split2 = self::explodeWithID("$$", $split[$k]);
                     if (isset($split2[1]) && ($split2[1] > 0)) {
                        if ($count_display) {
                           $out .= $separate;
                        }
                        $count_display++;
                        $page  = Toolbox::getItemTypeFormURL($linkitemtype);
                        $page .= (strpos($page,'?') ? '&id' : '?id');
                        $name  = Dropdown::getValueWithUnit($split2[0],$unit);
                        if ($_SESSION["glpiis_ids_visible"] || empty($split2[0])) {
                           $name = sprintf(__('%1$s (%2$s)'), $name, $split2[1]);
                        }
                        $out  .= "<a id='".$linkitemtype."_".$data['id']."_".
                                    $split2[1]."' href='$page=".$split2[1]."'>".$name."</a>";
                     }
                  }
               }
               return $out;

            case "text" :
               $separate = '<br>';
               if (isset($searchopt[$ID]['splititems']) && $searchopt[$ID]['splititems']) {
                  $separate = '<hr>';
               }
               $split = explode("$$$$", $data[$NAME.$num]);
               $out = '';
               $count_display = 0;
               foreach ($split as $val) {
                  if (strlen(trim($val)) > 0) {
                     $split2 = self::explodeWithID("$$", $val);
                     if ($count_display) {
                        $out .= $separate;
                     }
                     $count_display++;
                     if (isset($searchopt[$ID]['htmltext']) && $searchopt[$ID]['htmltext']) {
                        $text = Html::clean(Toolbox::unclean_cross_side_scripting_deep(nl2br($split2[0])));
                     } else {
                        $text = nl2br($split2[0]);
                     }

                     if (self::$output_type == self::HTML_OUTPUT
                           && (Toolbox::strlen($text) > $CFG_GLPI['cut'])) {
                        $rand = mt_rand();
                        $out .= sprintf(__('%1$s %2$s'), "<span id='text$rand'>".
                                                         Html::resume_text($text, $CFG_GLPI['cut']).
                                                        '</span>',
                                                         Html::showToolTip($text,
                                                                  array('applyto' => "text$rand",
                                                                        'display' => false)));

                     } else {
                        $out .= $text;
                     }

                  }
               }

               return $out;

            case "date" :
            case "date_delay" :
               $split = explode("$$$$", $data[$NAME.$num]);
               $out   = '';
               foreach ($split as $val) {
                  $out .= (empty($out)?'':'<br>').Html::convDate($val);
               }
               return $out;

            case "datetime" :
               $split = explode("$$$$", $data[$NAME.$num]);
               $out   = '';
               foreach ($split as $val) {
                  $out .= (empty($out)?'':'<br>').Html::convDateTime($val);
               }
               return $out;

            case "timestamp" :
               $withseconds = false;
               if (isset($searchopt[$ID]['withseconds'])) {
                  $withseconds = $searchopt[$ID]['withseconds'];
               }
               $withdays = true;
               if (isset($searchopt[$ID]['withdays'])) {
                  $withdays = $searchopt[$ID]['withdays'];
               }

               $split = explode("$$$$", $data[$NAME.$num]);
               $out   = '';
               foreach ($split as $val) {
                   $out .= (empty($out)?'':'<br>').Html::timestampToString($val,$withseconds, $withdays);
               }
               return $out;

            case "email" :
               $split         = explode('$$$$', $data[$NAME.$num]);
               $out           = '';
               $count_display = 0;
               foreach ($split as $val) {
                  $split2 = self::explodeWithID("$$", $val);
                  if ($count_display) {
                     $out .= "<br>";
                  }
                  $count_display++;
                  if (!empty($val)) {
                     $out .= (empty($out)?'':'<br>');
                     $out .= "<a href='mailto:$split2[0]'>$split2[0]</a>";
                  }
               }
               return (empty($out) ? "&nbsp;" : $out);

            case "weblink" :
               $orig_link = trim($data[$NAME.$num]);
               if (!empty($orig_link)) {
                  // strip begin of link
                  $link = preg_replace('/https?:\/\/(www[^\.]*\.)?/','',$orig_link);
                  $link = preg_replace('/\/$/', '', $link);
                  if (Toolbox::strlen($link)>$CFG_GLPI["url_maxlength"]) {
                     $link = Toolbox::substr($link, 0, $CFG_GLPI["url_maxlength"])."...";
                  }
                  return "<a href=\"".formatOutputWebLink($orig_link)."\" target='_blank'>$link</a>";
               }
               return "&nbsp;";

            case "number" :
               if (isset($searchopt[$ID]['forcegroupby']) && $searchopt[$ID]['forcegroupby']) {
                  $out           = "";
                  $split         = explode("$$$$", $data[$NAME.$num]);
                  $count_display = 0;
                  for ($k=0 ; $k<count($split) ; $k++) {
                     if (strlen(trim($split[$k])) > 0) {
                        $split2 = self::explodeWithID("$$", $split[$k]);
                        if ($count_display) {
                           $out .= "<br>";
                        }
                        $count_display++;
                        if (isset($searchopt[$ID]['toadd']) && isset($searchopt[$ID]['toadd'][$split2[0]])) {
                           $out .= $searchopt[$ID]['toadd'][$split2[0]];
                        } else {
                           $number = str_replace(' ', '&nbsp;', Html::formatNumber($split2[0], false,0));
                           $out .= Dropdown::getValueWithUnit($number, $unit);
                        }
                     }
                  }
                  return $out;
               }
               if (isset($searchopt[$ID]['toadd']) && isset($searchopt[$ID]['toadd'][$data[$NAME.$num]])) {
                  return $searchopt[$ID]['toadd'][$data[$NAME.$num]];
               } else {
                  $number = str_replace(' ', '&nbsp;', Html::formatNumber($data[$NAME.$num], false, 0));
                  return Dropdown::getValueWithUnit($number, $unit);
               }


            case "decimal" :
               if (isset($searchopt[$ID]['forcegroupby']) && $searchopt[$ID]['forcegroupby']) {
                  $out           = "";
                  $split         = explode("$$$$" ,$data[$NAME.$num]);
                  $count_display = 0;
                  for ($k=0 ; $k<count($split) ; $k++) {
                     if (strlen(trim($split[$k])) > 0) {
                        $split2 = self::explodeWithID("$$", $split[$k]);

                        if ($count_display) {
                           $out .= "<br>";
                        }
                        $count_display++;
                        if (isset($searchopt[$ID]['toadd']) && isset($searchopt[$ID]['toadd'][$split2[0]])) {
                           $out .= $searchopt[$ID]['toadd'][$split2[0]];
                        } else {
                           $number = str_replace(' ', '&nbsp;', Html::formatNumber($split2[0]));
                           $out .= Dropdown::getValueWithUnit($number, $unit);
                        }
                     }
                  }
                  return $out;
               }
               $number = str_replace(' ', '&nbsp;', Html::formatNumber($data[$NAME.$num]));
               return Dropdown::getValueWithUnit($number, $unit);

            case "bool" :
               if (isset($searchopt[$ID]['forcegroupby']) && $searchopt[$ID]['forcegroupby']) {
                  $out           = "";
                  $split         = explode("$$$$", $data[$NAME.$num]);
                  $count_display = 0;
                  for ($k=0 ; $k<count($split) ; $k++) {
                     if (strlen(trim($split[$k])) > 0) {
                        $split2 = self::explodeWithID("$$", $split[$k]);
                        if ($count_display) {
                           $out .= "<br>";
                        }
                        $count_display++;
                        $out .= Dropdown::getValueWithUnit(Dropdown::getYesNo($split2[0]),$unit);
                     }
                  }
                  return $out;
               }
               return Dropdown::getValueWithUnit(Dropdown::getYesNo($data[$NAME.$num]),$unit);

            case "right":
               return Profile::getRightValue($data[$NAME.$num]);

            case "itemtypename":
               if ($obj = getItemForItemtype($data[$NAME.$num])) {
                  return $obj->getTypeName();
               }
               return "";

            case "language":
               if (isset($CFG_GLPI['languages'][$data[$NAME.$num]])) {
                  return $CFG_GLPI['languages'][$data[$NAME.$num]][0];
               }
               return __('Default value');
         }
      }

      // Manage items with need group by / group_concat
      if (isset($searchopt[$ID]['forcegroupby']) && $searchopt[$ID]['forcegroupby']) {
         $out           = "";
         $split         = explode("$$$$", $data[$NAME.$num]);
         $count_display = 0;
         $separate      = '<br>';
         if (isset($searchopt[$ID]['splititems']) && $searchopt[$ID]['splititems']) {
            $separate = '<hr>';
         }
         for ($k=0 ; $k<count($split) ; $k++) {
            if (strlen(trim($split[$k])) > 0) {
               if ($count_display) {
                  $out .= $separate;
               }
               $withoutid = self::explodeWithID("$$", $split[$k]);
               $count_display++;
               // Get specific display if available
               $itemtype = getItemTypeForTable($table);
               if ($item = getItemForItemtype($itemtype)) {
                  $tmpdata  = array($field => $withoutid[0]);
                  $specific = $item->getSpecificValueToDisplay($field, $tmpdata, array('html' => true));
               }
               if (!empty($specific)) {
                  $out .= $specific;
               } else {
                  $out      .= Dropdown::getValueWithUnit($withoutid[0], $unit);
               }
            }
         }
         return $out;
      }

      // Get specific display if available
      $itemtype = getItemTypeForTable($table);
      if ($item = getItemForItemtype($itemtype)) {
         $tmpdata  = array($field => $data[$NAME.$num]);
         if (isset($searchopt[$ID]['additionalfields']) && count($searchopt[$ID]['additionalfields'])) {
            foreach ($searchopt[$ID]['additionalfields'] as $key) {
               $tmpdata[$key] = $data[$NAME.$num.'_'.$key];
            }
         }
         $specific = $item->getSpecificValueToDisplay($field, $tmpdata, array('html' => true));
         if (!empty($specific)) {
            return $specific;
         }
      }

      // Manage auto CONCAT id
      $split = self::explodeWithID('$$', $data[$NAME.$num]);
      $split[0] = trim($split[0]);
      if (isset($searchopt[$ID]['toadd']) && isset($searchopt[$ID]['toadd'][$split[0]])) {
         return $searchopt[$ID]['toadd'][$split[0]];
      }
      // Empty is 0 or empty
      if (empty($split[0])&& isset($searchopt[$ID]['emptylabel'])) {
         return $searchopt[$ID]['emptylabel'];
      }
      return Dropdown::getValueWithUnit($split[0], $unit);
   }


   /**
    * Reset save searches
    *
    * @return nothing
   **/
   static function resetSaveSearch() {

      unset($_SESSION['glpisearch']);
      $_SESSION['glpisearch']       = array();
      unset($_SESSION['glpisearchcount']);
      $_SESSION['glpisearchcount']  = array();
      unset($_SESSION['glpisearchcount2']);
      $_SESSION['glpisearchcount2'] = array();
   }


   /**
    * Completion of the URL $_GET values with the $_SESSION values or define default values
    *
    * @param $itemtype        item type to manage
    * @param $usesession      Use datas save in session (true by default)
    * @param $forcebookmark   force trying to load parameters from default bookmark:
    *                         used for global search (false by default)
    *
    * @return nothing
   **/
   static function manageGetValues($itemtype, $usesession=true, $forcebookmark=false) {
      global $_GET, $DB;

      $redirect = false;

      if (isset($_GET["add_search_count"]) && $_GET["add_search_count"]) {
         $_SESSION["glpisearchcount"][$itemtype]++;
         Html::redirect(str_replace("add_search_count=1&", "", $_SERVER['REQUEST_URI']));
      }

      if (isset($_GET["delete_search_count"]) && $_GET["delete_search_count"]) {
         if ($_SESSION["glpisearchcount"][$itemtype] > 1) {
            $_SESSION["glpisearchcount"][$itemtype]--;
         }
         Html::redirect(str_replace("delete_search_count=1&", "", $_SERVER['REQUEST_URI']));
      }

      if (isset($_GET["add_search_count2"]) && $_GET["add_search_count2"]) {
         $_SESSION["glpisearchcount2"][$itemtype]++;
         Html::redirect(str_replace("add_search_count2=1&", "", $_SERVER['REQUEST_URI']));
      }

      if (isset($_GET["delete_search_count2"]) && $_GET["delete_search_count2"]) {
         if ($_SESSION["glpisearchcount2"][$itemtype] >= 1) {
            $_SESSION["glpisearchcount2"][$itemtype]--;
         }
         Html::redirect(str_replace("delete_search_count2=1&", "", $_SERVER['REQUEST_URI']));
      }

      $default_values = array();

      $default_values["start"]       = 0;
      $default_values["order"]       = "ASC";
      $default_values["is_deleted"]  = 0;
      $default_values["distinct"]    = "N";
      $default_values["link"]        = array();
      $default_values["field"]       = array();
      $default_values["contains"]    = array(0 => "");
      $default_values["searchtype"]  = array(0 => "contains");
      $default_values["link2"]       = array();
      $default_values["field2"]      = array(0 => "view");
      $default_values["contains2"]   = array(0 => "");
      $default_values["itemtype2"]   = "";
      $default_values["searchtype2"] = "";
      $default_values["sort"]        = 1;

      if (($itemtype != 'AllAssets')
          && class_exists($itemtype)
          && method_exists($itemtype,'getDefaultSearchRequest')) {

         $default_values = array_merge($default_values,
                                       call_user_func(array($itemtype,
                                                            'getDefaultSearchRequest')));
      }

      // First view of the page or force bookmark : try to load a bookmark
      if ($forcebookmark
          || ($usesession
              && !isset($_GET["reset"])
              && !isset($_SESSION['glpisearch'][$itemtype]))) {

         $query = "SELECT `bookmarks_id`
                   FROM `glpi_bookmarks_users`
                   WHERE `users_id`='".Session::getLoginUserID()."'
                         AND `itemtype` = '$itemtype'";
         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               $IDtoload = $DB->result($result, 0, 0);
               // Set session variable
               $_SESSION['glpisearch'][$itemtype] = array();
               // Load bookmark on main window
               $bookmark = new Bookmark();
               // Only get datas for bookmarks
               if ($forcebookmark) {
                  $_GET = $bookmark->getParameters($IDtoload);
               } else {
                  $bookmark->load($IDtoload, false);
               }
            }
         }
      }

      if ($usesession
          && isset($_GET["reset"])) {
         if (isset($_SESSION['glpisearch'][$itemtype])) {
            unset($_SESSION['glpisearch'][$itemtype]);
         }
         if (isset($_SESSION['glpisearchcount'][$itemtype])) {
            unset($_SESSION['glpisearchcount'][$itemtype]);
         }
         if (isset($_SESSION['glpisearchcount2'][$itemtype])) {
            unset($_SESSION['glpisearchcount2'][$itemtype]);
         }

         // Bookmark use
         if (isset($_GET["glpisearchcount"])) {
            $_SESSION["glpisearchcount"][$itemtype] = $_GET["glpisearchcount"];
         } else if (isset($_GET["field"])) {
            $_SESSION["glpisearchcount"][$itemtype] = count($_GET["field"]);
         }

         // Bookmark use
         if (isset($_GET["glpisearchcount2"])) {
            $_SESSION["glpisearchcount2"][$itemtype] = $_GET["glpisearchcount2"];
         } else if (isset($_GET["field2"])) {
            $_SESSION["glpisearchcount2"][$itemtype] = count($_GET["field2"]);
         }
      }

      if (is_array($_GET)
          && $usesession) {
         foreach ($_GET as $key => $val) {
            $_SESSION['glpisearch'][$itemtype][$key] = $val;
         }
      }

      foreach ($default_values as $key => $val) {
         if (!isset($_GET[$key])) {
            if ($usesession
                && isset($_SESSION['glpisearch'][$itemtype][$key])) {
               $_GET[$key] = $_SESSION['glpisearch'][$itemtype][$key];
            } else {
               $_GET[$key]                              = $val;
               $_SESSION['glpisearch'][$itemtype][$key] = $val;
            }
         }
      }

      if (!isset($_SESSION["glpisearchcount"][$itemtype])) {
         if (isset($_GET["glpisearchcount"])) {
            $_SESSION["glpisearchcount"][$itemtype] = $_GET["glpisearchcount"];
         } else {
            $_SESSION["glpisearchcount"][$itemtype] = 1;
         }
      }
      if (!isset($_SESSION["glpisearchcount2"][$itemtype])) {
         // Set in URL for bookmark
         if (isset($_GET["glpisearchcount2"])) {
            $_SESSION["glpisearchcount2"][$itemtype] = $_GET["glpisearchcount2"];
         } else {
            $_SESSION["glpisearchcount2"][$itemtype] = 0;
         }
      }
//       Html::printCleanArray($_GET);
   }


   /**
    * Clean search options depending of user active profile
    *
    * @param $itemtype              item type to manage
    * @param $action                action which is used to manupulate searchoption (r/w)
    *                               (default 'r')
    * @param $withplugins  boolean  get plugins options (true by default)
    *
    * @return clean $SEARCH_OPTION array
   **/
   static function getCleanedOptions($itemtype, $action='r', $withplugins=true) {
      global $CFG_GLPI;

      $options = &self::getOptions($itemtype, $withplugins);
      $todel   = array();

      if (!Session::haveRight('infocom',$action)
          && in_array($itemtype,$CFG_GLPI["infocom_types"])) {
         $itemstodel = Infocom::getSearchOptionsToAdd($itemtype);
         $todel      = array_merge($todel, array_keys($itemstodel));
      }

      if (!Session::haveRight('contract',$action)
          && in_array($itemtype, $CFG_GLPI["contract_types"])) {
         $itemstodel = Contract::getSearchOptionsToAdd();
         $todel      = array_merge($todel, array_keys($itemstodel));
      }

      if (!Session::haveRight('document',$action)
          && in_array($itemtype, $CFG_GLPI["document_types"])) {
         $itemstodel = Document::getSearchOptionsToAdd();
         $todel      = array_merge($todel, array_keys($itemstodel));
      }

      // do not show priority if you don't have right in profile
      If (($itemtype == 'Ticket')
          && ($action == 'w')
          && !Session::haveRight('update_priority', 1)) {
         $todel[] = 3;
      }

      if ($itemtype == 'Computer') {
         if (!Session::haveRight('networking', $action)) {
            $itemstodel = NetworkPort::getSearchOptionsToAdd($itemtype);
            $todel      = array_merge($todel, array_keys($itemstodel));
         }
      }
      if (!Session::haveRight('notes', $action)) {
         $todel[] = 90;
      }

      if (count($todel)) {
         foreach ($todel as $ID) {
            if (isset($options[$ID])) {
               unset($options[$ID]);
            }
         }
      }

      return $options;
   }


   /**
    *
    * Get an option number in the SEARCH_OPTION array
    *
    * @param $itemtype
    * @param $field     name
    *
    * @return integer
   **/
   static function getOptionNumber($itemtype, $field) {

      $table = getTableForItemType($itemtype);
      $opts  = &self::getOptions($itemtype);

      foreach ($opts as $num => $opt) {
         if (is_array($opt)
             && ($opt['table'] == $table)
             && ($opt['field'] == $field)) {
            return $num;
         }
      }
      return 0;
   }


   /**
    * Get the SEARCH_OPTION array
    *
    * @param $itemtype
    * @param $withplugins boolean get search options from plugins (true by default)
    *
    * @return the reference to  array of search options for the given item type
   **/
   static function &getOptions($itemtype, $withplugins=true) {
      global $CFG_GLPI;

      static $search = array();

      if (!isset($search[$itemtype])) {
         // standard type first
         switch ($itemtype) {
            case 'Internet' :
               $search[$itemtype]['common']            = __('Characteristics');

               $search[$itemtype][1]['table']          = 'networkport_types';
               $search[$itemtype][1]['field']          = 'name';
               $search[$itemtype][1]['name']           = __('Name');
               $search[$itemtype][1]['datatype']       = 'itemlink';
               $search[$itemtype][1]['searchtype']     = 'contains';

               $search[$itemtype][2]['table']          = 'networkport_types';
               $search[$itemtype][2]['field']          = 'id';
               $search[$itemtype][2]['name']           = __('ID');
               $search[$itemtype][2]['searchtype']     = 'contains';

               $search[$itemtype][31]['table']         = 'glpi_states';
               $search[$itemtype][31]['field']         = 'completename';
               $search[$itemtype][31]['name']          = __('Status');

               $search[$itemtype] += NetworkPort::getSearchOptionsToAdd('networkport_types');
               break;

            case 'AllAssets' :
               $search[$itemtype]['common']            = __('Characteristics');

               $search[$itemtype][1]['table']          = 'asset_types';
               $search[$itemtype][1]['field']          = 'name';
               $search[$itemtype][1]['name']           = __('Name');
               $search[$itemtype][1]['datatype']       = 'itemlink';
               $search[$itemtype][1]['searchtype']     = 'contains';

               $search[$itemtype][2]['table']          = 'asset_types';
               $search[$itemtype][2]['field']          = 'id';
               $search[$itemtype][2]['name']           = __('ID');
               $search[$itemtype][2]['searchtype']     = 'contains';

               $search[$itemtype][31]['table']         = 'glpi_states';
               $search[$itemtype][31]['field']         = 'completename';
               $search[$itemtype][31]['name']          = __('Status');

               $search[$itemtype] += Location::getSearchOptionsToAdd();

               $search[$itemtype][5]['table']          = 'asset_types';
               $search[$itemtype][5]['field']          = 'serial';
               $search[$itemtype][5]['name']           = __('Serial number');

               $search[$itemtype][6]['table']          = 'asset_types';
               $search[$itemtype][6]['field']          = 'otherserial';
               $search[$itemtype][6]['name']           = __('Inventory number');

               $search[$itemtype][16]['table']         = 'asset_types';
               $search[$itemtype][16]['field']         = 'comment';
               $search[$itemtype][16]['name']          = __('Comments');
               $search[$itemtype][16]['datatype']      = 'text';

               $search[$itemtype][70]['table']         = 'glpi_users';
               $search[$itemtype][70]['field']         = 'name';
               $search[$itemtype][70]['name']          = __('User');

               $search[$itemtype][7]['table']         = 'asset_types';
               $search[$itemtype][7]['field']         = 'contact';
               $search[$itemtype][7]['name']          = __('Alternate username');
               $search[$itemtype][7]['datatype']      = 'string';

               $search[$itemtype][8]['table']         = 'asset_types';
               $search[$itemtype][8]['field']         = 'contact_num';
               $search[$itemtype][8]['name']          = __('Alternate username number');
               $search[$itemtype][8]['datatype']      = 'string';

               $search[$itemtype][71]['table']         = 'glpi_groups';
               $search[$itemtype][71]['field']         = 'completename';
               $search[$itemtype][71]['name']          = __('Group');

               $search[$itemtype][19]['table']         = 'asset_types';
               $search[$itemtype][19]['field']         = 'date_mod';
               $search[$itemtype][19]['name']          = __('Last update');
               $search[$itemtype][19]['datatype']      = 'datetime';
               $search[$itemtype][19]['massiveaction'] = false;

               $search[$itemtype][23]['table']         = 'glpi_manufacturers';
               $search[$itemtype][23]['field']         = 'name';
               $search[$itemtype][23]['name']          = __('Manufacturer');

               $search[$itemtype][24]['table']         = 'glpi_users';
               $search[$itemtype][24]['field']         = 'name';
               $search[$itemtype][24]['linkfield']     = 'users_id_tech';
               $search[$itemtype][24]['name']          = __('Technician in charge of the hardware');

               $search[$itemtype][80]['table']         = 'glpi_entities';
               $search[$itemtype][80]['field']         = 'completename';
               $search[$itemtype][80]['name']          = __('Entity');
               break;

            default :
               if ($item = getItemForItemtype($itemtype)) {
                  $search[$itemtype] = $item->getSearchOptions();
               }
               break;
         }

         if (Session::getLoginUserID()
             && in_array($itemtype, $CFG_GLPI["ticket_types"])) {
            $search[$itemtype]['tracking']          = __('Assistance');

            $search[$itemtype][60]['table']         = 'glpi_tickets';
            $search[$itemtype][60]['linkfield']     = 'items_id';
            $search[$itemtype][60]['field']         = 'count';
            $search[$itemtype][60]['name']          = _x('quantity', 'Number of tickets');
            $search[$itemtype][60]['forcegroupby']  = true;
            $search[$itemtype][60]['usehaving']     = true;
            $search[$itemtype][60]['datatype']      = 'number';
            $search[$itemtype][60]['massiveaction'] = false;
            $search[$itemtype][60]['joinparams']    = array('jointype'
                                                             => "itemtype_item",
                                                            'condition'
                                                             => getEntitiesRestrictRequest('AND',
                                                                                           'NEWTABLE'));
            $search[$itemtype][140]['table']         = 'glpi_problems';
            $search[$itemtype][140]['field']         = 'count';
            $search[$itemtype][140]['name']          = __('Number of problems');
            $search[$itemtype][140]['forcegroupby']  = true;
            $search[$itemtype][140]['usehaving']     = true;
            $search[$itemtype][140]['datatype']      = 'number';
            $search[$itemtype][140]['massiveaction'] = false;
            $search[$itemtype][140]['joinparams']    = array('beforejoin'
                                                              => array('table'
                                                                        => 'glpi_items_problems',
                                                                       'joinparams'
                                                                        => array('jointype'
                                                                                  => 'itemtype_item')),
                                                             'condition'
                                                              => getEntitiesRestrictRequest('AND',
                                                                                            'NEWTABLE'));
         }

         if (in_array($itemtype, $CFG_GLPI["networkport_types"])
             || ($itemtype == 'AllAssets')) {
            $search[$itemtype] += NetworkPort::getSearchOptionsToAdd($itemtype);
         }

         if (in_array($itemtype, $CFG_GLPI["contract_types"])
             || ($itemtype == 'AllAssets')) {
            $search[$itemtype] += Contract::getSearchOptionsToAdd();
         }

         if (in_array($itemtype, $CFG_GLPI["document_types"])
             || ($itemtype == 'AllAssets')) {
            $search[$itemtype] += Document::getSearchOptionsToAdd();
         }

         if (in_array($itemtype, $CFG_GLPI["infocom_types"])
             || ($itemtype == 'AllAssets')) {
            $search[$itemtype] += Infocom::getSearchOptionsToAdd($itemtype);
         }

         if ($withplugins) {
            // Search options added by plugins
            $plugsearch = Plugin::getAddSearchOptions($itemtype);
            if (count($plugsearch)) {
               $search[$itemtype] += array('plugins' => __('Plugins'));
               $search[$itemtype] += $plugsearch;
            }
         }
         // Complete linkfield if not define
         if (!isset($item)) { // Special union type
            $itemtable = $CFG_GLPI['union_search_type'][$itemtype];
         } else {
            $itemtable = $item->getTable();
         }

         foreach ($search[$itemtype] as $key => $val) {
            if (!is_array($val)) {
               // skip sub-menu
               continue;
            }
            // Compatibility before 0.80 : Force massive action to false if linkfield is empty :
            if (isset($val['linkfield']) && empty($val['linkfield'])) {
               $search[$itemtype][$key]['massiveaction'] = false;
            }

            // Set default linkfield
            if (!isset($val['linkfield']) || empty($val['linkfield'])) {
               if ((strcmp($itemtable,$val['table']) == 0)
                   && (!isset($val['joinparams']) || (count($val['joinparams']) == 0))) {
                  $search[$itemtype][$key]['linkfield'] = $val['field'];
               } else {
                  $search[$itemtype][$key]['linkfield'] = getForeignKeyFieldForTable($val['table']);
               }
            }
            // Set default datatype
//             if (!isset($val['datatype']) || empty($val['datatype'])) {
//                if ((strcmp($itemtable,$val['table']) != 0)
//                    && ($val['field'] == 'name' || $val['field'] == 'completename')) {
//                   $search[$itemtype][$key]['datatype'] = 'dropdown';
//                } else {
//                   $search[$itemtype][$key]['datatype'] = 'string';
//                }
//             }
            // Add default joinparams
            if (!isset($val['joinparams'])) {
               $search[$itemtype][$key]['joinparams'] = array();
            }
         }

      }

      return $search[$itemtype];
   }


   /**
    * Convert an array to be add in url
    *
    * @param $name                  name of array
    * @param $array  string/array   to be added
    *
    * @return string to add
   **/
   static function getArrayUrlLink($name, $array) {

      $out = "";
      if (is_array($array) && count($array)>0) {
         foreach ($array as $key => $val) {
            $out .= "&amp;".$name."[$key]=".urlencode(stripslashes($val));
         }
      }
      return $out;
   }


   /**
    * Is the search item related to infocoms
    *
    * @param $itemtype  item type
    * @param $searchID  ID of the element in $SEARCHOPTION
    *
    * @return boolean
   **/
   static function isInfocomOption($itemtype, $searchID) {
      global $CFG_GLPI;

      return (((($searchID >= 25) && ($searchID <= 28))
               || (($searchID >= 37) && ($searchID <= 38))
               || (($searchID >= 50) && ($searchID <= 59))
               || (($searchID >= 120) && ($searchID <= 125)))
              && in_array($itemtype, $CFG_GLPI["infocom_types"]));
   }


   /**
    * @param $itemtype
    * @param $field_num
   **/
   static function getActionsFor($itemtype, $field_num) {

      $searchopt = &self::getOptions($itemtype);
      $actions   = array('contains'  => __('contains'),
                         'searchopt' => array());

      if (isset($searchopt[$field_num])) {
         $actions['searchopt'] = $searchopt[$field_num];

         // Force search type
         if (isset($actions['searchopt']['searchtype'])) {
            // Reset search option
            $actions              = array();
            $actions['searchopt'] = $searchopt[$field_num];
            if (!is_array($actions['searchopt']['searchtype'])) {
               $actions['searchopt']['searchtype'] = array($actions['searchopt']['searchtype']);
            }
            foreach ($actions['searchopt']['searchtype'] as $searchtype) {
               switch ($searchtype) {
                  case "equals" :
                     $actions['equals'] = __('is');
                     break;

                  case "notequals" :
                     $actions['notequals'] = __('is not');
                     break;

                  case "contains" :
                     $actions['contains'] = __('contains');
                     break;

                  case "under" :
                     $actions['under'] = __('under');
                     break;

                  case "notunder" :
                     $actions['notunder'] = __('not under');
                     break;

                  case "lessthan" :
                     $actions['lessthan'] = __('before');
                     break;

                  case "morethan" :
                     $actions['morethan'] = __('after');
                     break;
               }
            }
            return $actions;
         }
         if (isset($searchopt[$field_num]['datatype'])) {
            switch ($searchopt[$field_num]['datatype']) {
               case 'number' :
                  $opt = array('contains'  => __('contains'),
                               'equals'    => __('is'),
                               'notequals' => __('is not'),
                               'searchopt' => $searchopt[$field_num]);
                  // No is / isnot if no limits defined
                  if (!isset($searchopt[$field_num]['min'])
                      && !isset($searchopt[$field_num]['max'])) {
                     unset($opt['equals']);
                     unset($opt['notequals']);
                  }
                  return $opt;

               case 'bool' :
                  return array('equals'    => __('is'),
                               'notequals' => __('is not'),
                               'contains'  => __('contains'),
                               'searchopt' => $searchopt[$field_num]);

               case 'right' :
                  return array('equals'    => __('is'),
                               'notequals' => __('is not'),
                               'searchopt' => $searchopt[$field_num]);

               case 'itemtypename' :
                  return array('equals'    => __('is'),
                               'notequals' => __('is not'),
                               'searchopt' => $searchopt[$field_num]);

               case 'date' :
               case 'datetime' :
               case 'date_delay' :
                  return array('equals'    => __('is'),
                               'notequals' => __('is not'),
                               'lessthan'  => __('before'),
                               'morethan'  => __('after'),
                               'contains'  => __('contains'),
                               'searchopt' => $searchopt[$field_num]);
            }
         }

//          switch ($searchopt[$field_num]['table']) {
//             case 'glpi_users_validation' :
//                return array('equals'    => __('is'),
//                             'notequals' => __('is not'),
//                             'searchopt' => $searchopt[$field_num]);
//          }

         switch ($searchopt[$field_num]['field']) {
            case 'id' :
               return array('equals'    => __('is'),
                            'notequals' => __('is not'),
                            'searchopt' => $searchopt[$field_num]);

            case 'name' :
            case 'completename' :
               $actions = array('contains'  => __('contains'),
                                'equals'    => __('is'),
                                'notequals' => __('is not'),
                                'searchopt' => $searchopt[$field_num]);

               // Specific case of TreeDropdown : add under
               $itemtype_linked = getItemTypeForTable($searchopt[$field_num]['table']);
               if ($itemlinked = getItemForItemtype($itemtype_linked)) {
                  if ($itemlinked instanceof CommonTreeDropdown) {
                     $actions['under']    = __('under');
                     $actions['notunder'] = __('not under');
                  }
                  return $actions;
               }
         }
      }
      return $actions;
   }


   /**
    * Print generic Header Column
    *
    * @param $type            display type (0=HTML, 1=Sylk,2=PDF,3=CSV)
    * @param $value           value to display
    * @param &$num            column number
    * @param $linkto          link display element (HTML specific) (default '')
    * @param $issort          is the sort column ? (default 0)
    * @param $order           order type ASC or DESC (defaut '')
    * @param $options  string options to add (default '')
    *
    * @return string to display
   **/
   static function showHeaderItem($type, $value, &$num, $linkto="", $issort=0, $order="",
                                  $options="") {
      global $CFG_GLPI;

      $out = "";
      switch ($type) {
         case self::PDF_OUTPUT_LANDSCAPE : //pdf

         case self::PDF_OUTPUT_PORTRAIT :
            global $PDF_HEADER;
            $PDF_HEADER[$num] = Toolbox::decodeFromUtf8(Html::clean($value), 'windows-1252');
            break;

         case self::SYLK_OUTPUT : //sylk
            global $SYLK_HEADER,$SYLK_SIZE;
            $SYLK_HEADER[$num] = self::sylk_clean($value);
            $SYLK_SIZE[$num]   = Toolbox::strlen($SYLK_HEADER[$num]);
            break;

         case self::CSV_OUTPUT : //CSV
            $out = "\"".self::csv_clean($value)."\"".$_SESSION["glpicsv_delimiter"];
            break;

         default :
            $out = "<th $options>";
            if ($issort) {
               if ($order=="DESC") {
                  $out .= "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
               } else {
                  $out .= "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
               }
            }
            if (!empty($linkto)) {
               $out .= "<a href=\"$linkto\">";
            }
            $out .= $value;
            if (!empty($linkto)) {
               $out .= "</a>";
            }
            $out .= "</th>\n";
      }
      $num++;
      return $out;
   }


   /**
    * Print generic normal Item Cell
    *
    * @param $type         display type (0=HTML, 1=Sylk,2=PDF,3=CSV)
    * @param $value        value to display
    * @param &$num         column number
    * @param $row          row number
    * @param $extraparam   extra parameters for display (default '')
    *
    *@return string to display
   **/
   static function showItem($type, $value, &$num, $row, $extraparam='') {

      $out = "";
      switch ($type) {
         case self::PDF_OUTPUT_LANDSCAPE : //pdf
         case self::PDF_OUTPUT_PORTRAIT :
            global $PDF_ARRAY,$PDF_HEADER;
            $value                 = Html::weblink_extract($value);
            $PDF_ARRAY[$row][$num] = Toolbox::decodeFromUtf8(Html::clean($value), 'windows-1252');
            break;

         case self::SYLK_OUTPUT : //sylk
            global $SYLK_ARRAY,$SYLK_HEADER,$SYLK_SIZE;
            $value                  = Html::weblink_extract($value);
            $SYLK_ARRAY[$row][$num] = self::sylk_clean($value);
            $SYLK_SIZE[$num]        = max($SYLK_SIZE[$num],
                                          Toolbox::strlen($SYLK_ARRAY[$row][$num]));
            break;

         case self::CSV_OUTPUT : //csv
            $value = Html::weblink_extract($value);
            $out   = "\"".self::csv_clean($value)."\"".$_SESSION["glpicsv_delimiter"];
            break;

         default :
            //TODO supprimer valign pour mettre class mais conflit avec $extraparam
            $out = "<td $extraparam valign='top'>";

/*            if (!preg_match('/<hr>/',$value)) {
               $values = preg_split("/<br>/i",$value);
               $line_delimiter = '<br>';
            } else {
               $values = preg_split("/<hr>/i",$value);
               $line_delimiter = '<hr>';
            }

            $limitto = 20;
            if (count($values) > $limitto) {
               for ( $i=0 ; $i<$limitto ; $i++) {
                  $out .= $values[$i].$line_delimiter;
               }
               $rand=mt_rand();
               $out .= "...&nbsp;";
               $out .= Html::showToolTip($value,array('display'   => false,
                                                      'autoclose' => false));

            } else {*/
            $out .= $value;
//             }
            $out .= "</td>\n";
      }
      $num++;
      return $out;
   }


   /**
    * Print generic error
    *
    * @param $type display type (0=HTML, 1=Sylk,2=PDF,3=CSV)
    *
    * @return string to display
   **/
   static function showError($type) {

      $out = "";
      switch ($type) {
         case self::PDF_OUTPUT_LANDSCAPE : //pdf
         case self::PDF_OUTPUT_PORTRAIT :
         case self::SYLK_OUTPUT : //sylk
         case self::CSV_OUTPUT : //csv
            break;

         default :
            $out = "<div class='center b'>".__('No item found')."</div>\n";
      }
      return $out;
   }


   /**
    * Print generic footer
    *
    * @param $type   display type (0=HTML, 1=Sylk,2=PDF,3=CSV)
    * @param $title  title of file : used for PDF (default '')
    *
    * @return string to display
   **/
   static function showFooter($type, $title="") {

      $out = "";
      switch ($type) {
         case self::PDF_OUTPUT_LANDSCAPE : //pdf
            global $PDF_HEADER,$PDF_ARRAY;
            $pdf     = new Cezpdf('a4','landscape');
            $pdf->selectFont(GLPI_ROOT."/lib/ezpdf/fonts/Helvetica.afm");
            $nb      = count($PDF_ARRAY);

            $tmptxt  = sprintf(_n('%s item', '%s items', $nb), $nb);
            $pdf->ezStartPageNumbers(750, 10, 10, 'left',
                                     "GLPI PDF export - ".Html::convDate(date("Y-m-d")).
                                       " - ".Toolbox::decodeFromUtf8($tmptxt, 'windows-1252').
                                       " - {PAGENUM}/{TOTALPAGENUM}");
            $options = array('fontSize'      => 8,
                             'colGap'        => 2,
                             'maxWidth'      => 800,
                             'titleFontSize' => 8);
            $pdf->ezTable($PDF_ARRAY, $PDF_HEADER, Toolbox::decodeFromUtf8($title, 'windows-1252'),
                          $options);
            $pdf->ezStream();
            break;

         case self::PDF_OUTPUT_PORTRAIT : //pdf
            global $PDF_HEADER,$PDF_ARRAY;
            $pdf     = new Cezpdf('a4','portrait');
            $pdf->selectFont(GLPI_ROOT."/lib/ezpdf/fonts/Helvetica.afm");
            $nb      = count($PDF_ARRAY);

            $tmptxt  = sprintf(_n('%s item', '%s items', $nb), $nb);
            $pdf->ezStartPageNumbers(550, 10, 10, 'left',
                                     "GLPI PDF export - ".Html::convDate(date("Y-m-d")).
                                       " - ".Toolbox::decodeFromUtf8($tmptxt, 'windows-1252').
                                       " - {PAGENUM}/{TOTALPAGENUM}");
            $options = array('fontSize'      => 8,
                             'colGap'        => 2,
                             'maxWidth'      => 565,
                             'titleFontSize' => 8);
            $pdf->ezTable($PDF_ARRAY, $PDF_HEADER, Toolbox::decodeFromUtf8($title, 'windows-1252'),
                          $options);
            $pdf->ezStream();
            break;

         case self::SYLK_OUTPUT : //sylk
            global $SYLK_HEADER,$SYLK_ARRAY,$SYLK_SIZE;
            // largeurs des colonnes
            foreach ($SYLK_SIZE as $num => $val) {
               $out .= "F;W".$num." ".$num." ".min(50,$val)."\n";
            }
            $out .= "\n";
            // Header
            foreach ($SYLK_HEADER as $num => $val) {
               $out .= "F;SDM4;FG0C;".($num == 1 ? "Y1;" : "")."X$num\n";
               $out .= "C;N;K\"".self::sylk_clean($val)."\"\n";
               $out .= "\n";
            }
            // Datas
            foreach ($SYLK_ARRAY as $row => $tab) {
               foreach ($tab as $num => $val) {
                  $out .= "F;P3;FG0L;".($num == 1 ? "Y".$row.";" : "")."X$num\n";
                  $out .= "C;N;K\"".self::sylk_clean($val)."\"\n";
               }
            }
            $out.= "E\n";
            break;

         case self::CSV_OUTPUT : //csv
            break;

         default :
            $out = "</table></div>\n";
      }
      return $out;
   }


   /**
    * Print generic footer
    *
    * @param $type   display type (0=HTML, 1=Sylk,2=PDF,3=CSV)
    * @param $rows   number of rows
    * @param $cols   number of columns
    * @param $fixed  used tab_cadre_fixe table for HTML export ? (default 0)
    *
    * @return string to display
   **/
   static function showHeader($type, $rows, $cols, $fixed=0) {

      $out = "";
      switch ($type) {
         case self::PDF_OUTPUT_LANDSCAPE : //pdf
         case self::PDF_OUTPUT_PORTRAIT :
            global $PDF_ARRAY, $PDF_HEADER;
            $PDF_ARRAY  = array();
            $PDF_HEADER = array();
            break;

         case self::SYLK_OUTPUT : // Sylk
            global $SYLK_ARRAY, $SYLK_HEADER, $SYLK_SIZE;
            $SYLK_ARRAY  = array();
            $SYLK_HEADER = array();
            $SYLK_SIZE   = array();
            // entetes HTTP
            header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
            header('Pragma: private'); /// IE BUG + SSL
            header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
            header("Content-disposition: filename=glpi.slk");
            header('Content-type: application/octetstream');
            // entete du fichier
            echo "ID;PGLPI_EXPORT\n"; // ID;Pappli
            echo "\n";
            // formats
            echo "P;PGeneral\n";
            echo "P;P#,##0.00\n";       // P;Pformat_1 (reels)
            echo "P;P#,##0\n";          // P;Pformat_2 (entiers)
            echo "P;P@\n";              // P;Pformat_3 (textes)
            echo "\n";
            // polices
            echo "P;EArial;M200\n";
            echo "P;EArial;M200\n";
            echo "P;EArial;M200\n";
            echo "P;FArial;M200;SB\n";
            echo "\n";
            // nb lignes * nb colonnes
            echo "B;Y".$rows;
            echo ";X".$cols."\n"; // B;Yligmax;Xcolmax
            echo "\n";
            break;

         case self::CSV_OUTPUT : // csv
            header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
            header('Pragma: private'); /// IE BUG + SSL
            header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
            header("Content-disposition: filename=glpi.csv");
            header('Content-type: application/octetstream');
            break;

         default :
            if ($fixed) {
               $out = "<div class='center'><table border='0' class='tab_cadre_fixehov'>\n";
            } else {
               $out = "<div class='center'><table border='0' class='tab_cadrehov'>\n";
            }
      }
      return $out;
   }


   /**
    * Print generic new line
    *
    * @param $type         display type (0=HTML, 1=Sylk,2=PDF,3=CSV)
    * @param $odd          is it a new odd line ? (false by default)
    * @param $is_deleted   is it a deleted search ? (false by default)
    *
    * @return string to display
   **/
   static function showNewLine($type, $odd=false, $is_deleted=false) {

      $out = "";
      switch ($type) {
         case self::PDF_OUTPUT_LANDSCAPE : //pdf
         case self::PDF_OUTPUT_PORTRAIT :
         case self::SYLK_OUTPUT : //sylk
         case self::CSV_OUTPUT : //csv
            break;

         default :
            $class = " class='tab_bg_2".($is_deleted?'_2':'')."' ";
            if ($odd) {
               $class = " class='tab_bg_1".($is_deleted?'_2':'')."' ";
            }
            $out = "<tr $class>";
      }
      return $out;
   }


   /**
    * Print generic end line
    *
    * @param $type display type (0=HTML, 1=Sylk,2=PDF,3=CSV)
    *
    * @return string to display
   **/
   static function showEndLine($type) {

      $out = "";
      switch ($type) {
         case self::PDF_OUTPUT_LANDSCAPE : //pdf
         case self::PDF_OUTPUT_PORTRAIT :
         case self::SYLK_OUTPUT : //sylk
            break;

         case self::CSV_OUTPUT : //csv
            $out = "\n";
            break;

         default :
            $out = "</tr>";
      }
      return $out;
   }


   /**
    * @param $joinparams   array
    */
   static function computeComplexJoinID(array $joinparams) {

      $complexjoin = '';
//      Html::printCleanArray($joinparams);
      if (isset($joinparams['condition'])) {
         $complexjoin .= $joinparams['condition'];
      }

      // For jointype == child
      if (isset($joinparams['jointype']) && ($joinparams['jointype'] == 'child')
          && isset($joinparams['linkfield'])) {
         $complexjoin .= $joinparams['linkfield'];
      }

      if (isset($joinparams['beforejoin'])) {
         if (isset($joinparams['beforejoin']['table'])) {
            $joinparams['beforejoin'] = array($joinparams['beforejoin']);
         }
         foreach ($joinparams['beforejoin'] as $tab) {
            if (isset($tab['table'])) {
               $complexjoin .= $tab['table'];
            }
            if (isset($tab['joinparams']) && isset($tab['joinparams']['condition'])) {
               $complexjoin .= $tab['joinparams']['condition'];
            }
         }
      }

      if (!empty($complexjoin)) {
         $complexjoin = md5($complexjoin);
      }
      return $complexjoin;
   }


   /**
    * Clean display value for csv export
    *
    * @param $value string value
    *
    * @return clean value
   **/
   static function csv_clean($value) {

      if (Toolbox::get_magic_quotes_runtime()) {
         $value = stripslashes($value);
      }

      $value = str_replace("\"", "''", $value);
      $value = Html::clean($value);

      return $value;
   }


   /**
    * Clean display value for sylk export
    *
    * @param $value string value
    *
    * @return clean value
   **/
   static function sylk_clean($value) {

      if (Toolbox::get_magic_quotes_runtime()) {
         $value = stripslashes($value);
      }

      $value = preg_replace('/\x0A/', ' ', $value);
      $value = preg_replace('/\x0D/', NULL, $value);
      $value = str_replace("\"", "''", $value);
      $value = str_replace(';', ';;', $value);
      $value = Html::clean($value);

      return $value;
   }


   /**
    * Create SQL search condition
    *
    * @param $field           name (should be ` protected)
    * @param $val    string   value to search
    * @param $not    boolean  is a negative search ? (false by default)
    * @param $link            with previous criteria (default 'AND')
    *
    * @return search SQL string
   **/
   static function makeTextCriteria ($field, $val, $not=false, $link='AND') {

      $sql = $field . self::makeTextSearch($val, $not);

      if (($not && ($val != 'NULL') && ($val != 'null') && ($val != '^$'))    // Not something
          ||(!$not && ($val == '^$'))) {   // Empty
         $sql = "($sql OR $field IS NULL)";
      }
      return " $link $sql ";
   }


   /**
    * Create SQL search condition
    *
    * @param $val string   value to search
    * @param $not boolean  is a negative search ? (false by default)
    *
    * @return search string
   **/
   static function makeTextSearch($val, $not=false) {

      $NOT = "";
      if ($not) {
         $NOT = "NOT";
      }

      // Unclean to permit < and > search
      $val = Toolbox::unclean_cross_side_scripting_deep($val);

      if (($val == 'NULL') || ($val == 'null')) {
         $SEARCH = " IS $NOT NULL ";

      } else {
         $begin = 0;
         $end   = 0;
         if (($length = strlen($val)) > 0) {
            if (($val[0] == '^')) {
               $begin = 1;
            }

            if ($val[$length-1] == '$') {
               $end = 1;
            }
         }

         if ($begin || $end) {
            // no Toolbox::substr, to be consistent with strlen result
            $val = substr($val, $begin, $length-$end-$begin);
         }

         $SEARCH = " $NOT LIKE '".(!$begin?"%":"").$val.(!$end?"%":"")."' ";
      }
      return $SEARCH;
   }


   /**
    * @since version 0.84
    *
    * @param $pattern
    * @param $subject
   **/
   static function explodeWithID($pattern, $subject) {

      $tab = explode($pattern, $subject);

      if (isset($tab[1]) && !is_numeric($tab[1])) {
         // Report $ to tab[0]
         if (preg_match('/^(\\$*)(.*)/',$tab[1],$matchs)) {
            if (isset($matchs[2]) && is_numeric($matchs[2])) {
               $tab[1]  = $matchs[2];
               $tab[0] .= $matchs[1];
            }
         }
      }

      return $tab;
   }

}
?>
