<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"dropdownValue.php")) {
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

Session::checkLoginUser();

// Security
if (!($item = getItemForItemtype($_POST['itemtype']))) {
   exit();
}

$table = $item->getTable();

$displaywith = false;
if (isset($_POST['displaywith'])) {

   if (!is_array($_POST['displaywith'])) {
       $_POST['displaywith'] = Toolbox::decodeArrayFromInput($_POST["displaywith"]);
   }
   if (is_array($_POST['displaywith'])
       && count($_POST['displaywith'])) {
      $displaywith = true;
   }
}

// No define value
if (!isset($_POST['value'])) {
   $_POST['value'] = '';
}

if (!isset($_POST['permit_select_parent'])) {
   $_POST['permit_select_parent'] = false;
}

// No define rand
if (!isset($_POST['rand'])) {
   $_POST['rand'] = mt_rand();
}

if (isset($_POST['condition']) && !empty($_POST['condition'])) {
   $_POST['condition'] = rawurldecode(stripslashes($_POST['condition']));
}

if (!isset($_POST['emptylabel']) || ($_POST['emptylabel'] == '')) {
   $_POST['emptylabel'] = Dropdown::EMPTY_VALUE;
}

if (isset($_POST["entity_restrict"])
    && !empty($_POST["entity_restrict"])
    && !is_numeric($_POST["entity_restrict"])
    && !is_array($_POST["entity_restrict"])) {

   $_POST["entity_restrict"] = Toolbox::decodeArrayFromInput($_POST["entity_restrict"]);
}

// Make a select box with preselected values
if (!isset($_POST["limit"])) {
   $_POST["limit"] = $_SESSION["glpidropdown_chars_limit"];
}

$where = "WHERE 1 ";

if ($item->maybeDeleted()) {
   $where .= " AND `is_deleted` = '0' ";
}
if ($item->maybeTemplate()) {
   $where .= " AND `is_template` = '0' ";
}

$NBMAX = $CFG_GLPI["dropdown_max"];
$LIMIT = "LIMIT 0,$NBMAX";

if ($_POST['searchText']==$CFG_GLPI["ajax_wildcard"]) {
   $LIMIT = "";
}

$where .=" AND `$table`.`id` NOT IN ('".$_POST['value']."'";

if (isset($_POST['used'])) {

   if (is_array($_POST['used'])) {
      $used = $_POST['used'];
   } else {
      $used = Toolbox::decodeArrayFromInput($_POST['used']);
   }

   if (count($used)) {
      $where .= ",'".implode("','",$used)."'";
   }
}

if (isset($_POST['toadd'])) {
   if (is_array($_POST['toadd'])) {
      $toadd = $_POST['toadd'];
   } else {
      $toadd = Toolbox::decodeArrayFromInput($_POST['toadd']);
   }
} else {
   $toadd = array();
}

$where .= ") ";

if (isset($_POST['condition']) && $_POST['condition'] != '') {
   $where .= " AND ".$_POST['condition']." ";
}

if ($item instanceof CommonTreeDropdown) {

   if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]) {
      $where .= " AND `completename` ".Search::makeTextSearch($_POST['searchText']);
   }
   $multi = false;

   // Manage multiple Entities dropdowns
   $add_order = "";

   if ($item->isEntityAssign()) {
      $recur = $item->maybeRecursive();

       // Entities are not really recursive : do not display parents
      if ($_POST['itemtype'] == 'Entity') {
         $recur = false;
      }

      if (isset($_POST["entity_restrict"]) && !($_POST["entity_restrict"]<0)) {
         $where .= getEntitiesRestrictRequest(" AND ", $table, '', $_POST["entity_restrict"],
                                              $recur);

         if (is_array($_POST["entity_restrict"]) && count($_POST["entity_restrict"])>1) {
            $multi = true;
         }

      } else {
         $where .= getEntitiesRestrictRequest(" AND ", $table, '', '', $recur);

         if (count($_SESSION['glpiactiveentities'])>1) {
            $multi = true;
         }
      }

      // Force recursive items to multi entity view
      if ($recur) {
         $multi = true;
      }

      // no multi view for entitites
      if ($_POST['itemtype'] == "Entity") {
         $multi = false;
      }

      if ($multi) {
         $add_order = '`entities_id`, ';
      }

   }

   $query = "SELECT *
             FROM `$table`
             $where
             ORDER BY $add_order `completename`
             $LIMIT";

   if ($result = $DB->query($query)) {
      echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name='".$_POST['myname']."'
             size='1'";

      if (isset($_POST["on_change"]) && !empty($_POST["on_change"])) {
         echo " onChange='".stripslashes($_POST["on_change"])."'";
      }
      echo ">";

      if (isset($_POST['searchText'])
          && ($_POST['searchText'] != $CFG_GLPI["ajax_wildcard"])
          && ($DB->numrows($result) == $NBMAX)) {
         echo "<option class='tree' value='0'>--".__('Limited view')."--</option>";
      }

      if (count($toadd)) {
         foreach ($toadd as $key => $val) {
            echo "<option class='tree' ".($_POST['value']==$key?'selected':'').
                 " value='$key' title=\"".Html::cleanInputText($val)."\">".
                  Toolbox::substr($val, 0, $_POST["limit"])."</option>";
         }
      }

      if ($_POST['display_emptychoice']) {
         echo "<option class='tree' value='0'>".$_POST['emptylabel']."</option>";
      }

      $outputval = Dropdown::getDropdownName($table, $_POST['value']);

      if ((Toolbox::strlen($outputval) != 0)
          && ($outputval != "&nbsp;")) {

         if (Toolbox::strlen($outputval) > $_POST["limit"]) {
            // Completename for tree dropdown : keep right
            $outputval = "&hellip;".Toolbox::substr($outputval, -$_POST["limit"]);
         }
         if ($_SESSION["glpiis_ids_visible"]
             || (Toolbox::strlen($outputval) == 0)) {
            $outputval .= " (".$_POST['value'].")";
         }
         echo "<option class='tree' selected value='".$_POST['value']."'>".$outputval."</option>";
      }

      $last_level_displayed = array();

      if ($DB->numrows($result)) {
         $prev = -1;

         while ($data = $DB->fetch_assoc($result)) {
            $ID        = $data['id'];
            $level     = $data['level'];
            $outputval = $data['name'];

            if ($displaywith) {
               foreach ($_POST['displaywith'] as $key) {
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

            if ($multi
                && ($data["entities_id"] != $prev)) {
               if ($prev >= 0) {
                  echo "</optgroup>";
               }
               $prev = $data["entities_id"];
               echo "<optgroup label=\"". Dropdown::getDropdownName("glpi_entities", $prev) ."\">";
               // Reset last level displayed :
               $last_level_displayed = array();
            }

            $class = " class='tree' ";
            $raquo = "&raquo;";

            if ($level == 1) {
               $class = " class='treeroot'";
               $raquo = "";
            } else if ($level==2) {
               $class = " class='tree b' ";
            }

            if ($_SESSION['glpiuse_flat_dropdowntree']) {
               $outputval = $data['completename'];
               if ($level > 1) {
                  $class = "";
                  $raquo = "";
                  $level = 0;
               }

            } else { // Need to check if parent is the good one
               if ($level > 1) {
                  // Last parent is not the good one need to display arbo
                  if (!isset($last_level_displayed[$level-1])
                      || ($last_level_displayed[$level-1] != $data[$item->getForeignKeyField()])) {

                     $work_level    = $level-1;
                     $work_parentID = $data[$item->getForeignKeyField()];
                     $to_display    = '';

                     do {
                        // Get parent
                        if ($item->getFromDB($work_parentID)) {
                           $title = $item->fields['completename'];

                           if (isset($item->fields["comment"])) {
                              $title = sprintf(__('%1$s - %2$s'), $title, $item->fields["comment"]);
                           }
                           $output2 = $item->getName();
                           if (Toolbox::strlen($output2)>$_POST["limit"]) {
                              $output2 = Toolbox::substr($output2, 0 ,$_POST["limit"])."&hellip;";
                           }

                           $class2 = " class='tree' ";
                           $raquo2 = "&raquo;";

                           if ($work_level==1) {
                              $class2 = " class='treeroot'";
                              $raquo2 = "";
                           } else if ($work_level==2) {
                              $class2 = " class='tree b' ";
                           }

                           $to_display = "<option ".($_POST['permit_select_parent']?'':'disabled')." value='$work_parentID' $class2
                                           title=\"".Html::cleanInputText($title)."\">".
                                         str_repeat("&nbsp;&nbsp;&nbsp;", $work_level).
                                         $raquo2.$output2."</option>".$to_display;

                           $last_level_displayed[$work_level] = $item->fields['id'];
                           $work_level--;
                           $work_parentID = $item->fields[$item->getForeignKeyField()];

                        } else { // Error getting item : stop
                           $work_level = -1;
                        }

                     } while (($work_level >= 1)
                              && (!isset($last_level_displayed[$work_level])
                                  || ($last_level_displayed[$work_level] != $work_parentID)));

                     echo $to_display;
                  }
               }
               $last_level_displayed[$level] = $data['id'];
            }

            if (Toolbox::strlen($outputval) > $_POST["limit"]) {

               if ($_SESSION['glpiuse_flat_dropdowntree']) {
                  $outputval = "&hellip;".Toolbox::substr($outputval, -$_POST["limit"]);
               } else {
                  $outputval = Toolbox::substr($outputval, 0, $_POST["limit"])."&hellip;";
               }
            }

            if ($_SESSION["glpiis_ids_visible"]
                || (Toolbox::strlen($outputval) == 0)) {
               $outputval = sprintf(__('%1$s (%2$s)'), $outputval, $ID);
            }

            $title = $data['completename'];
            if (isset($data["comment"])) {
               $title = sprintf(__('%1$s - %2$s'), $title, $data["comment"]);
            }
            echo "<option value='$ID' $class title=\"".Html::cleanInputText($title).
                 "\">".str_repeat("&nbsp;&nbsp;&nbsp;", $level).$raquo.$outputval.
                 "</option>";
         }
         if ($multi) {
            echo "</optgroup>";
         }
      }
      echo "</select>";
   }

} else { // Not a dropdowntree
   $multi = false;

   if ($item->isEntityAssign()) {
      $multi = $item->maybeRecursive();

      if (isset($_POST["entity_restrict"]) && !($_POST["entity_restrict"] < 0)) {
         $where .= getEntitiesRestrictRequest("AND", $table, "entities_id",
                                              $_POST["entity_restrict"], $multi);

         if (is_array($_POST["entity_restrict"]) && (count($_POST["entity_restrict"]) > 1)) {
            $multi = true;
         }

      } else {
         $where .= getEntitiesRestrictRequest("AND", $table, '', '', $multi);

         if (count($_SESSION['glpiactiveentities'])>1) {
            $multi = true;
         }
      }
   }

   $field = "name";
   if ($item instanceof CommonDevice) {
      $field = "designation";
   }

   if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]) {
      $search = Search::makeTextSearch($_POST['searchText']);
      $where .=" AND  (`$table`.`$field` ".$search;

      if ($_POST['itemtype']=="SoftwareLicense") {
         $where .= " OR `glpi_softwares`.`name` ".$search;
      }
      $where .= ')';
   }

   switch ($_POST['itemtype']) {
      case "Contact" :
         $query = "SELECT `$table`.`entities_id`,
                          CONCAT(`name`,' ',`firstname`) AS $field,
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

      default :
         $query = "SELECT *
                   FROM `$table`
                   $where";
   }

   if ($multi) {
      $query .= " ORDER BY `entities_id`, $field
                 $LIMIT";
   } else {
      $query .= " ORDER BY $field
                 $LIMIT";
   }

   if ($result = $DB->query($query)) {
      echo "<select id='dropdown_".$_POST["myname"].$_POST["rand"]."' name='".$_POST['myname']."'
             size='1'";

      if (isset($_POST["on_change"]) && !empty($_POST["on_change"])) {
         echo " onChange='".stripslashes($_POST["on_change"])."'";
      }

      echo ">";

      if (isset($_POST['searchText'])
          && ($_POST['searchText'] != $CFG_GLPI["ajax_wildcard"])
          && ($DB->numrows($result) == $NBMAX)) {
         echo "<option value='0'>--".__('Limited view')."--</option>";

      } else if (!isset($_POST['display_emptychoice']) || $_POST['display_emptychoice']) {
         echo "<option value='0'>".$_POST["emptylabel"]."</option>";
      }

      if (count($toadd)) {
         foreach ($toadd as $key => $val) {
            echo "<option title=\"".Html::cleanInputText($val)."\" value='$key' ".
                  ($_POST['value']==$key?'selected':'').">".
                  Toolbox::substr($val, 0, $_POST["limit"])."</option>";
         }
      }

      $outputval = Dropdown::getDropdownName($table,$_POST['value']);

      if ((strlen($outputval) != 0) && ($outputval != "&nbsp;")) {
         if ($_SESSION["glpiis_ids_visible"]) {
            $outputval = sprintf(__('%1$s (%2$s)'), $outputval, $_POST['value']);
         }
         echo "<option selected value='".$_POST['value']."'>".$outputval."</option>";
      }

      if ($DB->numrows($result)) {
         $prev = -1;

         while ($data =$DB->fetch_assoc($result)) {
            $outputval = $data[$field];

            if ($displaywith) {
               foreach ($_POST['displaywith'] as $key) {
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
               $title = sprintf(__('%1$s - %2$s'), $title, $data["comment"]);
            }
            if ($_SESSION["glpiis_ids_visible"]
                || (strlen($outputval) == 0)) {
               //TRANS: %1$s is the name, %2$s the ID
               $outputval = sprintf(__('%1$s (%2$s)'), $outputval, $ID);
            }

            if ($multi
                && ($data["entities_id"] != $prev)) {
               if ($prev >= 0) {
                  echo "</optgroup>";
               }
               $prev = $data["entities_id"];
               echo "<optgroup label=\"". Dropdown::getDropdownName("glpi_entities", $prev) ."\">";
            }

            echo "<option value='$ID' title=\"".Html::cleanInputText($title)."\">".
                  Toolbox::substr($outputval, 0, $_POST["limit"])."</option>";
         }

         if ($multi) {
            echo "</optgroup>";
         }
      }
      echo "</select>";
   }
}

if (isset($_POST["comment"]) && $_POST["comment"]) {
   $paramscomment = array('value' => '__VALUE__',
                          'table' => $table);
   if (isset($_POST['update_link'])) {
      $paramscomment['withlink'] = "comment_link_".$_POST["myname"].$_POST["rand"];
   }
   
   Ajax::updateItemOnSelectEvent("dropdown_".$_POST["myname"].$_POST["rand"],
                                 "comment_".$_POST["myname"].$_POST["rand"],
                                 $CFG_GLPI["root_doc"]."/ajax/comments.php", $paramscomment);
}

Ajax::commonDropdownUpdateItem($_POST);
?>
