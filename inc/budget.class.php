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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Budget class
 */
class Budget extends CommonDropdown{

   // From CommonDBTM
   public $dohistory = true;

   static function getTypeName() {
      global $LANG;

      return $LANG['financial'][87];
   }


   function canCreate() {
      return haveRight('budget', 'w');
   }


   function canView() {
      return haveRight('budget', 'r');
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $ong[1] = $LANG['title'][26];

      if ($this->fields['id'] > 0) {
         if (haveRight("document","r")) {
            $ong[5] = $LANG['Menu'][27];
         }
         if (!isset($options['withtemplate']) || empty($options['withtemplate'])) {
            $ong[2] = $LANG['common'][96];
            if (haveRight("link","r")) {
               $ong[7] = $LANG['title'][34];
            }
            if (haveRight("notes","r")) {
               $ong[10] = $LANG['title'][37];
            }
            $ong[12] = $LANG['title'][38];
         }
      }

      return $ong;
   }


   /**
    * Print the contact form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target for the Form
    *     - withtemplate : template or basic item
    *
    *@return Nothing (display)
    **/
   function showForm ($ID, $options=array()) {
      global $LANG;

      if (!haveRight("budget","r")) return false;

      $use_cache = true;

      $rowspan = 4;
      if ($ID > 0) {
         $rowspan++;
         $this->check($ID, 'r');
      } else {
         // Create item
         $this->check(-1, 'w');
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;: </td>";
      echo "<td>";
      autocompletionTextField($this, "name");
      echo "</td>";

      echo "<td rowspan='$rowspan' class='middle right'>".$LANG['common'][25]."&nbsp;: </td>";
      echo "<td class='center middle' rowspan='$rowspan'><textarea cols='45' rows='4'
             name='comment' >".$this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][21]."&nbsp;:</td>";
      echo "<td><input type='text' name='value' size='14'
             value='".formatNumber($this->fields["value"], true)."'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['search'][8]."&nbsp;: </td>";
      echo "<td>";
      showDateFormItem("begin_date", $this->fields["begin_date"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['search'][9]."&nbsp;: </td>";
      echo "<td>";
      showDateFormItem("end_date",$this->fields["end_date"]);
      echo "</td></tr>";

      if ($ID>0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['common'][26]."&nbsp;: </td>";
         echo "<td>";
         echo ($this->fields["date_mod"]?convDateTime($this->fields["date_mod"]):$LANG['setup'][307]);
         echo "</td></tr>";
      }

      $this->showFormButtons($options);
      $this->addDivForTabs();
      return true;
   }


   function prepareInputForAdd($input) {

      if (isset($input["id"])&&$input["id"]>0) {
         $input["_oldID"] = $input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;
   }


   function post_addItem() {
      global $DB;

      // Manage add from template
      if (isset($this->input["_oldID"])) {
         // ADD Documents
         $query = "SELECT `documents_id`
                   FROM `glpi_documents_items`
                   WHERE `items_id` = '".$this->input["_oldID"]."'
                         AND `itemtype` = '".$this->getType()."';";
         $result = $DB->query($query);

         if ($DB->numrows($result)>0) {
            $docitem = new Document_Item();
            while ($data=$DB->fetch_array($result)) {
               $docitem->add(array('documents_id' => $data["documents_id"],
                                   'itemtype'     => $this->getType(),
                                   'items_id'     => $this->fields['id']));
            }
         }
      }
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'date_mod';
      $tab[19]['name']          = $LANG['common'][26];
      $tab[19]['datatype']      = 'datetime';
      $tab[19]['massiveaction'] = false;

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'begin_date';
      $tab[2]['name']      = $LANG['search'][8];
      $tab[2]['datatype']  = 'date';

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'end_date';
      $tab[3]['name']      = $LANG['search'][9];
      $tab[3]['datatype']  = 'date';

      $tab[4]['table']     = $this->getTable();
      $tab[4]['field']     = 'value';
      $tab[4]['name']      = $LANG['financial'][21];
      $tab[4]['datatype']  = 'decimal';

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[80]['table']         = 'glpi_entities';
      $tab[80]['field']         = 'completename';
      $tab[80]['name']          = $LANG['entity'][0];
      $tab[80]['massiveaction'] = false;

      $tab[86]['table']     = $this->getTable();
      $tab[86]['field']     = 'is_recursive';
      $tab[86]['name']      = $LANG['entity'][9];
      $tab[86]['datatype']  = 'bool';

      return $tab;
   }


   /**
   * Print the HTML array of Items on a budget
   *
   *@return Nothing (display)
   **/
   function showItems() {
      global $DB, $LANG;

      $budgets_id = $this->fields['id'];

      if (!$this->can($budgets_id,'r')) {
         return false;
      }

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_infocoms`
                WHERE `budgets_id` = '$budgets_id'
                      AND itemtype NOT IN ('ConsumableItem', 'CartridgeItem', 'Software')
               ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      echo "<div class='spaced'><table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='2'>";
      printPagerForm();
      echo "</th><th colspan='4'>";
      if ($DB->numrows($result)==0) {
         echo $LANG['document'][13];
      } else {
         echo $LANG['document'][19];
      }
      echo "</th></tr>";

      echo "<tr><th>".$LANG['common'][17]."</th>";
      echo "<th>".$LANG['entity'][0]."</th>";
      echo "<th>".$LANG['common'][16]."</th>";
      echo "<th>".$LANG['common'][19]."</th>";
      echo "<th>".$LANG['common'][20]."</th>";
      echo "<th>".$LANG['financial'][21]."</th>";
      echo "</tr>";

      $num = 0;
      for ($i = 0; $i < $number ; $i++) {
         $itemtype = $DB->result($result, $i, "itemtype");

         if (!class_exists($itemtype)) {
            continue;
         }
         $item = new $itemtype();
         if ($item->canView()) {
            switch ($itemtype) {
               default :
                  $query = "SELECT `".$item->getTable()."`.*,
                                   `glpi_infocoms`.`value`
                            FROM `glpi_infocoms`
                            INNER JOIN `".$item->getTable()."`
                                 ON (`".$item->getTable()."`.`id` = `glpi_infocoms`.`items_id`)
                            WHERE `glpi_infocoms`.`itemtype` = '$itemtype'
                                  AND `glpi_infocoms`.`budgets_id` = '$budgets_id' ".
                                  getEntitiesRestrictRequest(" AND", $item->getTable())."
                            ORDER BY `entities_id`,
                                     `".$item->getTable()."`.`name`";
               break;

               case 'Cartridge':
                  $query = "SELECT `".$item->getTable()."`.*,
                                   `glpi_cartridgeitems`.`name`
                            FROM `glpi_infocoms`
                            INNER JOIN `".$item->getTable()."`
                                 ON (`".$item->getTable()."`.`id` = `glpi_infocoms`.`items_id`)
                            INNER JOIN `glpi_cartridgeitems`
                                 ON (`".$item->getTable()."`.`cartridgeitems_id` = `glpi_cartridgeitems`.`id`)
                            WHERE `glpi_infocoms`.`itemtype`='$itemtype'
                                  AND `glpi_infocoms`.`budgets_id` = '$budgets_id' ".
                                  getEntitiesRestrictRequest(" AND", $item->getTable())."
                            ORDER BY `entities_id`,
                                     `glpi_cartridgeitems`.`name`";
               break;

               case 'Consumable':
                  $query = "SELECT `".$item->getTable()."`.*,
                                   `glpi_consumableitems`.`name`
                            FROM `glpi_infocoms`
                            INNER JOIN `".$item->getTable()."`
                                 ON (`".$item->getTable()."`.`id` = `glpi_infocoms`.`items_id`)
                            INNER JOIN `glpi_consumableitems`
                                 ON (`".$item->getTable()."`.`consumableitems_id` = `glpi_consumableitems`.`id`)
                            WHERE `glpi_infocoms`.`itemtype` = '$itemtype'
                                  AND `glpi_infocoms`.`budgets_id` = '$budgets_id' ".
                                  getEntitiesRestrictRequest(" AND", $item->getTable())."
                            ORDER BY `entities_id`,
                                     `glpi_consumableitems`.`name`";
               break;
            }

            if ($result_linked=$DB->query($query)) {
               $nb = $DB->numrows($result_linked);

               if ($nb>$_SESSION['glpilist_limit']) {
                  echo "<tr class='tab_bg_1'>";
                  echo "<td class='center'>".$item->getTypeName($nb)."&nbsp;:&nbsp;$nb</td>";
                  echo "<td class='center' colspan='2'>";
                  echo "<a href='". $item->getSearchURL() . "?" .
                        rawurlencode("contains[0]") . "=" . rawurlencode('$$$$'.$budgets_id) . "&" .
                        rawurlencode("field[0]") . "=50&sort=80&order=ASC&is_deleted=0&start=0". "'>" .
                        $LANG['reports'][57]."</a></td>";
                  echo "<td class='center'>-</td><td class='center'>-</td><td class='center'>-</td></tr>";

               } else if ($nb) {
                  for ($prem=true ; $data=$DB->fetch_assoc($result_linked) ; $prem=false) {
                     $ID = "";
                     if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                        $ID = " (".$data["id"].")";
                     }
                     $name = NOT_AVAILABLE;
                     if ($item->getFromDB($data["id"])) {
                        $name = $item->getLink();
                     }
                     echo "<tr class='tab_bg_1'>";
                     if ($prem) {
                        echo "<td class='center top' rowspan='$nb'>".$item->getTypeName($nb)
                              .($nb>1?"&nbsp;:&nbsp;$nb</td>":"</td>");
                     }
                     echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",
                                                                          $data["entities_id"]);
                     echo "</td><td class='center";
                     echo (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
                     echo ">".$name."</td>";
                     echo "<td class='center'>".(isset($data["serial"])? "".$data["serial"]."" :"-");
                     echo "</td>";
                     echo "<td class='center'>".
                            (isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
                     echo "<td class='center'>".
                            (isset($data["value"])? "".formatNumber($data["value"],true)."" :"-");

                     echo "</td></tr>";
                  }
               }
            $num += $nb;
            }
         }
      }
      if ($num>0) {
         echo "<tr class='tab_bg_2'><td class='center b'>".$LANG['common'][33]."&nbsp;:&nbsp;$num</td><td colspan='5'>&nbsp;</td></tr> ";
      }
      echo "</table></div>";
   }


   /**
   * Print the HTML array of value consumed for a budget
   *
   *@return Nothing (display)
   **/
   function showValuesByEntity() {
      global $DB, $LANG;

      $budgets_id = $this->fields['id'];

      if (!$this->can($budgets_id, 'r')) {
         return false;
      }

      // Type for which infocom are only template
      $ignore = array('CartridgeItem', 'ConsumableItem', 'Software');

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_infocoms`
                WHERE `budgets_id` = '$budgets_id'
                     AND `itemtype` NOT IN ('".implode("','",$ignore)."')".
                     getEntitiesRestrictRequest(" AND", 'glpi_infocoms', "entities_id")."
                GROUP BY `itemtype`";

      $result = $DB->query($query);
      $total = 0;

      $entities_values = array();
      $entitiestype_values = array();
      $found_types = array();

      if ($DB->numrows($result)) {
         while ($types = $DB->fetch_array($result)) {
            if (!class_exists($types['itemtype'])) {
               continue;
            }
            $item = new $types['itemtype']();
            $found_types[$types['itemtype']] = $item->getTypeName();
            $table = getTableForItemType($types['itemtype']);
            $query_infos = "SELECT SUM(`glpi_infocoms`.`value`) AS `sumvalue`,
                                   `$table`.`entities_id`
                            FROM `$table`
                            INNER JOIN `glpi_infocoms`
                                 ON (`glpi_infocoms`.`items_id` = `$table`.`id`
                                     AND `glpi_infocoms`.`itemtype` = '".$types['itemtype']."')
                            LEFT JOIN `glpi_entities`
                                 ON (`$table`.`entities_id` = `glpi_entities`.`id`)
                            WHERE `glpi_infocoms`.`budgets_id` = '$budgets_id' ".
                                  getEntitiesRestrictRequest(" AND", $table, "entities_id");

            if ($item->maybeTemplate()) {
               $query_infos .= " AND `$table`.`is_template` = '0' ";
            }
            $query_infos .= "GROUP BY `$table`.`entities_id`
                             ORDER BY `glpi_entities`.`completename` ASC";

            if ($result_infos = $DB->query($query_infos)) {
               //Store, for each entity, the budget spent
               while ($values = $DB->fetch_array($result_infos)) {
                  if (!isset($entities_values[$values['entities_id']])) {
                     $entities_values[$values['entities_id']] = 0;
                  }
                  if (!isset($entitiestype_values[$values['entities_id']][$types['itemtype']])) {
                     $entitiestype_values[$values['entities_id']][$types['itemtype']] = 0;
                  }
                  $entities_values[$values['entities_id']] += $values['sumvalue'];
                  $entitiestype_values[$values['entities_id']][$types['itemtype']] += $values['sumvalue'];
               }
            }

         }

         $budget = new Budget();
         $budget->getFromDB($budgets_id);

         $colspan = count($found_types)+2;
         echo "<div class='spaced'><table class='tab_cadre'>";
         echo "<tr><th colspan='$colspan'>".$LANG['financial'][108]."</th></tr>";
         echo "<tr><th>".$LANG['entity'][0]."</th>";
         if (count($found_types)) {
            foreach ($found_types as $type => $typename) {
               echo "<th>$typename</th>";
            }
         }
         echo "<th>".$LANG['common'][33]."</th>";
         echo "</tr>";

         foreach ($entities_values as $entity => $value) {
            echo "<tr class='tab_bg_1'>";
            echo "<td class='b'>".Dropdown::getDropdownName('glpi_entities',$entity)."</td>";
            if (count($found_types)) {
               foreach ($found_types as $type => $typename) {
                  echo "<td class='right'>";
                  $typevalue = 0;
                  if (isset($entitiestype_values[$entity][$type])) {
                     $typevalue = $entitiestype_values[$entity][$type];
                  }
                  echo formatNumber($typevalue);
                  echo "</td>";
               }
            }

            echo "<td class='right b'>".formatNumber($value)."</td>";
            echo "</tr>";
            $total += $value;
         }

         echo "<tr class='tab_bg_1'><th colspan='$colspan'><br></th></tr>";
         echo "<tr class='tab_bg_1'>";
         echo "<td class='right' colspan='".($colspan-1)."'>".$LANG['financial'][108]."</td>";
         echo "<td class='right b'>".formatNumber($total)."</td></tr>";
         if ($_SESSION['glpiactive_entity'] == $budget->fields['entities_id']) {
            echo "<tr class='tab_bg_1'>";
            echo "<td class='right' colspan='".($colspan-1)."'>".$LANG['financial'][109]."</td>";
            echo "<td class='right b'>".formatNumber($budget->fields['value'] - $total)."</td></tr>";
         }
         echo "</table></div>";

      }
   }

}

?>