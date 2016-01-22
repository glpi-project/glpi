<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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
* @since version 0.85
*/

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"getDropdownValue.php")) {
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

Session::checkLoginUser();

if (isset($_GET["entity_restrict"])
    && !is_array($_GET["entity_restrict"])
    && (substr($_GET["entity_restrict"], 0, 1) === '[')
    && (substr($_GET["entity_restrict"], -1) === ']')) {
   $_GET["entity_restrict"] = json_decode($_GET["entity_restrict"]);
}

// Security
if (!($item = getItemForItemtype($_GET['itemtype']))) {
   exit();
}
$table = $item->getTable();
$datas = array();

$displaywith = false;
if (isset($_GET['displaywith'])) {
   if (is_array($_GET['displaywith']) && count($_GET['displaywith'])) {
      $displaywith = true;
   }
}

if (!isset($_GET['permit_select_parent'])) {
   $_GET['permit_select_parent'] = false;
}

if (isset($_GET['condition']) && !empty($_GET['condition'])) {
    if (isset($_SESSION['glpicondition'][$_GET['condition']])) {
        $_GET['condition'] = $_SESSION['glpicondition'][$_GET['condition']];
    } else {
        $_GET['condition'] = '';
    }
}

if (!isset($_GET['emptylabel']) || ($_GET['emptylabel'] == '')) {
   $_GET['emptylabel'] = Dropdown::EMPTY_VALUE;
}

$where = "WHERE 1 ";

if ($item->maybeDeleted()) {
   $where .= " AND `is_deleted` = '0' ";
}
if ($item->maybeTemplate()) {
   $where .= " AND `is_template` = '0' ";
}

if (!isset($_GET['page'])) {
   $_GET['page']       = 1;
   $_GET['page_limit'] = $CFG_GLPI['dropdown_max'];
}

$start = ($_GET['page']-1)*$_GET['page_limit'];
$limit = $_GET['page_limit'];
// Get last item retrieve to init values
if ($_GET['page'] > 1) {
   $start--;
   $limit++;
}
$LIMIT = "LIMIT $start,$limit";

if (isset($_GET['used'])) {
   $used = $_GET['used'];

   if (count($used)) {
      $where .=" AND `$table`.`id` NOT IN ('".implode("','",$used)."' ) ";
   }
}

if (isset($_GET['toadd'])) {
   $toadd = $_GET['toadd'];
} else {
   $toadd = array();
}

// $where .= ") ";

if (isset($_GET['condition']) && ($_GET['condition'] != '')) {
   $where .= " AND ".$_GET['condition']." ";
}

$one_item = -1;
if (isset($_GET['_one_id'])) {
   $one_item = $_GET['_one_id'];
}

// Count real items returned
$count = 0;


if ($item instanceof CommonTreeDropdown) {

   if ($one_item >= 0) {
      $where .= " AND `$table`.`id` = '$one_item'";
   } else {
      if (!empty($_GET['searchText'])) {
         $search = Search::makeTextSearch($_GET['searchText']);
         if (Session::haveTranslations($_GET['itemtype'], 'completename')) {
            $where .= " AND (`$table`.`completename` $search ".
                             "OR `namet`.`value` $search " ;
         } else {
            $where .= " AND (`$table`.`completename` $search ";
         }
         // Also search by id
         if ($displaywith && in_array('id', $_GET['displaywith'])) {
            $where .= " OR `$table`.`id` ".$search;
         }

         $where .= ")";
      }
   }

   $multi = false;

   // Manage multiple Entities dropdowns
   $add_order = "";

   // No multi if get one item
   if ($item->isEntityAssign()
       && ($one_item < 0)) {
      $recur = $item->maybeRecursive();

       // Entities are not really recursive : do not display parents
      if ($_GET['itemtype'] == 'Entity') {
         $recur = false;
      }

      if (isset($_GET["entity_restrict"]) && !($_GET["entity_restrict"] < 0)) {
         $where .= getEntitiesRestrictRequest(" AND ", $table, '', $_GET["entity_restrict"],
                                              $recur);

         if (is_array($_GET["entity_restrict"]) && (count($_GET["entity_restrict"]) > 1)) {
            $multi = true;
         }

      } else {
         // If private item do not use entity
         if (!$item->maybePrivate()) {
            $where .= getEntitiesRestrictRequest(" AND ", $table, '', '', $recur);

            if (count($_SESSION['glpiactiveentities']) > 1) {
               $multi = true;
            }
         } else {
            $multi = false;
         }
      }

      // Force recursive items to multi entity view
      if ($recur) {
         $multi = true;
      }

      // no multi view for entitites
      if ($_GET['itemtype'] == "Entity") {
         $multi = false;
      }

      if ($multi) {
         $add_order = "`$table`.`entities_id`, ";
      }
   }

   $addselect = '';
   $addjoin = '';
   if (Session::haveTranslations($_GET['itemtype'], 'completename')) {
      $addselect = ", `namet`.`value` AS transcompletename";
      $addjoin   = " LEFT JOIN `glpi_dropdowntranslations` AS namet
                        ON (`namet`.`itemtype` = '".$_GET['itemtype']."'
                           AND `namet`.`items_id` = `$table`.`id`
                           AND `namet`.`language` = '".$_SESSION['glpilanguage']."'
                           AND `namet`.`field` = 'completename')";
   }
   if (Session::haveTranslations($_GET['itemtype'], 'name')) {
      $addselect .= ", `namet2`.`value` AS transname";
      $addjoin   .= " LEFT JOIN `glpi_dropdowntranslations` AS namet2
                        ON (`namet2`.`itemtype` = '".$_GET['itemtype']."'
                           AND `namet2`.`items_id` = `$table`.`id`
                           AND `namet2`.`language` = '".$_SESSION['glpilanguage']."'
                           AND `namet2`.`field` = 'name')";
   }
   if (Session::haveTranslations($_GET['itemtype'], 'comment')) {
      $addselect .= ", `commentt`.`value` AS transcomment";
      $addjoin   .= " LEFT JOIN `glpi_dropdowntranslations` AS commentt
                        ON (`commentt`.`itemtype` = '".$_GET['itemtype']."'
                           AND `commentt`.`items_id` = `$table`.`id`
                           AND `commentt`.`language` = '".$_SESSION['glpilanguage']."'
                           AND `commentt`.`field` = 'comment')";
   }

   $query = "SELECT `$table`.* $addselect
             FROM `$table`
             $addjoin
             $where
             ORDER BY $add_order `$table`.`completename`
             $LIMIT";

   if ($result = $DB->query($query)) {
      // Empty search text : display first
      if ($_GET['page'] == 1 && empty($_GET['searchText'])) {
         if ($_GET['display_emptychoice']) {
            if (($one_item < 0) || ($one_item  == 0)) {
               array_push($datas, array('id'   => 0,
                                        'text' => $_GET['emptylabel']));
            }
         }
      }

      if ($_GET['page'] == 1) {
         if (count($toadd)) {
            foreach ($toadd as $key => $val) {
               if (($one_item < 0) || ($one_item == $key)) {
                  array_push($datas, array('id'   => $key,
                                           'text' => stripslashes($val)));
               }
            }
         }
      }
      $last_level_displayed = array();
      $datastoadd           = array();

      // Ignore first item for all pages except first page or one_item
      $firstitem = (($_GET['page'] > 1) && ($one_item < 0));
      if ($DB->numrows($result)) {
         $prev             = -1;
         $firstitem_entity = -1;

         while ($data = $DB->fetch_assoc($result)) {
            $ID    = $data['id'];
            $level = $data['level'];

            if (isset($data['transname']) && !empty($data['transname'])) {
               $outputval = $data['transname'];
            } else {
               $outputval = $data['name'];
            }

            if ($multi
                && ($data["entities_id"] != $prev)) {
               // Do not do it for first item for next page load
               if (!$firstitem) {
                  if ($prev >= 0) {
                     if (count($datastoadd)) {
                        array_push($datas,
                                   array('text'     => Dropdown::getDropdownName("glpi_entities",
                                                                                 $prev),
                                         'children' => $datastoadd));
                     }
                  }
               }
               $prev = $data["entities_id"];
               if ($firstitem) {
                  $firstitem_entity = $prev;
               }
               // Reset last level displayed :
               $datastoadd = array();
            }


            if ($_SESSION['glpiuse_flat_dropdowntree']) {
               if (isset($data['transcompletename']) && !empty($data['transcompletename'])) {
                  $outputval = $data['transcompletename'];
               } else {
                  $outputval = $data['completename'];
               }
               $level = 0;
            } else { // Need to check if parent is the good one
                     // Do not do if only get one item
               if (($level > 1)
                   && ($one_item < 0)) {
                  // Last parent is not the good one need to display arbo
                  if (!isset($last_level_displayed[$level-1])
                      || ($last_level_displayed[$level-1] != $data[$item->getForeignKeyField()])) {

                     $work_level    = $level-1;
                     $work_parentID = $data[$item->getForeignKeyField()];
                     $parent_datas  = array();
                     do {
                        // Get parent
                        if ($item->getFromDB($work_parentID)) {
                           // Do not do for first item for next page load
                           if (!$firstitem) {
                              $title = $item->fields['completename'];

                              if (isset($item->fields["comment"])) {
                                 $addcomment
                                 = DropdownTranslation::getTranslatedValue($ID, $_GET['itemtype'],
                                                                           'comment',
                                                                           $_SESSION['glpilanguage'],
                                                                           $item->fields['comment']);
                                 $title = sprintf(__('%1$s - %2$s'), $title, $addcomment);
                              }
                              $output2 = DropdownTranslation::getTranslatedValue($item->fields['id'],
                                                                                 $_GET['itemtype'],
                                                                                 'name',
                                                                                 $_SESSION['glpilanguage'],
                                                                                 $item->fields['name']);
                           //   $output2 = $item->getName();

                              $temp = array('id'       => $ID,
                                            'text'     => $output2,
                                            'level'    => $work_level,
                                            'disabled' => true);
                              if ($_GET['permit_select_parent']) {
                                 unset($temp['disabled']);
                              }
                              array_unshift($parent_datas, $temp);
                           }
                           $last_level_displayed[$work_level] = $item->fields['id'];
                           $work_level--;
                           $work_parentID = $item->fields[$item->getForeignKeyField()];

                        } else { // Error getting item : stop
                           $work_level = -1;
                        }

                     } while (($work_level >= 1)
                              && (!isset($last_level_displayed[$work_level])
                                  || ($last_level_displayed[$work_level] != $work_parentID)));
                     // Add parents
                     foreach($parent_datas as $val){
                        array_push($datastoadd, $val);
                     }
                  }
               }
               $last_level_displayed[$level] = $data['id'];
            }

            // Do not do for first item for next page load
            if (!$firstitem) {
               if ($_SESSION["glpiis_ids_visible"]
                  || (Toolbox::strlen($outputval) == 0)) {
                  $outputval = sprintf(__('%1$s (%2$s)'), $outputval, $ID);
               }

               if (isset($data['transcompletename']) && !empty($data['transcompletename'])) {
                  $title = $data['transcompletename'];
               } else {
                  $title = $data['completename'];
               }

               if (isset($data["comment"])) {
                  if (isset($data['transcomment']) && !empty($data['transcomment'])) {
                     $addcomment = $data['transcomment'];
                  } else {
                     $addcomment = $data['comment'];
                  }
                  $title = sprintf(__('%1$s - %2$s'), $title, $addcomment);
               }
               array_push($datastoadd, array('id'    => $ID,
                                             'text'  => $outputval,
                                             'level' => $level, 
                                             'title' => $title));
               $count++;
            }
            $firstitem = false;
         }
      }
   }
   if ($multi) {
      if (count($datastoadd)) {
         // On paging mode do not add entity information each time
         if ($prev == $firstitem_entity) {
            $datas = array_merge($datas, $datastoadd);
         } else {
            array_push($datas, array('text'     => Dropdown::getDropdownName("glpi_entities", $prev),
                                     'children' => $datastoadd));
         }
      }
   } else {
      if (count($datastoadd)) {
         $datas = array_merge($datas, $datastoadd);
      }
   }


} else { // Not a dropdowntree
   $multi = false;
   // No multi if get one item
   if ($item->isEntityAssign()
       && ($one_item < 0)) {
      $multi = $item->maybeRecursive();

      if (isset($_GET["entity_restrict"]) && !($_GET["entity_restrict"] < 0)) {
         $where .= getEntitiesRestrictRequest("AND", $table, "entities_id",
                                              $_GET["entity_restrict"], $multi);

         if (is_array($_GET["entity_restrict"]) && (count($_GET["entity_restrict"]) > 1)) {
            $multi = true;
         }

      } else {
         // Do not use entity if may be private
         if (!$item->maybePrivate()) {
            $where .= getEntitiesRestrictRequest("AND", $table, '', '', $multi);

            if (count($_SESSION['glpiactiveentities'])>1) {
               $multi = true;
            }
         } else {
            $multi = false;
         }
      }
   }

   $field = "name";
   if ($item instanceof CommonDevice) {
      $field = "designation";
   } else if ($item instanceof Item_Devices) {
      $field = "itemtype";
   }

   if ($one_item >= 0) {
      $where .=" AND `$table`.`id` = '$one_item'";
   } else {
      if (!empty($_GET['searchText'])) {
         $search = Search::makeTextSearch($_GET['searchText']);
         $where .=" AND  (`$table`.`$field` ".$search;

         if (Session::haveTranslations($_GET['itemtype'], $field)) {
            $where .= " OR `namet`.`value` ".$search;
         }
         if ($_GET['itemtype'] == "SoftwareLicense") {
            $where .= " OR `glpi_softwares`.`name` ".$search;
         }
         // Also search by id
         if ($displaywith && in_array('id', $_GET['displaywith'])) {
            $where .= " OR `$table`.`id` ".$search;
         }

         $where .= ')';
      }
   }
   $addselect = '';
   $addjoin = '';
   if (Session::haveTranslations($_GET['itemtype'], $field)) {
      $addselect .= ", `namet`.`value` AS transname";
      $addjoin   .= " LEFT JOIN `glpi_dropdowntranslations` AS namet
                        ON (`namet`.`itemtype` = '".$_GET['itemtype']."'
                            AND `namet`.`items_id` = `$table`.`id`
                            AND `namet`.`language` = '".$_SESSION['glpilanguage']."'
                            AND `namet`.`field` = '$field')";
   }
   if (Session::haveTranslations($_GET['itemtype'], 'comment')) {
      $addselect .= ", `commentt`.`value` AS transcomment";
      $addjoin   .= " LEFT JOIN `glpi_dropdowntranslations` AS commentt
                        ON (`commentt`.`itemtype` = '".$_GET['itemtype']."'
                            AND `commentt`.`items_id` = `$table`.`id`
                            AND `commentt`.`language` = '".$_SESSION['glpilanguage']."'
                            AND `commentt`.`field` = 'comment')";
   }

   switch ($_GET['itemtype']) {
      case "Contact" :
         $query = "SELECT `$table`.`entities_id`,
                          CONCAT(IFNULL(`name`,''),' ',IFNULL(`firstname`,'')) AS $field,
                          `$table`.`comment`, `$table`.`id`
                   FROM `$table`
                   $where";
         break;

      case "SoftwareLicense" :
         $query = "SELECT `$table`.*,
                          CONCAT(`glpi_softwares`.`name`,' - ',`glpi_softwarelicenses`.`name`)
                              AS $field
                   FROM `$table`
                   LEFT JOIN `glpi_softwares`
                        ON (`glpi_softwarelicenses`.`softwares_id` = `glpi_softwares`.`id`)
                   $where";
         break;

      case "Profile" :
         $query = "SELECT DISTINCT `$table`.*
                   FROM `$table`
                   LEFT JOIN `glpi_profilerights`
                        ON (`glpi_profilerights`.`profiles_id` = `$table`.`id`)
                   $where";
         break;

      default :
         $query = "SELECT `$table`.* $addselect
                   FROM `$table`
                   $addjoin
                   $where";
   }

   if ($multi) {
      $query .= " ORDER BY `$table`.`entities_id`, `$table`.`$field`
                 $LIMIT";
   } else {
      $query .= " ORDER BY `$table`.`$field`
                 $LIMIT";
   }

   if ($result = $DB->query($query)) {

      // Display first if no search
      if ($_GET['page'] == 1 && empty($_GET['searchText'])) {
         if (!isset($_GET['display_emptychoice']) || $_GET['display_emptychoice']) {
            if (($one_item < 0) || ($one_item == 0)) {
               array_push($datas, array('id'    => 0,
                                        'text'  => $_GET["emptylabel"]));
            }
         }
      }
      if ($_GET['page'] == 1) {
         if (count($toadd)) {
            foreach ($toadd as $key => $val) {
               if (($one_item < 0) || ($one_item == $key)) {
                  array_push($datas, array('id'    => $key,
                                           'text'  => stripslashes($val)));
               }
            }
         }
      }

//       $outputval = Dropdown::getDropdownName($table, $_GET['value']);

      $datastoadd = array();

      if ($DB->numrows($result)) {
         $prev = -1;

         while ($data =$DB->fetch_assoc($result)) {
            if ($multi
                && ($data["entities_id"] != $prev)) {
               if ($prev >= 0) {
                  if (count($datastoadd)) {
                     array_push($datas,
                                array('text'     => Dropdown::getDropdownName("glpi_entities",
                                                                              $prev),
                                      'children' => $datastoadd));
                  }
               }
               $prev       = $data["entities_id"];
               $datastoadd = array();
            }

            if (isset($data['transname']) && !empty($data['transname'])) {
               $outputval = $data['transname'];
            } else if ($field == 'itemtype' && class_exists($data['itemtype'])) {
               $tmpitem = new $data[$field]();
               if ($tmpitem->getFromDB($data['items_id'])) {
                  $outputval = sprintf(__('%1$s - %2$s'), $tmpitem->getTypeName(),$tmpitem->getName());
               } else {
                  $outputval = $tmpitem->getTypeName();
               }
            } else {
               $outputval = $data[$field];
            }
            $outputval = Toolbox::unclean_cross_side_scripting_deep($outputval);

            if ($displaywith) {
               foreach ($_GET['displaywith'] as $key) {
                  if (isset($data[$key])) {
                     $withoutput = $data[$key];
                     if (isForeignKeyField($key)) {
                        $withoutput = Dropdown::getDropdownName(getTableNameForForeignKeyField($key),
                                                                $data[$key]);
                     }
                     if ((strlen($withoutput) > 0) && ($withoutput != '&nbsp;')) {
                        $outputval = sprintf(__('%1$s - %2$s'), $outputval, $withoutput);
                     }
                  }
               }
            }
            $ID         = $data['id'];
            $addcomment = "";
            $title      = $outputval;
            if (isset($data["comment"])) {
               if (isset($data['transcomment']) && !empty($data['transcomment'])) {
                  $addcomment .= $data['transcomment'];
               } else {
                  $addcomment .= $data["comment"];
               }

               $title = sprintf(__('%1$s - %2$s'), $title, $addcomment);
            }
            if ($_SESSION["glpiis_ids_visible"]
                || (strlen($outputval) == 0)) {
               //TRANS: %1$s is the name, %2$s the ID
               $outputval = sprintf(__('%1$s (%2$s)'), $outputval, $ID);
            }
            array_push($datastoadd, array('id'    => $ID,
                                          'text'  => $outputval, 
                                          'title' => $title));
            $count++;
         }
         if ($multi) {
            if (count($datastoadd)) {
               array_push($datas, array('text'     => Dropdown::getDropdownName("glpi_entities",
                                                                                $prev),
                                        'children' => $datastoadd));
            }
         } else {
            if (count($datastoadd)) {
               $datas = array_merge($datas, $datastoadd);
            }
         }
      }
   }
}

if (($one_item >= 0) && isset($datas[0])) {
   echo json_encode($datas[0]);
} else {

   $ret['results'] = $datas;
   $ret['count']   = $count;
   echo json_encode($ret);
}

?>
