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


// FUNCTIONS Setup

function showDropdownList($target, $tablename,$entities_id='',$locations_id=-1) {
   global $DB,$CFG_GLPI,$LANG;

   if (!haveRight("dropdown", "w") && !haveRight("entity_dropdown", "w")) {
      return false;
   }

   $field="name";
   if (in_array($tablename, $CFG_GLPI["dropdowntree_tables"])) {
      $field="completename";
   }

   if (!empty($entities_id) && $entities_id>=0) {
      $entity_restrict = $entities_id;
   } else {
      $entity_restrict = $_SESSION["glpiactive_entity"];
   }

   if ($tablename=="glpi_netpoints") {
      if ($locations_id > 0) {
         $where = " WHERE `locations_id` = '$location'";
      } else if ($locations_id < 0) {
         $where = getEntitiesRestrictRequest(" WHERE ",$tablename,'',$entity_restrict);
      } else {
         $where = " WHERE `locations_id` = '0' " .
                           getEntitiesRestrictRequest(" AND ",$tablename,'',$entity_restrict);
      }
   } else if (in_array($tablename, $CFG_GLPI["specif_entities_tables"])) {
      $where = getEntitiesRestrictRequest(" WHERE ",$tablename,'',$entity_restrict);
   } else {
      $where = '';
   }

   echo "<div class='center'>";
   $query = "SELECT *
             FROM `$tablename`
             $where
             ORDER BY `$field`";
   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)>0) {
         echo "<form method='post' name='massiveaction_form' id='massiveaction_form' action=\"$target\">";
         echo "<table class='tab_cadre_fixe'>";
         $sel="";
         if (isset($_GET["select"]) && $_GET["select"]=="all") {
            $sel="checked";
         }
         $i=0;
         while ($data=$DB->fetch_assoc($result)) {
            $class="class='tab_bg_2'";
            if ($i%2) {
               $class="class='tab_bg_1'";
            }
            echo "<tr $class><td width='10'>";
            echo "<input type='checkbox' name='item[".$data["id"]."]' value='1' $sel></td>";
            echo "<td>".$data[$field]."</td></tr>";
            $i++;
         }
         echo "</table>";
         echo "<input type='hidden' name='which' value='$tablename'>";
         echo "<input type='hidden' name='entities_id' value='$entity_restrict'>";
         echo "<input type='hidden' name='value2' value='$locations_id'>";

         echo "<table width='950px' class='tab_glpi'>";
         $parameters="which=$tablename&amp;mass_deletion=1&amp;entities_id=$entities_id";
         echo "<tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/arrow-left.png\" alt=''></td>";
         echo "<td><a onclick= \"if (markCheckboxes('massiveaction_form')) return false;\" href='".
                $_SERVER['PHP_SELF']."?$parameters&amp;select=all'>".$LANG['buttons'][18]."</a></td>";
         echo "<td>/</td>";
         echo "<td><a onclick=\"if (unMarkCheckboxes('massiveaction_form')) return false;\" href='".
                $_SERVER['PHP_SELF']."?$parameters&amp;select=none'>".$LANG['buttons'][19]."</a></td>";
         echo "<td class='left' width='80%'>";
         echo "<input type='submit' class='submit' name='mass_delete' value='".$LANG['buttons'][6]."'>";
         echo "&nbsp;<strong>".$LANG['setup'][1]."</strong>";
         echo "</td></tr></table>";
         echo "</form>";
      } else {
         echo "<strong>".$LANG['search'][15]."</strong>";
      }
   }
   echo "</div>\n";
}

function showFormTreeDown($target, $tablename, $human, $ID, $value2 = '', $where = '', $tomove = '',
                          $type = '',$entities_id='') {
   global $CFG_GLPI, $LANG;

   if (!haveRight("dropdown", "w") && !haveRight("entity_dropdown", "w")) {
      return false;
   }

   $entity_restrict = -1;
   $numberof = 0;
   if (in_array($tablename, $CFG_GLPI["specif_entities_tables"])) {
      if (!empty($entities_id) && $entities_id>=0) {
         $entity_restrict = $entities_id;
      } else {
         $entity_restrict = $_SESSION["glpiactive_entity"];
      }
      $numberof = countElementsInTableForEntity($tablename, $entity_restrict);
   } else {
      $numberof = countElementsInTable($tablename);
   }

   echo "<div class='center'>\n";
   echo "<form method='post' action=\"$target\">";
   echo "<table class='tab_cadre_fixe'>\n";
   echo "<tr><th colspan='3'>$human:</th></tr>\n";
   if ($numberof > 0) {
      echo "<tr><td class='center middle tab_bg_1'>";
      echo "<input type='hidden' name='which' value='$tablename'>";
      echo "<input type='hidden' name='entities_id' value='$entity_restrict'>";
      $value = getTreeLeafValueName($tablename, $ID, 1);
      dropdownValue($tablename, "id", $ID, 0, $entity_restrict);

      // on ajoute un input text pour entrer la valeur modifier
      echo "&nbsp;&nbsp<input type='image' class='calendrier' src=\"" . $CFG_GLPI["root_doc"] .
            "/pics/puce.gif\" alt='' title='' name='fillright' value='fillright'>&nbsp";
      echo "<input type='hidden' name='tablename' value='$tablename'>";

      if ($ID>0) {
         autocompletionTextField('value',$tablename,'name',$value["name"],40,$entity_restrict,-1,
                                 'maxlength="100"');
         echo '<br>';
         echo "<textarea rows='2' cols='50' name='comment' title='" . $LANG['common'][25] . "' >" .
                $value["comment"] . "</textarea>";

         echo "</td><td class='tab_bg_2 center' width='98'>";

         //  on ajoute un bouton modifier
         echo "<input type='submit' name='update' value='".$LANG['buttons'][14]."' class='submit'>";
         echo "</td><td class='tab_bg_2 center' width='98'>";
         echo "<input type='submit' name='delete' value='".$LANG['buttons'][6]."' class='submit'>";
      } else {
         echo "</td><td class='tab_bg_2 center' width='202'>&nbsp;";
      }
      echo "</td></tr></table></form>\n";

      echo "<form method='post' action=\"$target\">";
      echo "<input type='hidden' name='which' value='$tablename'>";
      echo "<table class='tab_cadre_fixe'>\n";
      echo "<tr><td class='tab_bg_1 center'>";
      dropdownValue($tablename, "value_to_move", $tomove, 0, $entity_restrict);
      echo "&nbsp;&nbsp;&nbsp;" . $LANG['setup'][75] . "&nbsp;:&nbsp;&nbsp;&nbsp;";
      dropdownValue($tablename, "value_where", $where, 0, $entity_restrict);
      echo "</td><td class='tab_bg_2 center' width='202'>";
      echo "<input type='hidden' name='tablename' value='$tablename' >";
      echo "<input type='submit' name='move' value='" . $LANG['buttons'][20] . "' class='submit'>";
      echo "<input type='hidden' name='entities_id' value='$entity_restrict'>";
      echo "</td></tr>";
   }
   echo "</table></form>\n";

   echo "<form action=\"$target\" method='post'>";
   echo "<input type='hidden' name='entities_id' value='$entity_restrict'>";
   echo "<input type='hidden' name='which' value='$tablename'>";

   echo "<table class='tab_cadre_fixe'>\n";
   echo "<tr><td class='tab_bg_1 center'>";
   autocompletionTextField('value',$tablename,'name','',40,$entity_restrict,-1,'maxlength=\'100\'');
   echo "</td><td class='tab_bg_1 center'>";

   if ($numberof > 0) {
      echo "<select name='type'>";
      echo "<option value='under' " .
               ($type == 'under' ? " selected " : "") . ">" . $LANG['setup'][75] . "</option>";
      echo "<option value='same' " .
               ($type == 'same' ? " selected " : "") . ">" . $LANG['setup'][76] . "</option>";
      echo "</select>&nbsp;&nbsp;&nbsp;";
      dropdownValue($tablename, "value2", (strlen($value2) > 0 ? $value2 : 0), 0, $entity_restrict);
   } else {
      echo "<input type='hidden' name='type' value='first'>";
   }
   echo "</td><td rowspan='2' class='tab_bg_2 center' width='202' >";
   echo "<input type='hidden' name='tablename' value='$tablename' >";
   echo "<input type='submit' name='add' value='" . $LANG['buttons'][8] . "' class='submit'>";
   echo "</td></tr>\n";
   echo "<tr><td colspan='2' class='tab_bg_1 center'>";
   echo "<textarea rows='2' cols='50' name='comment' title='".$LANG['common'][25]."'></textarea>";
   echo "</td></tr>";
   echo "</table></form>\n";

   if (strpos($target,'setup.dropdowns.php') && $numberof>0) {
      echo "<a href='$target?which=$tablename&amp;mass_deletion=1&amp;entities_id=$entities_id'>";
      echo $LANG['title'][42]."</a>";
   }

   echo "</div>\n";
}

function showFormNetpoint($target, $human, $ID, $entities_id='',$locations_id=0) {
   global $DB, $CFG_GLPI, $LANG;

   $tablename="glpi_netpoints";

   if (!haveRight("entity_dropdown", "w")) {
      return false;
   }

   $entity_restrict = -1;
   $numberof=0;
   if (!empty($entities_id) && $entities_id>=0) {
      $entity_restrict = $entities_id;
   } else {
      $entity_restrict = $_SESSION["glpiactive_entity"];
   }
   if ($locations_id>0) {
      $numberof = countElementsInTable($tablename, "locations_id=$locations_id ");
   } else if ($locations_id<0) {
      $numberof = countElementsInTable($tablename, getEntitiesRestrictRequest(" ",$tablename,'',
                                                                              $entity_restrict));
   } else {
      $numberof = countElementsInTable($tablename,
                                 "locations_id=0 ".getEntitiesRestrictRequest(" AND ",$tablename,'',
                                                                              $entity_restrict));
   }

   echo "<div class='center'>&nbsp;";
   echo "<form method='post' action=\"$target\">";
   echo "<table class='tab_cadre_fixe'>";
   echo "<tr><th colspan='3'>$human:</th></tr>";
   if ($numberof > 0) {
      echo "<tr><td class='tab_bg_1 center top'>";
      echo "<input type='hidden' name='tablename' value='$tablename'>";
      echo "<input type='hidden' name='which' value='$tablename'>";
      echo "<input type='hidden' name='entities_id' value='$entity_restrict'>";
      dropdownNetpoint("id", $ID, $locations_id, 0, $entity_restrict);

      // on ajoute un input text pour entrer la valeur modifier
      echo "&nbsp;&nbsp;<input type='image' class='calendrier' src=\"" . $CFG_GLPI["root_doc"] .
            "/pics/puce.gif\" alt='' title='' name='fillright' value='fillright'>&nbsp;";

      if ($ID>0) {
         $query = "SELECT *
                   FROM `glpi_netpoints`
                   WHERE `id` = '$ID'";
         $result = $DB->query($query);
         $value = $loc = $comment = "";
         $entity = 0;

         if ($DB->numrows($result) == 1) {
            $value = $DB->result($result, 0, "name");
            $loc = $DB->result($result, 0, "locations_id");
            $comment = $DB->result($result, 0, "comment");
         }
         echo "<br>";
         echo $LANG['common'][15] . "&nbsp;: ";
         dropdownValue("glpi_locations", "value2", $locations_id, 0, $entity_restrict);

         echo $LANG['networking'][52] . "&nbsp;: ";
         autocompletionTextField('value',$tablename,'name',$value,40,$entity_restrict,-1,
                                 'maxlength="100"');
         echo "<br>";
         echo "<textarea rows='2' cols='50' name='comment' title='" . $LANG['common'][25] . "' >" .
                $comment . "</textarea>";
         echo "</td><td class='tab_bg_2 center' width='98'>";

         //  on ajoute un bouton modifier
         echo "<input type='submit' name='update' value='".$LANG['buttons'][14]."' class='submit'>";
         echo "</td><td class='tab_bg_2 center' width='98'>";
         echo "<input type='submit' name='delete' value='".$LANG['buttons'][6]."' class='submit'>";
      } else {
         echo "<input type='hidden' name='value2' value='$locations_id'>";
         echo "</td><td class='tab_bg_2 center' width='202'>&nbsp;";
      }
      echo "</td></tr>";
   }
   echo "</table></form>\n";

   echo "<form action=\"$target\" method='post'>";
   echo "<input type='hidden' name='which' value='$tablename'>";
   echo "<input type='hidden' name='tablename' value='$tablename' >";
   echo "<input type='hidden' name='entities_id' value='$entity_restrict'>";
   echo "<input type='hidden' name='value2' value='$locations_id'>";

   echo "<table class='tab_cadre_fixe'>";
   echo "<tr><td class='tab_bg_1 center'>".$LANG['networking'][52] . "&nbsp;: ";
   autocompletionTextField('value',$tablename,'name','',40,$entity_restrict,-1,'maxlength="100"');
   echo "<br>";
   echo "<textarea rows='2' cols='50' name='comment' title='" .$LANG['common'][25] ."'></textarea>";
   echo "</td><td class='tab_bg_2 center' width='202'>";
   echo "<input type='submit' name='add' value='" . $LANG['buttons'][8] . "' class='submit'>";
   echo "</td></tr>";

   echo "</table></form>\n";

   // Multiple Add for Netpoint
   echo "<form action=\"$target\" method='post'>";
   echo "<input type='hidden' name='which' value='$tablename'>";
   echo "<input type='hidden' name='value2' value='$locations_id'>";
   echo "<input type='hidden' name='tablename' value='$tablename' >";
   echo "<input type='hidden' name='entities_id' value='$entity_restrict'>";

   echo "<table class='tab_cadre_fixe'>";
   echo "<tr><td class ='center tab_bg_1'>".$LANG['networking'][52] . "&nbsp;: ";
   echo "<input type='text' maxlength='100' size='5' name='before'>";
   dropdownInteger('from', 0, 0, 400);
   echo "-->";
   dropdownInteger('to', 0, 0, 400);
   echo "<input type='text' maxlength='100' size='5' name='after'><br>";
   echo "<textarea rows='2' cols='50' name='comment' title='".$LANG['common'][25]."'></textarea></td>";
   echo "<td class='center tab_bg_2' width='202'>";
   echo "<input type='submit' name='several_add' value=\"" . $LANG['buttons'][8] . "\" class='submit'>";
   echo "</td></tr>";
   echo "</table></form>\n";

   if (strpos($target,'setup.dropdowns.php') && $numberof>0) {
      echo "<a href='$target?which=$tablename&amp;mass_deletion=1&amp;entities_id=$entities_id&amp;".
            "value2=$locations_id'>".$LANG['title'][42]."</a>";
   }

   echo "</div>";
}

function showFormDropDown($target, $tablename, $human, $ID, $entities_id='') {
   global $DB, $CFG_GLPI, $LANG;

   if (!haveRight("dropdown", "w") && !haveRight("entity_dropdown", "w")) {
      return false;
   }

   $entity_restrict = -1;
   $numberof=0;
   if (in_array($tablename, $CFG_GLPI["specif_entities_tables"])) {
      if (!empty($entities_id) && $entities_id>=0) {
         $entity_restrict = $entities_id;
      } else {
         $entity_restrict = $_SESSION["glpiactive_entity"];
      }
      $numberof = countElementsInTableForEntity($tablename, $entity_restrict);
   } else {
      $numberof = countElementsInTable($tablename);
   }

   echo "<div class='center'>&nbsp;";
   echo "<form method='post' action=\"$target\">";
   echo "<table class='tab_cadre_fixe'>";
   echo "<tr><th colspan='3'>$human:</th></tr>";
   if ($numberof > 0) {
      echo "<tr><td class='tab_bg_1 center top'>";
      echo "<input type='hidden' name='which' value='$tablename'>";
      echo "<input type='hidden' name='entities_id' value='$entity_restrict'>";

      if (!empty ($ID)) {
         $value = getDropdownName($tablename, $ID, 1);
      } else {
         $value = array("name" => "",
                        "comment" => "");
      }
      dropdownValue($tablename, "id", $ID, 0, $entity_restrict);

      // on ajoute un input text pour entrer la valeur modifier
      echo "&nbsp;&nbsp;<input type='image' class='calendrier' src=\"" .
            $CFG_GLPI["root_doc"] . "/pics/puce.gif\" alt='' title='' name='fillright' value='".
            "fillright'>&nbsp;";
      echo "<input type='hidden' name='tablename' value='$tablename'>";

      if ($ID>0) {
         autocompletionTextField('value',$tablename,'name',$value["name"],40,$entity_restrict,-1,
                                 'maxlength="100"');
         echo "<br>";
         echo "<textarea rows='2' cols='50' name='comment' title='" . $LANG['common'][25] . "' >" .
                $value["comment"] . "</textarea>";

         echo "</td><td class='tab_bg_2 center' width='98'>";

         //  on ajoute un bouton modifier
         echo "<input type='submit' name='update' value='".$LANG['buttons'][14]."' class='submit'>";
         echo "</td><td class='tab_bg_2 center' width='98'>";
         echo "<input type='submit' name='delete' value=\"" . $LANG['buttons'][6] . "\" class='submit'>";
      } else {
         echo "</td><td class='tab_bg_2 center' width='202'>&nbsp;";
      }
      echo "</td></tr>";

   }
   echo "</table></form>\n";
   echo "<form action=\"$target\" method='post'>";
   echo "<input type='hidden' name='which' value='$tablename'>";
   echo "<input type='hidden' name='entities_id' value='$entity_restrict'>";

   echo "<table class='tab_cadre_fixe'>";
   echo "<tr><td class='tab_bg_1 center'>";
   autocompletionTextField('value',$tablename,'name','',40,$entity_restrict,-1,'maxlength="100"');
   echo "<br>";
   echo "<textarea rows='2' cols='50' name='comment' title='" . $LANG['common'][25] . "'></textarea>";

   echo "</td><td class='tab_bg_2 center' width='202'>";
   echo "<input type='hidden' name='tablename' value='$tablename' >";
   echo "<input type='hidden' name='entities_id' value='$entity_restrict'>";

   echo "<input type='submit' name='add' value=\"" . $LANG['buttons'][8] . "\" class='submit'>";
   echo "</td></tr>";

   echo "</table></form>";

   if (strpos($target,'setup.dropdowns.php') && $numberof>0) {
      echo "<a href='$target?which=$tablename&amp;mass_deletion=1&amp;entities_id=$entities_id'>";
      echo $LANG['title'][42]."</a>";
   }

   echo "</div>";
}

function moveTreeUnder($table, $to_move, $where) {
   global $DB;

   $parentIDfield=getForeignKeyFieldForTable($table);

   if ($where != $to_move) {
      // Is the $where location under the to move ???
      $impossible_move = false;

      $current_ID = $where;
      while ($current_ID != 0 && $impossible_move == false) {
         $query = "SELECT *
                   FROM `$table`
                   WHERE `id` = '$current_ID'";
         $result = $DB->query($query);
         $current_ID = $DB->result($result, 0, "$parentIDfield");
         if ($current_ID == $to_move) {
            $impossible_move = true;
         }
      }
      if (!$impossible_move) {
         // Move Location
         $query = "UPDATE
                   `$table`
                   SET $parentIDfield = '$where'
                   WHERE `id` = '$to_move'";
         $result = $DB->query($query);
         regenerateTreeCompleteNameUnderID($table, $to_move);

         // Clean sons / ancestors if needed
         CleanFields($table, 'sons_cache', 'ancestors_cache');
      }
   }
}

function updateDropdown($input) {
   global $DB, $CFG_GLPI;

   // Clean datas
   $input["value"]=trim($input["value"]);
   if (empty($input["value"])) {
      return false;
   }

   $query = "UPDATE
             `".$input["tablename"]."`
             SET `name` = '".$input["value"]."', `comment` = '".$input["comment"]."'";

   if ($input["tablename"] == "glpi_netpoints") {
      $query .= ", `locations_id` = '".$input["value2"]."'";
   }
   $query .= "WHERE `id` = '".$input["id"]."'";

   if ($result = $DB->query($query)) {
      if (in_array($input["tablename"], $CFG_GLPI["dropdowntree_tables"])) {
         regenerateTreeCompleteNameUnderID($input["tablename"], $input["id"]);
      }
      // Clean sons / ancestors if needed
      CleanFields($input["tablename"], 'sons_cache', 'ancestors_cache');
      return true;
   }
}

function getDropdownID($input) {
   global $DB, $CFG_GLPI;

   // Clean datas
   $input["value"]=trim($input["value"]);
   if (!empty ($input["value"])) {
      $add_entity_field_twin = "";
      if (in_array($input["tablename"], $CFG_GLPI["specif_entities_tables"])) {
         $add_entity_field_twin = " `entities_id` = '" . $input["entities_id"] . "'
                                   AND ";
      }

      $query_twin = "SELECT `id`
                     FROM `".$input["tablename"]."`
                     WHERE $add_entity_field_twin
                               `name` = '".$input["value"]."'";

      if ($input["tablename"] == "glpi_netpoints") {
         $query_twin .= " AND `locations_id` = '".$input["value2"]."'";

      } else if (in_array($input["tablename"], $CFG_GLPI["dropdowntree_tables"])) {
         $parentIDfield=getForeignKeyFieldForTable($input["tablename"]);

         if ($input['type'] != "first" && $input["value2"] != 0) {
            $level_up=-1;
            $query = "SELECT *
                      FROM `".$input["tablename"]."`
                      WHERE `id` = '" . $input["value2"] . "'";
            $result = $DB->query($query);

            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_array($result);
               $level_up = $data[$parentIDfield];
               if ($input["type"] == "under") {
                  $level_up = $data["id"];
               }
            }
         } else {
            $level_up = 0;
         }
         $query_twin .= " AND `$parentIDfield` = '$level_up'";
      }

      // Check twin :
      if ($result_twin = $DB->query($query_twin) ) {
         if ($DB->numrows($result_twin) > 0) {
            return $DB->result($result_twin,0,"id");
         }
      }
      return -1;
   }
}

/**
 * Import a value in a dropdown table.
 *
 * This import a new dropdown if it doesn't exist.
 *
 *@param $dpdTable string : Name of the glpi dropdown table.
 *@param $value string : Value of the new dropdown.
 *@param $entities_id int : entity in case of specific dropdown
 *@param $external_params
 *@param $comment
 *@param $add if true, add it if not found. if false, just check if exists
 *
 *@return integer : dropdown id.
 *
 **/
function externalImportDropdown($dpdTable, $value, $entities_id = -1,$external_params=array(),
                                $comment="",$add=true) {
   global $DB, $CFG_GLPI;

   $value=trim($value);
   if (strlen($value) == 0) {
      return 0;
   }

   $input["tablename"] = $dpdTable;
   $input["value"] = $value;
   $input['type'] = "first";
   $input["comment"] = $comment;
   $input["entities_id"] = $entities_id;

   $process = false;
   $input_values=array("name"=>$value);
   $rulecollection = getRuleCollectionClassByTableName($dpdTable);

   switch ($dpdTable) {
      case "glpi_manufacturers" :
      case "glpi_operatingsystems" :
      case "glpi_operatingsystemsservicepacks" :
      case "glpi_operatingsystemsversions" :
      case "glpi_computerstypes" :
      case "glpi_monitorstypes" :
      case "glpi_printerstypes" :
      case "glpi_peripheralstypes" :
      case "glpi_phonestypes" :
      case "glpi_networkequipmentstypes" :
         $process = true;
         break;

      case "glpi_computersmodels" :
      case "glpi_monitorsmodels" :
      case "glpi_printersmodels" :
      case "glpi_peripheralsmodels" :
      case "glpi_phonesmodels" :
      case "glpi_networkequipmentsmodels" :
         $process = true;
         $input_values["manufacturer"] = $external_params["manufacturer"];
         break;
   }
   if ($process) {
      $res_rule = $rulecollection->processAllRules($input_values, array (), array());
      if (isset($res_rule["name"])) {
         $input["value"] = $res_rule["name"];
      }
   }
   return ($add ? addDropdown($input) : getDropdownID($input));
}

function addDropdown($input) {
   global $DB, $CFG_GLPI;

   // Clean datas
   $input["value"]=trim($input["value"]);

   // Check twin :
   if ($ID = getDropdownID($input)) {
      if ($ID>0) {
         return $ID;
      }
   }

   if (!empty ($input["value"])) {
      $add_entity_field = "";
      $add_entity_value = "";
      if (in_array($input["tablename"], $CFG_GLPI["specif_entities_tables"])) {
         $add_entity_field = "`entities_id`,";
         $add_entity_value = "'" . $input["entities_id"] . "',";
      }

      $field ="INSERT INTO
                `".$input["tablename"]."` ($add_entity_field `name`, `comment`";
      $valuefield = "VALUES ($add_entity_value '" . $input["value"] . "', '" . $input["comment"] . "'";

      if ($input["tablename"] == "glpi_netpoints") {
         $query = "$field, `locations_id`)
                   $valuefield, '" . $input["value2"] . "')";

      } else if (in_array($input["tablename"], $CFG_GLPI["dropdowntree_tables"])) {
         $parentIDfield=getForeignKeyFieldForTable($input["tablename"]);

         if ($input['type'] != "first" && $input["value2"] != 0) {
            $level_up=-1;
            $query = "SELECT *
                      FROM `".$input["tablename"]."`
                      WHERE `id` = '" . $input["value2"] . "'";
            $result = $DB->query($query);

            if ($DB->numrows($result) > 0) {
               $data = $DB->fetch_array($result);
               $level_up = $data["$parentIDfield"];
               if ($input["type"] == "under") {
                  $level_up = $data["id"];
               }
            }
         } else {
            $level_up = 0;
         }
         $query = "$field, `$parentIDfield`,`completename`)
                   $valuefield, '$level_up','')";
      } else {
         $query = "$field)
                   $valuefield)";
      }

      if ($result = $DB->query($query)) {
         $ID = $DB->insert_id();
         if (in_array($input["tablename"], $CFG_GLPI["dropdowntree_tables"])) {
            regenerateTreeCompleteNameUnderID($input["tablename"], $ID);
         }
         // Clean sons / ancestors if needed
         CleanFields($input["tablename"], 'sons_cache', 'ancestors_cache');

         return $ID;
      }
   }
}

function deleteDropdown($input) {
   global $DB;

   $send = array ();
   $send["tablename"] = $input["tablename"];
   $send["oldID"] = $input["id"];
   $send["newID"] = 0;
   replaceDropDropDown($send);
}

/** Replace a dropdown item (oldID) by another one (newID) in a dropdown table (tablename) and update all linked fields
* @param $input array : paramaters : need tablename / oldID / newID
*/
function replaceDropDropDown($input) {
   global $DB,$CFG_GLPI;

   if (!isset($input["tablename"]) || !isset($input["oldID"]) || !isset($input["newID"])
       || $input["oldID"]==$input["newID"]) {
      return false;
   }

   $RELATION = getDbRelations();

   if (isset ($RELATION[$input["tablename"]])) {
      foreach ($RELATION[$input["tablename"]] as $table => $field) {
         if ($table[0]!='_') {
            if (!is_array($field)) {
               // Manage OCS lock for items - no need for array case
               if ($table=="glpi_computers" && $CFG_GLPI['use_ocs_mode']) {
                  $query = "SELECT `id`
                            FROM `glpi_computers`
                            WHERE `is_ocs_import` = '1'
                                  AND `$field` = '" . $input["oldID"] . "'";
                  $result=$DB->query($query);
                  if ($DB->numrows($result)) {
                     if (!function_exists('mergeOcsArray')) {
                        include_once (GLPI_ROOT . "/inc/ocsng.function.php");
                     }
                     while ($data=$DB->fetch_array($result)) {
                        mergeOcsArray($data['id'],array($field),"computer_update");
                     }
                  }
               }
               $query = "UPDATE
                         `$table`
                         SET `$field` = '" . $input["newID"] . "'
                         WHERE `$field` = '" . $input["oldID"] . "'";
               $DB->query($query);
            } else {
               foreach ($field as $f) {
                  $query = "UPDATE
                            `$table`
                            SET `$f` = '" . $input["newID"] . "'
                            WHERE `$f` = '" . $input["oldID"] . "'";
                  $DB->query($query);
               }
            }
         }
      }
   }
   $query = "DELETE
             FROM `".$input["tablename"]."`
             WHERE `id` = '" . $input["oldID"] . "'";
   $DB->query($query);

   // Need to be done on entity class
   if ($input["tablename"]=="glpi_entities") {
      $query = "DELETE
                FROM `glpi_entitiesdatas`
                WHERE `entities_id` = '" . $input["oldID"] . "'";
      $DB->query($query);

      $di = new DocumentItem();
      $di->cleanDBonItemDelete(ENTITY_TYPE,$input["oldID"]);

      // Clean sons / ancestors if needed
      CleanFields('glpi_entities', 'sons_cache', 'ancestors_cache');
   }
}

function showDeleteConfirmForm($target, $table, $ID,$entities_id) {
   global $DB, $LANG,$CFG_GLPI;

   if (in_array($table, $CFG_GLPI["specif_entities_tables"])) {
      if (!haveRight("entity_dropdown","w")) {
         return false;
      }
   } else if (!haveRight("dropdown", "w")) {
      return false;
   }

   if (in_array($table,$CFG_GLPI["dropdowntree_tables"])) {
      $parentIDfield=getForeignKeyFieldForTable($table);
      $query = "SELECT COUNT(*) AS cpt
                FROM `$table`
                WHERE `$parentIDfield` = '$ID'";
      $result = $DB->query($query);
      if ($DB->result($result, 0, "cpt") > 0) {
         echo "<div class='center'><p class='red'>" . $LANG['setup'][74] . "</p></div>";
         return;
      }

      if ($table == "glpi_knowbaseitemscategories") {
         $query = "SELECT COUNT(*) AS cpt
                   FROM `glpi_knowbaseitems`
                   WHERE `knowbaseitemscategories_id` = '$ID'";
         $result = $DB->query($query);
         if ($DB->result($result, 0, "cpt") > 0) {
            echo "<div class='center'><p class='red'>" . $LANG['setup'][74] . "</p></div>";
            return;
         }
      }
   }
   echo "<div class='center'>";
   echo "<p class='red'>" . $LANG['setup'][63] . "</p>";

   if ($table!="glpi_entities") {
      echo "<p>" . $LANG['setup'][64] . "</p>";
      echo "<form action=\"$target\" method='post'>";
      echo "<input type='hidden' name='tablename' value='$table'/>";
      echo "<input type='hidden' name='id' value='$ID'/>";
      echo "<input type='hidden' name='which' value='$table'/>";
      echo "<input type='hidden' name='forcedelete' value='1'/>";
      echo "<input type='hidden' name='entities_id' value='$entities_id'/>";

      echo "<table class='tab_cadre'><tr><td>";
      echo "<input class='button' type='submit' name='delete' value='".$LANG['buttons'][2]."'/></td>";
      echo "<td><input class='button' type='submit' name='annuler' value='".$LANG['buttons'][34]."'/>";
      echo "</td></tr></table>\n";
      echo "</form>";
   }
   echo "<p>" . $LANG['setup'][65] . "</p>";
   echo "<form action=\"$target\" method='post'>";
   echo "<input type='hidden' name='which' value='$table'/>";
   echo "<table class='tab_cadre'><tr><td>";
   dropdownNoValue($table, "newID", $ID,$entities_id);
   echo "<input type='hidden' name='tablename' value='$table'/>";
   echo "<input type='hidden' name='oldID' value='$ID'/>";
   echo "<input type='hidden' name='entities_id' value='$entities_id'/>";
   echo "</td><td><input class='button' type='submit' name='replace' value='".$LANG['buttons'][39]."'/>";
   echo "</td><td>";
   echo "<input class='button' type='submit' name='annuler' value='".$LANG['buttons'][34]."' /></td>";
   echo "</tr></table>\n";
   echo "</form>";
   echo "</div>";
}

/** Check if the dropdown $ID is used into item tables
* @param $table string : table name
* @param $ID integer : value ID
* @return boolean : is the value used ?
*/
function dropdownUsed($table, $ID) {
   global $DB;

   $RELATION = getDbRelations();
   if (isset ($RELATION[$table])) {
      foreach ($RELATION[$table] as $tablename => $field) {
         if ($tablename[0]!='_') {
            if (!is_array($field)) {
               $query = "SELECT COUNT(*) AS cpt
                         FROM `$tablename`
                         WHERE `$field` = '$ID'";
               $result = $DB->query($query);
               if ($DB->result($result, 0, "cpt") > 0) {
                  return true;
               }
            } else {
               foreach ($field as $f) {
                  $query = "SELECT COUNT(*) AS cpt
                            FROM `$tablename`
                            WHERE `$f` = '$ID'";
                  $result = $DB->query($query);
                  if ($DB->result($result, 0, "cpt") > 0) {
                     return true;
                  }
               }
            }
         }
      }
   }
   return false;
}

function listTemplates($itemtype, $target, $add = 0) {
   global $DB, $CFG_GLPI, $LANG;

   //Check is user have minimum right r
   if (!haveTypeRight($itemtype, "r") && !haveTypeRight($itemtype, "w")) {
      return false;
   }

   $query = "SELECT * ";
   $where = " WHERE `is_template` = '1'";
   $whereentity = " AND `entities_id` = '" . $_SESSION["glpiactive_entity"] . "'";
   $order = " ORDER by `template_name`";
   switch ($itemtype) {
      case COMPUTER_TYPE :
         $title = $LANG['Menu'][0];
         $query .= "FROM `glpi_computers`
                    $where
                        $whereentity
                    $order";
         break;

      case NETWORKING_TYPE :
         $title = $LANG['Menu'][1];
         $query .= "FROM `glpi_networkequipments`
                    $where
                        $whereentity
                    $order";
         break;

      case MONITOR_TYPE :
         $title = $LANG['Menu'][3];
         $query .= "FROM `glpi_monitors`
                    $where
                        $whereentity
                    $order";
         break;

      case PRINTER_TYPE :
         $title = $LANG['Menu'][2];
         $query .= "FROM `glpi_printers`
                    $where
                        $whereentity
                    $order";
         break;

      case PERIPHERAL_TYPE :
         $title = $LANG['Menu'][16];
         $query .= "FROM `glpi_peripherals`
                    $where
                        $whereentity
                    $order";
         break;

      case SOFTWARE_TYPE :
         $title = $LANG['Menu'][4];
         $query .= "FROM `glpi_softwares`
                    $where
                        $whereentity
                    $order";
         break;

      case PHONE_TYPE :
         $title = $LANG['Menu'][34];
         $query = "FROM `glpi_phones`
                    $where
                        $whereentity
                    $order";
         break;

      case OCSNG_TYPE :
         $title = $LANG['Menu'][33];
         $query = "FROM `glpi_ocsservers`
                    $where
                    $order";
         break;

      case BUDGET_TYPE :
         $title = $LANG['financial'][87];
         $query = "FROM `glpi_budgets`
                    $where
                    $order";
         break;
   }
   if ($result = $DB->query($query)) {
      echo "<div class='center'><table class='tab_cadre' width='50%'>";
      if ($add) {
         echo "<tr><th>" . $LANG['common'][7] . " - $title:</th></tr>";
         echo "<tr><td class='tab_bg_1 center'>";
         echo "<a href=\"$target?id=-1&amp;withtemplate=2\">&nbsp;&nbsp;&nbsp;" .
                $LANG['common'][31] . "&nbsp;&nbsp;&nbsp;</a></td>";
         echo "</tr>";
      } else {
         echo "<tr><th colspan='2'>" . $LANG['common'][14] . " - $title:</th></tr>";
      }

      while ($data = $DB->fetch_array($result)) {
         $templname = $data["template_name"];
         if ($_SESSION["glpiis_ids_visible"] || empty($data["template_name"])) {
            $templname.= "(".$data["id"].")";
         }
         echo "<tr><td class='tab_bg_1 center'>";
         if (haveTypeRight($itemtype, "w") && !$add) {
            echo "<a href=\"$target?id=" . $data["id"] . "&amp;withtemplate=1\">";
            echo "&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
            echo "<td class='tab_bg_2 center b'>";
            echo "<a href=\"$target?id=" . $data["id"] . "&amp;purge=purge&amp;withtemplate=1\">" .
                   $LANG['buttons'][6] . "</a></td>";
         } else {
            echo "<a href=\"$target?id=" . $data["id"] . "&amp;withtemplate=2\">";
            echo "&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
         }
         echo "</tr>";
      }

      if (haveTypeRight($itemtype, "w") && !$add) {
         echo "<tr><td colspan='2' class='tab_bg_2 center b'>";
         echo "<a href=\"$target?withtemplate=1\">" . $LANG['common'][9] . "</a>";
         echo "</td></tr>";
      }
      echo "</table></div>\n";
   }
}

function showLdapAuthList($target) {
   global $DB, $LANG, $CFG_GLPI;

   if (!haveRight("config", "w")) {
      return false;
   }

   echo "<div class='center'>";

   if (canUseLdap()) {
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2' class='b'>". $LANG['login'][2] . "</th></tr>\n";
      echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['common'][16] . "</td>";
      echo "<td class='center'>" . $LANG['common'][52] . "</td></tr>\n";

      $sql = "SELECT *
              FROM `glpi_authldaps`";
      $result = $DB->query($sql);
      if ($DB->numrows($result)) {
         while ($ldap_method = $DB->fetch_array($result)) {
            echo "<tr class='tab_bg_2'><td class='center'>";
            echo "<a href='$target?next=extauth_ldap&amp;id=" . $ldap_method["id"] . "' >";
            echo $ldap_method["name"] . "</a>" ."</td>";
            echo "<td class='center'>" . $LANG['ldap'][21]."&nbsp;: ".$ldap_method["host"]."&nbsp;:".
                   $ldap_method["port"];
            $replicates=getAllReplicatesNamesForAMaster($ldap_method["id"]);
            if (!empty($replicates)) {
               echo "<br>".$LANG['ldap'][22]."&nbsp;: ".$replicates. "</td>";
            }
            echo '</tr>';
         }
      }
      echo "</table>\n";
   } else {
      echo '<input type="hidden" name="LDAP_Test" value="1">';
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>" . $LANG['setup'][152] . "</th></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center><p class='red'>" . $LANG['setup'][157] . "</p>";
      echo "<p>" . $LANG['setup'][158] . "</p></td></tr></table>\n";
   }
   echo "</div>";
}

function showOtherAuthList($target) {
   global $DB, $LANG, $CFG_GLPI;

   if (!haveRight("config", "w")) {
      return false;
   }

   echo "<form name=cas action=\"$target\" method='post'>";
   echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";
   echo "<div class='center'>";
   echo "<table class='tab_cadre_fixe'>";

   // CAS config
   echo "<tr><th colspan='2'>" . $LANG['setup'][177];
   if (!empty($CFG_GLPI["cas_host"])) {
      echo " - ".$LANG['setup'][192];
   }
   echo "</th></tr>\n";

   if (function_exists('curl_init')
       && (version_compare(PHP_VERSION, '5', '>=') || (function_exists("domxml_open_mem")))) {

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][174] . "</td>";
      echo "<td><input type='text' name='cas_host' value=\"".$CFG_GLPI["cas_host"]."\"></td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][175] . "</td>";
      echo "<td><input type='text' name='cas_port' value=\"".$CFG_GLPI["cas_port"]."\"></td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][176] . "</td>";
      echo "<td><input type='text' name='cas_uri' value=\"".$CFG_GLPI["cas_uri"]."\"></td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][182] . "</td>";
      echo "<td><input type='text' name='cas_logout' value=\"".$CFG_GLPI["cas_logout"]."\"></td></tr>\n";
   } else {
      echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
      echo "<p class='red'>" . $LANG['setup'][178] . "</p>";
      echo "<p>" . $LANG['setup'][179] . "</p></td></tr>\n";
   }
   // X509 config
   echo "<tr><th colspan='2'>" . $LANG['setup'][190];
   if (!empty($CFG_GLPI["x509_email_field"])) {
      echo " - ".$LANG['setup'][192];
   }
   echo "</th></tr>\n";
   echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][191] . "</td>";
   echo "<td><input type='text' name='x509_email_field' value=\"".$CFG_GLPI["x509_email_field"]."\">";
   echo "</td></tr>\n";

   // Autres config
   echo "<tr><th colspan='2'>" . $LANG['common'][67];
   if (!empty($CFG_GLPI["existing_auth_server_field"])) {
      echo " - ".$LANG['setup'][192];
   }
   echo "</th></tr>\n";
   echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][193] . "</td>";
   echo "<td><select name='existing_auth_server_field'>";
   echo "<option value=''>&nbsp;</option>\n";
   echo "<option value='HTTP_AUTH_USER' " .
          ($CFG_GLPI["existing_auth_server_field"]=="HTTP_AUTH_USER" ? " selected " : "") . ">".
          "HTTP_AUTH_USER</option>\n";
   echo "<option value='REMOTE_USER' " .
          ($CFG_GLPI["existing_auth_server_field"]=="REMOTE_USER" ? " selected " : "") . ">".
          "REMOTE_USER</option>\n";
   echo "<option value='PHP_AUTH_USER' " .
          ($CFG_GLPI["existing_auth_server_field"]=="PHP_AUTH_USER" ? " selected " : "") . ">".
          "PHP_AUTH_USER</option>\n";
   echo "<option value='USERNAME' " .
          ($CFG_GLPI["existing_auth_server_field"]=="USERNAME" ? " selected " : "") . ">".
          "USERNAME</option>\n";
   echo "<option value='REDIRECT_REMOTE_USER' " .
          ($CFG_GLPI["existing_auth_server_field"]=="REDIRECT_REMOTE_USER" ? " selected " : "") .">".
          "REDIRECT_REMOTE_USER</option>\n";
   echo "</select>";
   echo "</td></tr>\n";

   echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][199] . "</td><td>";
   dropdownYesNo('existing_auth_server_field_clean_domain',
                 $CFG_GLPI['existing_auth_server_field_clean_domain']);
   echo "</td></tr>\n";

   echo "<tr><th colspan='2'>" . $LANG['setup'][194]."</th></tr>\n";
   echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ldap'][4] . "</td><td>";
   dropdownValue("glpi_authldaps","authldaps_id_extra",$CFG_GLPI["authldaps_id_extra"]);
   echo "</td></tr>\n";

   echo "<tr class='tab_bg_1'><td class='center' colspan='2'>";
   echo "<input type='submit' name='update' class='submit' value=\"".$LANG['buttons'][7]."\" >";
   echo "</td></tr>";

   echo "</table></div></form>\n";
}

function showImapAuthList($target) {
   global $DB, $LANG, $CFG_GLPI;

   if (!haveRight("config", "w")) {
      return false;
   }

   echo "<div class='center'>";

   if (canUseImapPop()) {
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'class='b'>" . $LANG['login'][3] . "</th></tr>\n";
      echo "<tr class='tab_bg_1'><td class='center'>" . $LANG['common'][16] . "</td>";
      echo "<td class='center'>" . $LANG['common'][52] . "</td></tr>\n";
      $sql = "SELECT *
              FROM `glpi_authmails`";
      $result = $DB->query($sql);
      if ($DB->numrows($result)) {
         while ($mail_method = $DB->fetch_array($result)) {
            echo "<tr class='tab_bg_2'><td class='center'>";
            echo "<a href='$target?next=extauth_mail&amp;id=" . $mail_method["id"] . "' >";
            echo $mail_method["name"] . "</a></td>";
            echo "<td class='center'>" . $mail_method["host"] . "</td></tr>\n";
         }
      }
      echo "</table>\n";
   } else {
      echo '<input type="hidden" name="IMAP_Test" value="1" >';
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>" . $LANG['setup'][162] . "</th></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'><p class='red'>" . $LANG['setup'][165] . "</p>";
      echo "<p>" . $LANG['setup'][166] . "</p></td></tr></table>\n";
   }
   echo "</div>";
}

function showMailServerConfig($value) {
   global $LANG;

   if (!haveRight("config", "w")) {
      return false;
   }
   if (strstr($value,":")) {
      $addr = str_replace("{", "", preg_replace("/:.*/", "", $value));
      $port = preg_replace("/.*:/", "", preg_replace("/\/.*/", "", $value));
   } else {
      if (strstr($value,"/")) {
         $addr = str_replace("{", "", preg_replace("/\/.*/", "", $value));
      } else {
         $addr = str_replace("{", "", preg_replace("/}.*/", "", $value));
      }
      $port = "";
   }
   $mailbox = preg_replace("/.*}/", "", $value);

   echo "<tr class='tab_bg_1'><td>" . $LANG['common'][52] . "&nbsp;:</td>";
   echo "<td><input size='30' type='text' name='mail_server' value=\"" . $addr . "\" ></td></tr>\n";

   echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][168] . "&nbsp;:</td><td>";
   echo "<select name='server_type'>";
   echo "<option value=''>&nbsp;</option>\n";
   echo "<option value='/imap' " .(strstr($value,"/imap") ? " selected " : "") . ">IMAP</option>\n";
   echo "<option value='/pop' " .(strstr($value,"/pop") ? " selected " : "") . ">POP</option>\n";
   echo "</select>&nbsp;";

   echo "<select name='server_ssl'>";
   echo "<option value=''>&nbsp;</option>\n";
   echo "<option value='/ssl' " .(strstr($value,"/ssl") ? " selected " : "") . ">SSL</option>\n";
   echo "</select>&nbsp;";

   echo "<select name='server_tls'>";
   echo "<option value=''>&nbsp;</option>\n";
   echo "<option value='/tls' " .(strstr($value,"/tls") ? " selected " : "") . ">TLS</option>\n";
   echo "<option value='/notls' " .(strstr($value,"/notls") ? " selected " : "").">NO-TLS</option>\n";
   echo "</select>&nbsp;";

   echo "<select name='server_cert'>";
   echo "<option value=''>&nbsp;</option>\n";
   echo "<option value='/novalidate-cert' " .(strstr($value,"/novalidate-cert") ? " selected " : "") .
          ">NO-VALIDATE-CERT</option>\n";
   echo "<option value='/validate-cert' " .(strstr($value,"/validate-cert") ? " selected " : "") .
          ">VALIDATE-CERT</option>\n";
   echo "</select>\n";

   echo "<input type=hidden name=imap_string value='".$value."'>";
   echo "</td></tr>\n";

   echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][169] . "&nbsp;:</td>";
   echo "<td><input size='30' type='text' name='server_mailbox' value=\"" . $mailbox . "\" >";
   echo "</td></tr>\n";
   echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][171] . "&nbsp;:</td>";
   echo "<td><input size='10' type='text' name='server_port' value='$port'></td></tr>\n";
   if (empty ($value)) {
      $value = "&nbsp;";
   }
   echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][170] . "&nbsp;:</td>";
   echo "<td><strong>$value</strong></td></tr>\n";
}

function constructMailServerConfig($input) {

   $out = "";
   if (isset ($input['mail_server']) && !empty ($input['mail_server'])) {
      $out .= "{" . $input['mail_server'];
   } else {
      return $out;
   }
   if (isset ($input['server_port']) && !empty ($input['server_port'])) {
      $out .= ":" . $input['server_port'];
   }
   if (isset ($input['server_type'])) {
      $out .= $input['server_type'];
   }
   if (isset ($input['server_ssl'])) {
      $out .= $input['server_ssl'];
   }
   if (isset ($input['server_cert'])
       && (!empty($input['server_ssl']) || !empty($input['server_tls']))) {
      $out .= $input['server_cert'];
   }
   if (isset ($input['server_tls'])) {
      $out .= $input['server_tls'];
   }
   $out .= "}";
   if (isset ($input['server_mailbox'])) {
      $out .= $input['server_mailbox'];
   }

   return $out;
}

/*
 * Display a HTML report about systeme information / configuration
 *
 */
function showSystemInformations () {
   global $DB,$LANG,$CFG_GLPI;

   $width=128;

   echo "<div class='center' id='tabsbody'>";
   echo "<table class='tab_cadre_fixe'>";
   echo "<tr><th>" . $LANG['setup'][721] . "</th></tr>";
   echo "<tr class='tab_bg_1'><td><pre>[code]\n&nbsp;\n";

   echo "GLPI ".$CFG_GLPI['version']." (".$CFG_GLPI['root_doc']." => ".
         dirname(dirname($_SERVER["SCRIPT_FILENAME"])).")\n";

   echo "\n</pre></td></tr><tr><th>" . $LANG['common'][52] . "</th></tr>\n";
   echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";

   echo wordwrap($LANG['setup'][5]."&nbsp;: ".php_uname()."\n", $width, "\n\t");
   $exts = get_loaded_extensions();
   sort($exts);
   echo wordwrap("PHP ".phpversion()." (".implode(', ',$exts).")\n", $width, "\n\t");
   $msg = $LANG['common'][12].": ";
   foreach (array('memory_limit',
                  'max_execution_time',
                  'safe_mode') as $key) {
      $msg.= $key.'="'.ini_get($key).'" ';
   }
   echo wordwrap($msg."\n", $width, "\n\t");

   $msg = $LANG['Menu'][4].": ";
   if (isset($_SERVER["SERVER_SOFTWARE"])) {
      $msg .= $_SERVER["SERVER_SOFTWARE"];
   }
   if (isset($_SERVER["SERVER_SIGNATURE"])) {
      $msg .= ' ('.html_clean($_SERVER["SERVER_SIGNATURE"]).')';
   }
   echo wordwrap($msg."\n", $width, "\n\t");

   if (isset($_SERVER["HTTP_USER_AGENT"])) {
      echo "\t" . $_SERVER["HTTP_USER_AGENT"] . "\n";
   }

   $version = "???";
   foreach ($DB->request('SELECT VERSION() as ver') as $data) {
      $version = $data['ver'];
   }
   echo "MySQL: $version (".$DB->dbuser."@".$DB->dbhost."/".$DB->dbdefault.")\n";

   echo "\n</pre></td></tr><tr class='tab_bg_2'><th>" . $LANG['plugins'][0] . "</th></tr>";
   echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";

   $plug = new Plugin();
   $pluglist=$plug->find("","name, directory");
   foreach ($pluglist as $plugin) {
      $msg = substr(str_pad($plugin['directory'],30),0,16)." ".$LANG['common'][16].":".
             utf8_substr(str_pad($plugin['name'],40),0,30)." ";
      $msg .= $LANG['rulesengine'][78]."&nbsp;:".str_pad($plugin['version'],10)." ";
      $msg .= $LANG['joblist'][0]."&nbsp;:";
      switch ($plugin['state']) {
         case PLUGIN_NEW :
            $msg .=  $LANG['joblist'][9];
            break;

         case PLUGIN_ACTIVATED :
            $msg .=  $LANG['setup'][192];
            break;

         case PLUGIN_NOTINSTALLED :
            $msg .=  $LANG['plugins'][1];
            break;

         case PLUGIN_TOBECONFIGURED :
            $msg .=  $LANG['plugins'][2];
            break;

         case PLUGIN_NOTACTIVATED :
            $msg .=  $LANG['plugins'][3];
            break;

         case PLUGIN_TOBECLEANED :
         default :
            $msg .=  $LANG['plugins'][4];
            break;
      }
      echo wordwrap("\t".$msg."\n", $width, "\n\t\t");
   }
   echo "\n</pre></td></tr>";

   $ldap_servers = getLdapServers ();

   if (!empty($ldap_servers)) {
      echo "\n</pre></td><tr class='tab_bg_2'><th>" . $LANG['login'][2] . "</th></tr>\n";
      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";
      foreach ($ldap_servers as $ID => $value) {
         $fields = array($LANG['common'][52]=>'host',
                         $LANG['setup'][172]=>'port',
                         $LANG['setup'][154]=>'basedn',
                         $LANG['setup'][159]=>'condition',
                         $LANG['setup'][155]=>'rootdn',
                         $LANG['setup'][180]=>'use_tls');
         $msg = '';
         $first = true;
         foreach($fields as $label => $field) {
            $msg .= (!$first?', ':'').$label.': '.($value[$field] != ''?'\''.$value[$field].
                     '\'':$LANG['common'][49]);
            $first = false;
         }
         echo wordwrap($msg."\n", $width, "\n\t\t");
      }
   }

   echo "\n</pre></td></tr><tr class='tab_bg_2'><th>" . $LANG['setup'][704] .
      " / ". $LANG['mailgate'][0] ."</th></tr>\n";
   echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";

   $msg = $LANG['setup'][231].": ";
   switch($CFG_GLPI['smtp_mode']) {
      case MAIL_MAIL :
         $msg .= $LANG['setup'][650];
         break;

      case MAIL_SMTP :
         $msg .= $LANG['setup'][651];
         break;

      case MAIL_SMTPSSL :
         $msg .= $LANG['setup'][652];
         break;

      case MAIL_SMTPTLS :
         $msg .= $LANG['setup'][653];
         break;
   }
   if ($CFG_GLPI['smtp_mode'] != MAIL_MAIL) {
      $msg .= " (".(empty($CFG_GLPI['smtp_username'])?'':$CFG_GLPI['smtp_username']."@").
                 $CFG_GLPI['smtp_host'].")";
   }
   echo wordwrap($msg."\n", $width, "\n\t\t");

   echo $LANG['mailgate'][0]."\n";
   foreach ($DB->request('glpi_mailcollectors') as $mc) {
      $msg = "\t".$LANG['common'][16].':"'.$mc['name'].'"  ';
      $msg .= " ".$LANG['common'][52].':'.$mc['host'];
      $msg .= " ".$LANG['login'][6].':"'.$mc['login'].'"';
      $msg .= " ".$LANG['login'][7].':'.(empty($mc['password'])?$LANG['choice'][0]:$LANG['choice'][1]);
      $msg .= " ".$LANG['common'][60].':'.($mc['is_active']?$LANG['choice'][1]:$LANG['choice'][0]);
      echo wordwrap($msg."\n", $width, "\n\t\t");
   }

   echo "\n[/code]\n</pre></td></tr><tr class='tab_bg_2'><th>" . $LANG['setup'][722] . "</th></tr>\n";
   echo "</tr>\n";
   echo "</table></div>\n";
}

?>
