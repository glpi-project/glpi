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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"dropdownValue.php")) {
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   header_nocache();
}

if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

checkLoginUser();

// Security
if (!class_exists($_POST['itemtype']) ) {
   exit();
}
$item  = new $_POST['itemtype']();
$table = $item->getTable();

$displaywith = false;

if (isset($_POST['displaywith'])
    && is_array($_POST['displaywith'])
    && count($_POST['displaywith'])) {

   $displaywith = true;
}

// No define value
if (!isset($_POST['value'])) {
   $_POST['value'] = '';
}

// No define rand
if (!isset($_POST['rand'])) {
   $_POST['rand'] = mt_rand();
}

if (isset($_POST['condition']) && !empty($_POST['condition'])) {
   $_POST['condition'] = rawurldecode(stripslashes($_POST['condition']));
}

if (!isset($_POST['emptylabel']) || $_POST['emptylabel'] == '') {
   $_POST['emptylabel'] = DROPDOWN_EMPTY_VALUE;
}

if (isset($_POST["entity_restrict"])
    && !is_numeric($_POST["entity_restrict"])
    && !is_array($_POST["entity_restrict"])) {

   $_POST["entity_restrict"] = unserialize(stripslashes($_POST["entity_restrict"]));
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
      $used = unserialize(stripslashes($_POST['used']));
   }

   if (count($used)) {
      $where .= ",'".implode("','",$used)."'";
   }
}

$where .= ") ";

if (isset($_POST['condition']) && $_POST['condition'] != '') {
   $where .= " AND ".$_POST['condition']." ";
}

if ($item instanceof CommonTreeDropdown) {

   if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"]) {
      $where .= " AND `completename` ".makeTextSearch($_POST['searchText']);
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
      if ($_POST['itemtype']=="Entity") {
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

      if (isset($_POST["auto_submit"]) && $_POST["auto_submit"]==1) {
         echo " onChange='submit()'";
      }
      echo ">";

      if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"] && $DB->numrows($result)==$NBMAX) {
         echo "<option class='tree' value='0'>--".$LANG['common'][11]."--</option>";
      }
      $display_selected = true;

      switch ($table) {
         case "glpi_entities" :
            // If entity=0 allowed
            if (isset($_POST["entity_restrict"])
                && (($_POST["entity_restrict"]<=0 && in_array(0, $_SESSION['glpiactiveentities']))
                    || (is_array($_POST["entity_restrict"]) && in_array(0, $_POST["entity_restrict"])))) {

               echo "<option class='tree' value='0'>--".$LANG['entity'][2]."--</option>";

               // Entity=0 already add above
               if ($_POST['value']==0) {
                  $display_selected = false;
               }
            }
            break;

         default :
            if ($_POST['display_emptychoice']) {
               echo "<option class='tree' value='0'>".$_POST['emptylabel']."</option>";
            }
      }

      if ($display_selected) {
         $outputval = Dropdown::getDropdownName($table, $_POST['value']);

         if (strlen($outputval)!=0 && $outputval!="&nbsp;") {

            if (utf8_strlen($outputval)>$_POST["limit"]) {
               // Completename for tree dropdown : keep right
               $outputval = "&hellip;".utf8_substr($outputval, -$_POST["limit"]);
            }
            if ($_SESSION["glpiis_ids_visible"] || strlen($outputval)==0) {
               $outputval .= " (".$_POST['value'].")";
            }
            echo "<option class='tree' selected value='".$_POST['value']."'>".$outputval."</option>";
         }
      }

      $last_level_displayed = array();

      if ($DB->numrows($result)) {
         $prev = -1;

         while ($data =$DB->fetch_array($result)) {
            $ID     = $data['id'];
            $level  = $data['level'];
            $output = $data['name'];

            if ($displaywith) {
               foreach ($_POST['displaywith'] as $key) {
                  if (isset($data[$key]) && strlen($data[$key])!=0) {
                     $output .= " - ".$data[$key];
                  }
               }
            }

            if ($multi && $data["entities_id"]!=$prev) {
               if ($prev>=0) {
                  echo "</optgroup>";
               }
               $prev = $data["entities_id"];
               echo "<optgroup label=\"". Dropdown::getDropdownName("glpi_entities", $prev) ."\">";
               // Reset last level displayed :
               $last_level_displayed = array();
            }

            $class = " class='tree' ";
            $raquo = "&raquo;";

            if ($level==1) {
               $class = " class='treeroot'";
               $raquo = "";
            }

            if ($_SESSION['glpiuse_flat_dropdowntree']) {
               $output = $data['completename'];
               if ($level>1) {
                  $class = "";
                  $raquo = "";
                  $level = 0;
               }

            } else { // Need to check if parent is the good one
               if ($level>1) {
                  // Last parent is not the good one need to display arbo
                  if (!isset($last_level_displayed[$level-1])
                      || $last_level_displayed[$level-1] != $data[$item->getForeignKeyField()]) {

                     $work_level    = $level-1;
                     $work_parentID = $data[$item->getForeignKeyField()];
                     $to_display    = '';

                     do {
                        // Get parent
                        if ($item->getFromDB($work_parentID)) {
                           $addcomment = "";

                           if (isset($item->fields["comment"])) {
                              $addcomment = " - ".$item->fields["comment"];
                           }
                           $output2 = $item->getName();
                           if (utf8_strlen($output2)>$_POST["limit"]) {
                              $output2 = utf8_substr($output2, 0 ,$_POST["limit"])."&hellip;";
                           }

                           $class2 = " class='tree' ";
                           $raquo2 = "&raquo;";

                           if ($work_level==1) {
                              $class2 = " class='treeroot'";
                              $raquo2 = "";
                           }

                           $to_display = "<option disabled value='$work_parentID' $class2
                                           title=\"".cleanInputText($item->fields['completename'].
                                             $addcomment)."\">".
                                         str_repeat("&nbsp;&nbsp;&nbsp;", $work_level).
                                         $raquo2.$output2."</option>".$to_display;

                           $last_level_displayed[$work_level] = $item->fields['id'];
                           $work_level--;
                           $work_parentID = $item->fields[$item->getForeignKeyField()];

                        } else { // Error getting item : stop
                           $work_level = -1;
                        }

                     } while ($work_level > 1
                              && (!isset($last_level_displayed[$work_level])
                                  || $last_level_displayed[$work_level] != $work_parentID));

                     echo $to_display;
                  }
               }
               $last_level_displayed[$level] = $data['id'];
            }

            if (utf8_strlen($output)>$_POST["limit"]) {

               if ($_SESSION['glpiuse_flat_dropdowntree']) {
                  $output = "&hellip;".utf8_substr($output, -$_POST["limit"]);
               } else {
                  $output = utf8_substr($output, 0, $_POST["limit"])."&hellip;";
               }
            }

            if ($_SESSION["glpiis_ids_visible"] || strlen($output)==0) {
               $output .= " ($ID)";
            }
            $addcomment = "";

            if (isset($data["comment"])) {
               $addcomment = " - ".$data["comment"];
            }
            echo "<option value='$ID' $class title=\"".cleanInputText($data['completename'].
                   $addcomment)."\">".str_repeat("&nbsp;&nbsp;&nbsp;", $level).$raquo.$output.
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

      if (isset($_POST["entity_restrict"]) && !($_POST["entity_restrict"]<0)) {
         $where .= getEntitiesRestrictRequest("AND", $table, "entities_id",
                                              $_POST["entity_restrict"], $multi);

         if (is_array($_POST["entity_restrict"]) && count($_POST["entity_restrict"])>1) {
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
      $search = makeTextSearch($_POST['searchText']);
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

      if (isset($_POST["auto_submit"]) && $_POST["auto_submit"]==1) {
         echo " onChange='submit()'";
      }
      echo ">";

      if ($_POST['searchText']!=$CFG_GLPI["ajax_wildcard"] && $DB->numrows($result)==$NBMAX) {
         echo "<option value='0'>--".$LANG['common'][11]."--</option>";

      } else if (!isset($_POST['display_emptychoice']) || $_POST['display_emptychoice']) {
         echo "<option value='0'>".$_POST["emptylabel"]."</option>";
      }

      $output = Dropdown::getDropdownName($table,$_POST['value']);

      if (strlen($output)!=0 && $output!="&nbsp;") {
         if ($_SESSION["glpiis_ids_visible"]) {
            $output .= " (".$_POST['value'].")";
         }
         echo "<option selected value='".$_POST['value']."'>".$output."</option>";
      }

      if ($DB->numrows($result)) {
         $prev = -1;

         while ($data =$DB->fetch_array($result)) {
            $output = $data[$field];

            if ($displaywith) {
               foreach ($_POST['displaywith'] as $key) {
                  if (isset($data[$key]) && strlen($data[$key])!=0) {
                     $output .= " - ".$data[$key];
                  }
               }
            }
            $ID = $data['id'];
            $addcomment = "";

            if (isset($data["comment"])) {
               $addcomment = " - ".$data["comment"];
            }
            if ($_SESSION["glpiis_ids_visible"] || strlen($output)==0) {
               $output .= " ($ID)";
            }

            if ($multi && $data["entities_id"]!=$prev) {
               if ($prev>=0) {
                  echo "</optgroup>";
               }
               $prev = $data["entities_id"];
               echo "<optgroup label=\"". Dropdown::getDropdownName("glpi_entities", $prev) ."\">";
            }

            echo "<option value='$ID' title=\"".cleanInputText($output.$addcomment)."\">".
                  utf8_substr($output, 0, $_POST["limit"])."</option>";
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

   ajaxUpdateItemOnSelectEvent("dropdown_".$_POST["myname"].$_POST["rand"],
                               "comment_".$_POST["myname"].$_POST["rand"],
                               $CFG_GLPI["root_doc"]."/ajax/comments.php", $paramscomment, false);
}

if (isset($_POST["update_item"])
    && (is_array($_POST["update_item"]) || strlen($_POST["update_item"])>0)) {

   if (!is_array($_POST["update_item"])) {
      $data = unserialize(stripslashes($_POST["update_item"]));
   } else {
      $data = $_POST["update_item"];
   }

   if (is_array($data) && count($data)) {
      $paramsupdate = array();
      if (isset($data['value_fieldname'])) {
         $paramsupdate = array($data['value_fieldname'] => '__VALUE__');
      }

      if (isset($data["moreparams"])
          && is_array($data["moreparams"])
          && count($data["moreparams"])) {

         foreach ($data["moreparams"] as $key => $val) {
            $paramsupdate[$key] = $val;
         }
      }

      ajaxUpdateItemOnSelectEvent("dropdown_".$_POST["myname"].$_POST["rand"], $data['to_update'],
                                  $data['url'], $paramsupdate, false);
   }
}

?>
