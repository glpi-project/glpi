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

         openArrowMassive("massiveaction_form", true);
         closeArrowMassive('mass_delete',
                           $LANG['buttons'][6]."&nbsp;<strong>".$LANG['setup'][1]."</strong>");

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
      Netpoint::dropdown("id", $ID, $locations_id, 0, $entity_restrict);

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
      case "glpi_operatingsystemservicepacks" :
      case "glpi_operatingsystemversions" :
      case "glpi_computertypes" :
      case "glpi_monitortypes" :
      case "glpi_printertypes" :
      case "glpi_peripheraltypes" :
      case "glpi_phonetypes" :
      case "glpi_networkequipmenttypes" :
         $process = true;
         break;

      case "glpi_computermodels" :
      case "glpi_monitormodels" :
      case "glpi_printermodels" :
      case "glpi_peripheralmodels" :
      case "glpi_phonemodels" :
      case "glpi_networkequipmentmodels" :
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
                FROM `glpi_entitydatas`
                WHERE `entities_id` = '" . $input["oldID"] . "'";
      $DB->query($query);

      $di = new Document_Item();
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

      if ($table == "glpi_knowbaseitemcategories") {
         $query = "SELECT COUNT(*) AS cpt
                   FROM `glpi_knowbaseitems`
                   WHERE `knowbaseitemcategories_id` = '$ID'";
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
         $query .= "FROM `glpi_phones`
                    $where
                        $whereentity
                    $order";
         break;

      case OCSNG_TYPE :
         $title = $LANG['Menu'][33];
         $query .= "FROM `glpi_ocsservers`
                    $where
                    $order";
         break;

      case BUDGET_TYPE :
         $title = $LANG['financial'][87];
         $query .= "FROM `glpi_budgets`
                    $where
                        $whereentity
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

// TODO : use common search engine
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
            echo "<a href='$target?id=" . $mail_method["id"] . "' >";
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
?>
