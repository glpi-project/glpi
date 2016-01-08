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
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Budget class
 */
class Budget extends CommonDropdown{

   // From CommonDBTM
   public $dohistory           = true;

   static $rightname           = 'budget';
   protected $usenotepad       = true;

   var $can_be_translated = false;


   static function getTypeName($nb=0) {
      return _n('Budget', 'Budgets', $nb);
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__,$ong, $options);
      $this->addStandardTab('Document_Item',$ong, $options);
      $this->addStandardTab('Link',$ong, $options);
      $this->addStandardTab('Notepad',$ong, $options);
      $this->addStandardTab('Log',$ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               return array(1 => __('Main'),
                            2 => _n('Item', 'Items', Session::getPluralNumber()));
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 :
               $item->showValuesByEntity();
               break;

            case 2 :
               $item->showItems();
               break;
         }
      }
      return true;
   }


   /**
    * Print the contact form
    *
    * @param $ID        integer ID of the item
    * @param $options   array of possible options:
    *     - target for the Form
    *     - withtemplate : template or basic item
    *
    * @return Nothing (display)
    **/
   function showForm($ID, $options=array()) {

      $rowspan = 4;
      if ($ID > 0) {
         $rowspan++;
      }

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";

      echo "<td rowspan='$rowspan' class='middle right'>".__('Comments')."</td>";
      echo "<td class='center middle' rowspan='$rowspan'>".
           "<textarea cols='45' rows='4' name='comment' >".$this->fields["comment"]."</textarea>".
           "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._x('price', 'Value')."</td>";
      echo "<td><input type='text' name='value' size='14'
                 value='".Html::formatNumber($this->fields["value"], true)."'></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Start date')."</td>";
      echo "<td>";
      Html::showDateField("begin_date", array('value' => $this->fields["begin_date"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('End date')."</td>";
      echo "<td>";
      Html::showDateField("end_date", array('value' => $this->fields["end_date"]));
      echo "</td></tr>";

      if ($ID > 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Last update')."</td>";
         echo "<td>";
         echo ($this->fields["date_mod"]? Html::convDateTime($this->fields["date_mod"])
                                        : __('Never'));
         echo "</td></tr>";
      }

      $this->showFormButtons($options);
      return true;
   }


   function prepareInputForAdd($input) {

      if (isset($input["id"]) && ($input["id"] > 0)) {
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
         Document_Item::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);
      }
   }


   function getSearchOptions() {

      $tab = array();
      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['massiveaction']   = false;

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false;
      $tab[2]['datatype']        = 'number';

      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'begin_date';
      $tab[5]['name']            = __('Start date');
      $tab[5]['datatype']        = 'date';

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'end_date';
      $tab[3]['name']            = __('End date');
      $tab[3]['datatype']        = 'date';

      $tab[4]['table']           = $this->getTable();
      $tab[4]['field']           = 'value';
      $tab[4]['name']            = _x('price', 'Value');
      $tab[4]['datatype']        = 'decimal';

      $tab[16]['table']          = $this->getTable();
      $tab[16]['field']          = 'comment';
      $tab[16]['name']           = __('Comments');
      $tab[16]['datatype']       = 'text';

      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['massiveaction']  = false;
      $tab[80]['datatype']       = 'dropdown';

      $tab[86]['table']          = $this->getTable();
      $tab[86]['field']          = 'is_recursive';
      $tab[86]['name']           = __('Child entities');
      $tab[86]['datatype']       = 'bool';

      $tab += Notepad::getSearchOptionsToAdd();

      return $tab;
   }


   /**
    * Print the HTML array of Items on a budget
    *
    * @return Nothing (display)
   **/
   function showItems() {
      global $DB;

      $budgets_id = $this->fields['id'];

      if (!$this->can($budgets_id, READ)) {
         return false;
      }

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_infocoms`
                WHERE `budgets_id` = '$budgets_id'
                      AND itemtype NOT IN ('ConsumableItem', 'CartridgeItem', 'Software')
               ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      echo "<div class='spaced'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>";
      Html::printPagerForm();
      echo "</th><th colspan='4'>";
      if ($DB->numrows($result) == 0) {
         _e('No associated item');
      } else {
         echo _n('Associated item', 'Associated items', $DB->numrows($result));
      }
      echo "</th></tr>";

      echo "<tr><th>".__('Type')."</th>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Serial number')."</th>";
      echo "<th>".__('Inventory number')."</th>";
      echo "<th>"._x('price', 'Value')."</th>";
      echo "</tr>";

      $num       = 0;
      $itemtypes = array();
      for ($i = 0; $i < $number ; $i++) {
         $itemtypes[] = $DB->result($result, $i, "itemtype");
      }
      $itemtypes[] = 'Contract';
      $itemtypes[] = 'Ticket';
      $itemtypes[] = 'Problem';
      $itemtypes[] = 'Change';
      $itemtypes[] = 'Project';

      foreach ($itemtypes as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         if ($item->canView()) {
            switch ($itemtype) {

               case 'Contract' :
                  $query = "SELECT `".$item->getTable()."`.`id`,
                                   `".$item->getTable()."`.`entities_id`,
                                    SUM(`glpi_contractcosts`.`cost`) as value
                            FROM `glpi_contractcosts`
                            INNER JOIN `".$item->getTable()."`
                                 ON (`".$item->getTable()."`.`id` = `glpi_contractcosts`.`contracts_id`)
                            WHERE `glpi_contractcosts`.`budgets_id` = '$budgets_id' ".
                                  getEntitiesRestrictRequest(" AND", $item->getTable())."
                                  AND NOT `".$item->getTable()."`.`is_template`
                            GROUP BY `".$item->getTable()."`.`id`, `".$item->getTable()."`.`entities_id`
                            ORDER BY `".$item->getTable()."`.`entities_id`,
                                     `".$item->getTable()."`.`name`";
               break;

               case 'Ticket' :
               case 'Problem' :
               case 'Change' :
                  $costtable = getTableForItemType($item->getType().'Cost');
                  $query = "SELECT `".$item->getTable()."`.`id`,
                                   `".$item->getTable()."`.`entities_id`,
                                    SUM(`$costtable`.`actiontime`*`$costtable`.`cost_time`/".HOUR_TIMESTAMP."
                                          + `$costtable`.`cost_fixed`
                                          + `$costtable`.`cost_material`) as value
                            FROM `$costtable`
                            INNER JOIN `".$item->getTable()."`
                                 ON (`".$item->getTable()."`.`id` = `$costtable`.`".$item->getForeignKeyField()."`)
                            WHERE `$costtable`.`budgets_id` = '$budgets_id' ".
                                  getEntitiesRestrictRequest(" AND", $item->getTable())."
                            GROUP BY `".$item->getTable()."`.`id`, `".$item->getTable()."`.`entities_id`
                            ORDER BY `".$item->getTable()."`.`entities_id`,
                                     `".$item->getTable()."`.`name`";
               break;

               case 'Project' :
                  $query = "SELECT `".$item->getTable()."`.`id`,
                                   `".$item->getTable()."`.`entities_id`,
                                    SUM(`glpi_projectcosts`.`cost`) as value
                            FROM `glpi_projectcosts`
                            INNER JOIN `".$item->getTable()."`
                                 ON (`".$item->getTable()."`.`id` = `glpi_projectcosts`.`projects_id`)
                            WHERE `glpi_projectcosts`.`budgets_id` = '$budgets_id' ".
                                  getEntitiesRestrictRequest(" AND", $item->getTable())."
                            GROUP BY `".$item->getTable()."`.`id`, `".$item->getTable()."`.`entities_id`
                            ORDER BY `".$item->getTable()."`.`entities_id`,
                                     `".$item->getTable()."`.`name`";
                                                break;

               case 'Cartridge' :
                  $query = "SELECT `".$item->getTable()."`.*,
                                   `glpi_cartridgeitems`.`name`,
                                   `glpi_infocoms`.`value`
                            FROM `glpi_infocoms`
                            INNER JOIN `".$item->getTable()."`
                                 ON (`".$item->getTable()."`.`id` = `glpi_infocoms`.`items_id`)
                            INNER JOIN `glpi_cartridgeitems`
                                 ON (`".$item->getTable()."`.`cartridgeitems_id`
                                       = `glpi_cartridgeitems`.`id`)
                            WHERE `glpi_infocoms`.`itemtype`='$itemtype'
                                  AND `glpi_infocoms`.`budgets_id` = '$budgets_id' ".
                                  getEntitiesRestrictRequest(" AND", $item->getTable())."
                            ORDER BY `entities_id`,
                                     `glpi_cartridgeitems`.`name`";
               break;

               case 'Consumable' :
                  $query = "SELECT `".$item->getTable()."`.*,
                                   `glpi_consumableitems`.`name`,
                                   `glpi_infocoms`.`value`
                            FROM `glpi_infocoms`
                            INNER JOIN `".$item->getTable()."`
                                 ON (`".$item->getTable()."`.`id` = `glpi_infocoms`.`items_id`)
                            INNER JOIN `glpi_consumableitems`
                                 ON (`".$item->getTable()."`.`consumableitems_id`
                                       = `glpi_consumableitems`.`id`)
                            WHERE `glpi_infocoms`.`itemtype` = '$itemtype'
                                  AND `glpi_infocoms`.`budgets_id` = '$budgets_id' ".
                                  getEntitiesRestrictRequest(" AND", $item->getTable())."
                            ORDER BY `entities_id`,
                                     `glpi_consumableitems`.`name`";
               break;

               default :
                  $query = "SELECT `".$item->getTable()."`.*,
                                   `glpi_infocoms`.`value`
                            FROM `glpi_infocoms`
                            INNER JOIN `".$item->getTable()."`
                                 ON (`".$item->getTable()."`.`id` = `glpi_infocoms`.`items_id`)
                            WHERE `glpi_infocoms`.`itemtype` = '$itemtype'
                                  AND `glpi_infocoms`.`budgets_id` = '$budgets_id' ".
                                  getEntitiesRestrictRequest(" AND", $item->getTable())."
                                  ".($item->maybeTemplate()?" AND NOT `".$item->getTable()."`.`is_template`":'')."
                            ORDER BY `".$item->getTable()."`.`entities_id`,";
                if ($item instanceof Item_Devices) {
                   $query .= " `".$item->getTable()."`.`itemtype`";
                } else {
                   $query .= " `".$item->getTable()."`.`name`";
                }


               break;
            }

            if ($result_linked = $DB->query($query)) {
               $nb = $DB->numrows($result_linked);
               if ($nb > $_SESSION['glpilist_limit']) {
                  echo "<tr class='tab_bg_1'>";
                  $name = $item->getTypeName($nb);
                  //TRANS: %1$s is a name, %2$s is a number
                  echo "<td class='center'>".sprintf(__('%1$s: %2$s'), $name, $nb)."</td>";
                  echo "<td class='center' colspan='2'>";

                  $opt = array('order'      => 'ASC',
                               'is_deleted' => 0,
                               'reset'      => 'reset',
                               'start'      => 0,
                               'sort'       => 80,
                               'criteria'   => array(0 => array('value'      => '$$$$'.$budgets_id,
                                                                'searchtype' => 'contains',
                                                                'field'      => 50)));

                  echo "<a href='". $item->getSearchURL() . "?" .Toolbox::append_params($opt). "'>".
                        __('Device list')."</a></td>";
                  echo "<td class='center'>-</td><td class='center'>-</td><td class='center'>-".
                       "</td></tr>";

               } else if ($nb) {
                  for ($prem=true ; $data=$DB->fetch_assoc($result_linked) ; $prem=false) {
                     $name = NOT_AVAILABLE;
                     if ($item->getFromDB($data["id"])) {
                        if ($item instanceof Item_Devices) {
                           $tmpitem = new $item::$itemtype_2();
                           if ($tmpitem->getFromDB($data[$item::$items_id_2])) {
                              $name = $tmpitem->getLink(array('additional' => true));
                           }
                        } else {
                           $name = $item->getLink(array('additional' => true));
                        }
                     }
                     echo "<tr class='tab_bg_1'>";
                     if ($prem) {
                        $typename = $item->getTypeName($nb);
                        echo "<td class='center top' rowspan='$nb'>".
                              ($nb>1 ? sprintf(__('%1$s: %2$s'), $typename, $nb) : $typename)."</td>";
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
                            (isset($data["value"]) ? "".Html::formatNumber($data["value"], true).""
                                                   :"-");

                     echo "</td></tr>";
                  }
               }
            $num += $nb;
            }
         }
      }

      if ($num>0) {
         echo "<tr class='tab_bg_2'>";
         echo "<td class='center b'>".sprintf(__('%1$s = %2$s'), __('Total'), $num)."</td>";
         echo "<td colspan='5'>&nbsp;</td></tr> ";
      }
      echo "</table></div>";
   }


   /**
    * Print the HTML array of value consumed for a budget
    *
    * @return Nothing (display)
   **/
   function showValuesByEntity() {
      global $DB;

      $budgets_id = $this->fields['id'];

      if (!$this->can($budgets_id, READ)) {
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

      $total               = 0;
      $totalbytypes        = array();

      $itemtypes           = array();

      $entities_values     = array();
      $entitiestype_values = array();
      $found_types         = array();

      if ($DB->numrows($result)) {
         while ($types = $DB->fetch_assoc($result)) {
            $itemtypes[] = $types['itemtype'];
         }
      }

      $itemtypes[] = 'Contract';
      $itemtypes[] = 'Ticket';
      $itemtypes[] = 'Problem';
      $itemtypes[] = 'Project';
      $itemtypes[] = 'Change';

      foreach ($itemtypes as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         $table = getTableForItemType($itemtype);
         switch ($itemtype) {
            case 'Contract' :
               $query_infos = "SELECT SUM(`glpi_contractcosts`.`cost`) AS `sumvalue`,
                                       `$table`.`entities_id`
                                FROM `glpi_contractcosts`
                                INNER JOIN `$table`
                                    ON (`glpi_contractcosts`.`contracts_id` = `$table`.`id`)
                                WHERE `glpi_contractcosts`.`budgets_id` = '$budgets_id' ".
                                      getEntitiesRestrictRequest(" AND", $table, "entities_id")."
                                      AND `$table`.`is_template` = '0'
                                GROUP BY `$table`.`entities_id`";
               break;

            case 'Project' :
               $costtable   = getTableForItemType($item->getType().'Cost');
               $query_infos = "SELECT SUM(`glpi_projectcosts`.`cost`) AS `sumvalue`,
                                       `$table`.`entities_id`
                               FROM `glpi_projectcosts`
                               INNER JOIN `$table`
                                    ON (`glpi_projectcosts`.`projects_id` = `$table`.`id`)
                               WHERE `glpi_projectcosts`.`budgets_id` = '$budgets_id' ".
                                      getEntitiesRestrictRequest(" AND", $table, "entities_id")."
                                GROUP BY `$table`.`entities_id`";
               break;

            case 'Ticket' :
            case 'Problem' :
            case 'Change' :
               $costtable   = getTableForItemType($item->getType().'Cost');
               $query_infos = "SELECT SUM(`$costtable`.`actiontime`*`$costtable`.`cost_time`/".HOUR_TIMESTAMP."
                                          + `$costtable`.`cost_fixed`
                                          + `$costtable`.`cost_material`) AS `sumvalue`,
                                       `$table`.`entities_id`
                                FROM `$costtable`
                                INNER JOIN `$table`
                                    ON (`$costtable`.`".$item->getForeignKeyField()."` = `$table`.`id`)
                                WHERE `$costtable`.`budgets_id` = '$budgets_id' ".
                                      getEntitiesRestrictRequest(" AND", $table, "entities_id")."
                                GROUP BY `$table`.`entities_id`";
               break;

            default :
               $query_infos = "SELECT SUM(`glpi_infocoms`.`value`) AS `sumvalue`,
                                       `$table`.`entities_id`
                                FROM `$table`
                                INNER JOIN `glpi_infocoms`
                                    ON (`glpi_infocoms`.`items_id` = `$table`.`id`
                                        AND `glpi_infocoms`.`itemtype` = '$itemtype')
                                WHERE `glpi_infocoms`.`budgets_id` = '$budgets_id' ".
                                      getEntitiesRestrictRequest(" AND", $table, "entities_id");

               if ($item->maybeTemplate()) {
                  $query_infos .= " AND `$table`.`is_template` = '0' ";
               }
               $query_infos .= "GROUP BY `$table`.`entities_id`";
            break;
         }

         if ($result_infos = $DB->query($query_infos)) {
            if ($DB->numrows($result_infos)) {
               $found_types[$itemtype]  = $item->getTypeName(1);
               $totalbytypes[$itemtype] = 0;
               //Store, for each entity, the budget spent
               while ($values = $DB->fetch_assoc($result_infos)) {

                  if (!isset($entities_values[$values['entities_id']])) {
                     $entities_values[$values['entities_id']] = 0;
                  }
                  if (!isset($entitiestype_values[$values['entities_id']][$itemtype])) {
                     $entitiestype_values[$values['entities_id']][$itemtype] = 0;
                  }
                  $entities_values[$values['entities_id']]                 += $values['sumvalue'];
                  $entitiestype_values[$values['entities_id']][$itemtype]  += $values['sumvalue'];
                  $total                                                   += $values['sumvalue'];
                  $totalbytypes[$itemtype]                                 += $values['sumvalue'];
               }
            }
         }

      }

      $budget = new self();
      $budget->getFromDB($budgets_id);

      $colspan = count($found_types)+2;
      echo "<div class='spaced'><table class='tab_cadre_fixehov'>";
      echo "<tr class='noHover'><th colspan='$colspan'>".__('Total spent on the budget')."</th></tr>";
      echo "<tr><th>".__('Entity')."</th>";
      if (count($found_types)) {
         foreach ($found_types as $type => $typename) {
            echo "<th>$typename</th>";
         }
      }
      echo "<th>".__('Total')."</th>";
      echo "</tr>";

      // get all entities ordered by names
      $allentities = getAllDatasFromTable('glpi_entities','',true, 'completename');

      foreach ($allentities as $entity => $data) {
         if (isset($entities_values[$entity])) {
            echo "<tr class='tab_bg_1'>";
            echo "<td class='b'>".Dropdown::getDropdownName('glpi_entities', $entity)."</td>";
            if (count($found_types)) {
               foreach ($found_types as $type => $typename) {
                  echo "<td class='numeric'>";
                  $typevalue = 0;
                  if (isset($entitiestype_values[$entity][$type])) {
                     $typevalue = $entitiestype_values[$entity][$type];
                  }
                  echo Html::formatNumber($typevalue);
                  echo "</td>";
               }
            }

            echo "<td class='right b'>".Html::formatNumber($entities_values[$entity])."</td>";
            echo "</tr>";
         }
      }
      if (count($found_types)) {
         echo "<tr class='tab_bg_1'>";
         echo "<td class='right b'>".__('Total')."</td>";
         foreach ($found_types as $type => $typename) {
            echo "<td class='numeric b'>";
            echo Html::formatNumber($totalbytypes[$type]);
            echo "</td>";
         }
         echo "<td class='numeric b'>".Html::formatNumber($total)."</td>";
         echo "</tr>";
      }
      echo "<tr class='tab_bg_1 noHover'><th colspan='$colspan'><br></th></tr>";
      echo "<tr class='tab_bg_1 noHover'>";
      echo "<td class='right' colspan='".($colspan-1)."'>".__('Total spent on the budget')."</td>";
      echo "<td class='numeric b'>".Html::formatNumber($total)."</td></tr>";
      if ($_SESSION['glpiactive_entity'] == $budget->fields['entities_id']) {
         echo "<tr class='tab_bg_1 noHover'>";
         echo "<td class='right' colspan='".($colspan-1)."'>".__('Total remaining on the budget').
               "</td>";
         echo "<td class='numeric b'>".Html::formatNumber($budget->fields['value'] - $total).
               "</td></tr>";
      }
      echo "</table></div>";
   }

}
?>
