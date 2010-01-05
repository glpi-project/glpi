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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Budget class
 */
class Budget extends CommonDBTM{

   // From CommonDBTM
   public $table = 'glpi_budgets';
   public $type = 'Budget';
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

   function defineTabs($ID,$withtemplate) {
      global $LANG;
      $ong=array();
      $ong[1]=$LANG['title'][26];

      if ($ID>0) {
         if (haveRight("document","r")) {
            $ong[5]=$LANG['Menu'][27];
         }
         if(empty($withtemplate)) {
            $ong[2]=$LANG['common'][1];
            if (haveRight("link","r")) {
               $ong[7]=$LANG['title'][34];
            }
            if (haveRight("notes","r")) {
               $ong[10]=$LANG['title'][37];
            }
            $ong[12]=$LANG['title'][38];
         }
      }

      return $ong;
   }

   /**
    * Print the contact form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the contact to print
    *@param $withtemplate='' boolean : template or basic item
    *
    *
    *@return Nothing (display)
    *
    **/
   function showForm ($target,$ID,$withtemplate='') {

      global $CFG_GLPI, $LANG;

      if (!haveRight("budget","r")) return false;

      $use_cache=true;

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }

      $this->showTabs($ID, $withtemplate);
      $this->showFormHeader($target,$ID,$withtemplate,2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]." : </td>";
      echo "<td>";
      autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='4' class='middle right'>".$LANG['common'][25].
         "&nbsp;: </td>";
      echo "<td class='center middle' rowspan='4'>.<textarea cols='45'
      rows='4' name='comment' >".$this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][21]." :</td>";
      echo "<td><input type='text' name='value' size='14'
         value=\"".formatNumber($this->fields["value"],true)."\" ></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['search'][8]." : </td>";
      echo "<td>";
      showDateFormItem("begin_date",$this->fields["begin_date"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['search'][9]." : </td>";
      echo "<td>";
      showDateFormItem("end_date",$this->fields["end_date"]);
      echo "</td></tr>";

      $this->showFormButtons($ID,$withtemplate,2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

   function prepareInputForAdd($input) {

      if (isset($input["id"])&&$input["id"]>0){
         $input["_oldID"]=$input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;
   }

   function post_addItem($newID,$input) {
      global $DB;

      // Manage add from template
      if (isset($input["_oldID"])) {
         // ADD Documents
         $query="SELECT `documents_id`
                 FROM `glpi_documents_items`
                 WHERE `items_id` = '".$input["_oldID"]."'
                       AND `itemtype` = '".$this->type."';";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            $docitem=new Document_Item();
            while ($data=$DB->fetch_array($result)) {
               $docitem->add(array('documents_id' => $data["documents_id"],
                                   'itemtype' => $this->type,
                                   'items_id' => $newID));
            }
         }
      }
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab[1]['table']         = 'glpi_budgets';
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = 'Budget';

      $tab[2]['table']     = 'glpi_budgets';
      $tab[2]['field']     = 'begin_date';
      $tab[2]['linkfield'] = 'begin_date';
      $tab[2]['name']      = $LANG['search'][8];
      $tab[2]['datatype']  = 'date';

      $tab[3]['table']     = 'glpi_budgets';
      $tab[3]['field']     = 'end_date';
      $tab[3]['linkfield'] = 'end_date';
      $tab[3]['name']      = $LANG['search'][9];
      $tab[3]['datatype']  = 'date';

      $tab[4]['table']     = 'glpi_budgets';
      $tab[4]['field']     = 'value';
      $tab[4]['linkfield'] = 'value';
      $tab[4]['name']      = $LANG['financial'][21];
      $tab[4]['datatype']  = 'integer';

      $tab[16]['table']     = 'glpi_budgets';
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      $tab[86]['table']     = 'glpi_budgets';
      $tab[86]['field']     = 'is_recursive';
      $tab[86]['linkfield'] = 'is_recursive';
      $tab[86]['name']      = $LANG['entity'][9];
      $tab[86]['datatype']  = 'bool';

      return $tab;
   }

   /**
   * Print the HTML array of Items on a budget
   *
   *@return Nothing (display)
   *
   **/
   function showDevices() {
      global $DB,$CFG_GLPI, $LANG;

      $budgets_id = $this->fields['id'];

      if (!$this->can($budgets_id,'r')) {
         return false;
      }

      $query = "SELECT DISTINCT `itemtype`
               FROM `glpi_infocoms`
               WHERE `budgets_id` = '$budgets_id'
               AND itemtype NOT IN ('ConsumableItem','CartridgeItem','Software')
               ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      echo "<br><br><div class='center'><table class='tab_cadrehov'>";
      echo "<tr><th colspan='2'>";
      printPagerForm();
      echo "</th><th colspan='3'>".$LANG['document'][19]."&nbsp;:</th></tr>";
      echo "<tr><th>".$LANG['common'][17]."</th>";
      echo "<th>".$LANG['entity'][0]."</th>";
      echo "<th>".$LANG['common'][16]."</th>";
      echo "<th>".$LANG['common'][19]."</th>";
      echo "<th>".$LANG['common'][20]."</th>";
      echo "</tr>";

      $num=0;
      for  ($i = 0; $i < $number ; $i++) {
         $itemtype=$DB->result($result, $i, "itemtype");

         if (!class_exists($itemtype)) {
            continue;
         }
         $item = new $itemtype();
         if ($item->canView()) {
            $query = "SELECT ".$item->table.".*
                     FROM `glpi_infocoms`
                     INNER JOIN ".$item->table."
                                 ON (".$item->table.".`id` = `glpi_infocoms`.`items_id`)
                     WHERE `glpi_infocoms`.`itemtype`='$itemtype'
                           AND `glpi_infocoms`.`budgets_id` = '$budgets_id' ".
                              getEntitiesRestrictRequest(" AND",$item->table)."
                     ORDER BY `entities_id`, ".$item->table.".`name`";

            if ($result_linked=$DB->query($query)) {
               $nb=$DB->numrows($result_linked);

               if ($nb>$_SESSION['glpilist_limit']) {
                  echo "<tr class='tab_bg_1'>";
                  echo "<td class='center'>".$item->getTypeName()."<br />$nb</td>";
                  echo "<td class='center' colspan='2'>";
                  echo "<a href='". $item->getSearchURL() . "?" .
                        rawurlencode("contains[0]") . "=" . rawurlencode('$$$$'.$budgets_id) . "&" .
                        rawurlencode("field[0]") . "=50&sort=80&order=ASC&is_deleted=0&start=0". "'>" .
                        $LANG['reports'][57]."</a></td>";
                  echo "<td class='center'>-</td><td class='center'>-</td></tr>";
               } else if ($nb) {
                  for ($prem=true;$data=$DB->fetch_assoc($result_linked);$prem=false) {
                     $ID="";
                     if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                        $ID= " (".$data["id"].")";
                     }
                     $name=NOT_AVAILABLE;
                     if ($item->getFromDB($data["id"])) {
                        $name= $item->getLink();;
                     }
                     echo "<tr class='tab_bg_1'>";
                     if ($prem) {
                        echo "<td class='center top' rowspan='$nb'>".$item->getTypeName()
                              .($nb>1?"<br />$nb</td>":"</td>");
                     }
                     echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",$data["entities_id"]);
                     echo "</td><td class='center";
                     echo (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
                     echo ">".$name."</td>";
                     echo "<td class='center'>".(isset($data["serial"])? "".$data["serial"]."" :"-");
                     echo "</td><td class='center'>".
                                 (isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
                     echo "</tr>";
                  }
               }
            $num+=$nb;
            }
         }
      }
      echo "<tr class='tab_bg_2'><td class='center'>$num</td><td colspan='4'>&nbsp;</td></tr> ";
      echo "</table></div>";
   }

   /**
   * Print the HTML array of value consumed for a budget
   *
   *@return Nothing (display)
   *
   **/
   function showValuesByEntity() {
      global $DB,$LANG,$CFG_GLPI;

      $budgets_id = $this->fields['id'];

      if (!$this->can($budgets_id,'r')) {
         return false;
      }

      $query = "SELECT DISTINCT `itemtype`
               FROM `glpi_infocoms`
               WHERE `budgets_id` = '$budgets_id'
               GROUP BY `itemtype`";

      $result = $DB->query($query);
      $total = 0;

      $entities_values = array();

      // Type for which infocom are only template
      $ignore = array('CartridgeItem', 'ConsumableItem', 'Software');

      if ( $DB->numrows($result) ) {
         while ($types = $DB->fetch_array($result)) {
            if (in_array($types['itemtype'], $ignore) || !class_exists($types['itemtype'])) {
               continue;
            }
            $item=new $types['itemtype']();
            $table = getTableForItemType($types['itemtype']);
            $query_infos = "SELECT SUM(`glpi_infocoms`.`value`) AS `sumvalue`,
                                 `$table`.`entities_id`
                           FROM `$table`
                           INNER JOIN `glpi_infocoms`
                              ON (`glpi_infocoms`.`items_id` = `$table`.`id`
                                 AND `glpi_infocoms`.`itemtype` = '".$types['itemtype']."')
                           LEFT JOIN `glpi_entities` ON (`$table`.`entities_id` = `glpi_entities`.`id`)
                           WHERE `glpi_infocoms`.`budgets_id` = '$budgets_id' ".
                                 getEntitiesRestrictRequest(" AND",$table,"entities_id");

            if ($item->maybeTemplate()) {
               $query_infos .= " AND `$table`.`is_template`='0' ";
            }
            $query_infos .= "GROUP BY `$table`.`entities_id`
                           ORDER BY `glpi_entities`.`completename` ASC";

            if ($result_infos = $DB->query($query_infos)) {
               //Store, for each entity, the budget spent
               while ($values = $DB->fetch_array($result_infos)) {
                  if (!isset($entities_values[$values['entities_id']])) {
                     $entities_values[$values['entities_id']] = 0;
                  }
                  $entities_values[$values['entities_id']] += $values['sumvalue'];
               }
            }

         }

         $budget = new Budget();
         $budget->getFromDB($budgets_id);

         echo "<br><br><div class='center'><table class='tab_cadre'>";
         echo "<tr>";
         echo "<th colspan='2'>".$LANG['financial'][108]."</th></tr>";
         echo "<tr><th>".$LANG['common'][17]."</th>";
         echo "<th>".$LANG['financial'][21]."</th>";
         echo "</tr>";

         foreach ($entities_values as $entity => $value) {
            echo "<tr class='tab_bg_1'><td>".Dropdown::getDropdownName('glpi_entities',$entity)."</th>";
            echo "<td class='right'>".formatNumber($value)."</td>";
            echo "</tr>";
            $total += $value;
         }

         echo "<tr class='tab_bg_1'><th colspan='2'><br></th></tr>";
         echo "<tr class='tab_bg_1'>";
         echo "<td class='right'>".$LANG['financial'][108]."</td>";
         echo "<td class='right b' colspan='2'>".formatNumber($total)."</td></tr>";
         if ($_SESSION['glpiactive_entity'] == $budget->fields['entities_id']) {
            echo "<tr class='tab_bg_1'>";
            echo "<td class='right'>".$LANG['financial'][109]."</td>";
            echo "<td class='right b'>".formatNumber($budget->fields['value'] - $total)."</td></tr>";
         }
         echo "</table></div>";

      }
   }
}

?>