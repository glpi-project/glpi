<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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
   die("Sorry. You can't access this file directly");
}

/**
 * CommonITILCost Class
 *
 * @since version 0.85
**/
abstract class CommonITILCost extends CommonDBChild {

   public $dohistory        = true;


   static function getTypeName($nb=0) {
      return _n('Cost', 'Costs', $nb);
   }


   function getItilObjectItemType() {
      return str_replace('Cost','',$this->getType());
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      // can exists for template
      if (($item->getType() == static::$itemtype)
          && static::canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable($this->getTable(),
                                       "`".$item->getForeignKeyField()."` = '".$item->getID()."'");
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum          (default 1)
    * @param $withtemplate    (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      self::showForObject($item, $withtemplate);
      return true;
   }


   function getSearchOptions() {

      $tab                          = array();

      $tab['common']                = __('Characteristics');

      $tab[1]['table']              = $this->getTable();
      $tab[1]['field']              = 'name';
      $tab[1]['name']               =  __('Title');
      $tab[1]['searchtype']         = 'contains';
      $tab[1]['datatype']           = 'itemlink';
      $tab[1]['massiveaction']      = false;

      $tab[2]['table']              = $this->getTable();
      $tab[2]['field']              = 'id';
      $tab[2]['name']               = __('ID');
      $tab[2]['massiveaction']      = false;
      $tab[2]['datatype']           = 'number';

      $tab[16]['table']             = $this->getTable();
      $tab[16]['field']             = 'comment';
      $tab[16]['name']              = __('Comments');
      $tab[16]['datatype']          = 'text';

      $tab[12]['table']             = $this->getTable();
      $tab[12]['field']             = 'begin_date';
      $tab[12]['name']              = __('Begin date');
      $tab[12]['datatype']          = 'datetime';

      $tab[10]['table']             = $this->getTable();
      $tab[10]['field']             = 'end_date';
      $tab[10]['name']              = __('End date');
      $tab[10]['datatype']          = 'datetime';

      $tab[11]['table']             = $this->getTable();
      $tab[11]['field']             = 'actiontime';
      $tab[11]['name']              = __('Duration');
      $tab[11]['datatype']          = 'timestamp';

      $tab[14]['table']              = $this->getTable();
      $tab[14]['field']              = 'cost_time';
      $tab[14]['name']               = __('Time cost');
      $tab[14]['datatype']           = 'decimal';

      $tab[15]['table']             = $this->getTable();
      $tab[15]['field']             = 'cost_fixed';
      $tab[15]['name']              = __('Fixed cost');
      $tab[15]['datatype']          = 'decimal';

      $tab[16]['table']             = $this->getTable();
      $tab[16]['field']             = 'cost_material';
      $tab[16]['name']              = __('Material cost');
      $tab[16]['datatype']          = 'decimal';

      $tab[18]['table']             = 'glpi_budgets';
      $tab[18]['field']             = 'name';
      $tab[18]['name']              = _n('Budget', 'Budgets', 1);
      $tab[18]['datatype']          = 'dropdown';

      $tab[80]['table']             = 'glpi_entities';
      $tab[80]['field']             = 'completename';
      $tab[80]['name']              = __('Entity');
      $tab[80]['massiveaction']     = false;
      $tab[80]['datatype']          = 'dropdown';

      return $tab;
   }


   static function getSearchOptionsToAdd() {

      $tab                       = array();
      $tab['cost']               = __('Cost');

      $tab[48]['table']          = static::getTable();
      $tab[48]['field']          = 'totalcost';
      $tab[48]['name']           = __('Total cost');
      $tab[48]['datatype']       = 'decimal';
      $tab[48]['forcegroupby']   = true;
      $tab[48]['usehaving']      = true;
      $tab[48]['massiveaction']  = false;
      $tab[48]['joinparams']     = array('jointype'  => 'child');
      $tab[48]['computation']    = "(SUM(TABLE.`actiontime`
                                         * TABLE.`cost_time`/".HOUR_TIMESTAMP."
                                         + TABLE.`cost_fixed`
                                         + TABLE.`cost_material`)
                                     / COUNT(TABLE.`id`))
                                   * COUNT(DISTINCT TABLE.`id`)";

      $tab[42]['table']          = static::getTable();
      $tab[42]['field']          = 'cost_time';
      $tab[42]['name']           = __('Time cost');
      $tab[42]['datatype']       = 'decimal';
      $tab[42]['forcegroupby']   = true;
      $tab[42]['usehaving']      = true;
      $tab[42]['massiveaction']  = false;
      $tab[42]['joinparams']     = array('jointype'  => 'child');
      $tab[42]['computation']    = "(SUM(TABLE.`cost_time`*TABLE.`actiontime`/".HOUR_TIMESTAMP.")
                                      / COUNT(TABLE.`id`))
                                    * COUNT(DISTINCT TABLE.`id`)";

      $tab[49]['table']          = static::getTable();
      $tab[49]['field']          = 'actiontime';
      $tab[49]['name']           = sprintf(__('%1$s - %2$s'), __('Cost'), __('Duration'));
      $tab[49]['datatype']       = 'timestamp';
      $tab[49]['forcegroupby']   = true;
      $tab[49]['usehaving']      = true;
      $tab[49]['massiveaction']  = false;
      $tab[49]['joinparams']     = array('jointype'  => 'child');

      $tab[43]['table']          = static::getTable();
      $tab[43]['field']          = 'cost_fixed';
      $tab[43]['name']           = __('Fixed cost');
      $tab[43]['datatype']       = 'decimal';
      $tab[43]['forcegroupby']   = true;
      $tab[43]['usehaving']      = true;
      $tab[43]['massiveaction']  = false;
      $tab[43]['joinparams']     = array('jointype'  => 'child');
      $tab[43]['computation']    = "(SUM(TABLE.`cost_fixed`) / COUNT(TABLE.`id`))
                                    * COUNT(DISTINCT TABLE.`id`)";

      $tab[44]['table']          = static::getTable();
      $tab[44]['field']          = 'cost_material';
      $tab[44]['name']           = __('Material cost');
      $tab[44]['datatype']       = 'decimal';
      $tab[44]['forcegroupby']   = true;
      $tab[44]['usehaving']      = true;
      $tab[44]['massiveaction']  = false;
      $tab[44]['joinparams']     = array('jointype'  => 'child');
      $tab[44]['computation']    = "(SUM(TABLE.`cost_material`) / COUNT(TABLE.`id`))
                                    * COUNT(DISTINCT TABLE.`id`)";

      return $tab;
   }


   /**
    * Init cost for creation based on previous cost
   **/
   function initBasedOnPrevious() {

      $item = new static::$itemtype();
      if (!isset($this->fields[static::$items_id])
          || !$item->getFromDB($this->fields[static::$items_id])) {
         return false;
      }

      // Set actiontime to
      $this->fields['actiontime']
                    = max(0,
                          $item->fields['actiontime']
                              - $this->getTotalActionTimeForItem($this->fields[static::$items_id]));
      $lastdata     = $this->getLastCostForItem($this->fields[static::$items_id]);

      if (isset($lastdata['end_date'])) {
         $this->fields['begin_date'] = $lastdata['end_date'];
      }
      if (isset($lastdata['cost_time'])) {
         $this->fields['cost_time'] = $lastdata['cost_time'];
      }
      if (isset($lastdata['cost_fixed'])) {
         $this->fields['cost_fixed'] = $lastdata['cost_fixed'];
      }
      if (isset($lastdata['budgets_id'])) {
         $this->fields['budgets_id'] = $lastdata['budgets_id'];
      }
      if (isset($lastdata['name'])) {
         $this->fields['name'] = $lastdata['name'];
      }
   }


   /**
    * Get total actiNULL        11400   0.0000  0.0000  0.0000  on time used on costs for an item
    *
    * @param $items_id        integer  ID of the item
   **/
   function getTotalActionTimeForItem($items_id) {
      global $DB;

      $query = "SELECT SUM(`actiontime`)
                FROM `".$this->getTable()."`
                WHERE `".static::$items_id."` = '$items_id'";

      if ($result = $DB->query($query)) {
         return $DB->result($result, 0, 0);
      }

      return 0;
   }


   /**
    * Get last datas for an item
    *
    * @param $items_id        integer  ID of the item
   **/
   function getLastCostForItem($items_id) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `".static::$items_id."` = '$items_id'
                ORDER BY 'end_date' DESC, `id` DESC";

      if ($result = $DB->query($query)) {
         return $DB->fetch_assoc($result);
      }

      return array();
   }


   /**
    * Print the item cost form
    *
    * @param $ID        integer  ID of the item
    * @param $options   array    options used
   **/
   function showForm($ID, $options=array()) {

      if (isset($options['parent']) && !empty($options['parent'])) {
         $item = $options['parent'];
      }

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options[static::$items_id] = $item->getField('id');
         $this->check(-1, CREATE, $options);
         $this->initBasedOnPrevious();
      }

      if ($ID > 0) {
         $items_id = $this->fields[static::$items_id];
      } else {
         $items_id = $options['parent']->fields["id"];
      }

      $item = new static::$itemtype();
      if (!$item->getFromDB($items_id)) {
         return false;
      }

      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      echo "<input type='hidden' name='".static::$items_id."' value='".$item->fields['id']."'>";

      Html::autocompletionTextField($this,'name');
      echo "</td>";
      echo "<td>".__('Begin date')."</td>";
      echo "<td>";
      Html::showDateField("begin_date", array('value' => $this->fields['begin_date']));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Duration')."</td>";
      echo "<td>";
      Dropdown::showTimeStamp('actiontime', array('value'           => $this->fields['actiontime'],
                                                  'addfirstminutes' => true));
      echo "</td>";
      echo "<td>".__('End date')."</td>";
      echo "<td>";
      Html::showDateField("end_date", array('value' => $this->fields['end_date']));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Time cost')."</td><td>";
      echo "<input type='text' size='15' name='cost_time' value='".
             Html::formatNumber($this->fields["cost_time"], true)."'>";
      echo "</td>";
      $rowspan = 4;
      echo "<td rowspan='$rowspan'>".__('Comments')."</td>";
      echo "<td rowspan='$rowspan' class='middle'>";
      echo "<textarea cols='45' rows='".($rowspan+3)."' name='comment' >".$this->fields["comment"].
           "</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Fixed cost')."</td><td>";
      echo "<input type='text' size='15' name='cost_fixed' value='".
             Html::formatNumber($this->fields["cost_fixed"], true)."'>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Material cost')."</td><td>";
      echo "<input type='text' size='15' name='cost_material' value='".
             Html::formatNumber($this->fields["cost_material"], true)."'>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>".__('Budget')."</td>";
      echo "<td>";
      Budget::dropdown(array('value'  => $this->fields["budgets_id"],
                             'entity' => $this->fields["entities_id"]));
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Print the item costs
    *
    * @param $item                  CommonITILObject object or Project
    * @param $withtemplate boolean  Template or basic item (default '')
    *
    * @return total cost
   **/
   static function showForObject($item, $withtemplate='') {
      global $DB, $CFG_GLPI;

      $forproject = false;
      if (Toolbox::is_a($item, 'Project')) {
         $forproject = true;
      }

      $ID = $item->fields['id'];

      if (!$item->getFromDB($ID)
          || !$item->canViewItem()
          || !static::canView()) {
         return false;
      }
      $canedit = false;
      if (!$forproject) {
         $canedit = $item->canAddItem(__CLASS__);
      }

      echo "<div class='center'>";

      $condition = "= '$ID'";

      if ($forproject) {
         $condition = " IN ('".implode("','",ProjectTask::getAllTicketsForProject($ID))."')";
      }

      $query = "SELECT *
                FROM `".static::getTable()."`
                WHERE `".static::$items_id."` $condition
                ORDER BY `begin_date`";

      $rand   = mt_rand();

      if ($canedit) {
         echo "<div id='viewcost".$ID."_$rand'></div>\n";
         echo "<script type='text/javascript' >\n";
         echo "function viewAddCost".$ID."_$rand() {\n";
         $params = array('type'             => static::getType(),
                         'parenttype'       => static::$itemtype,
                         static::$items_id  => $ID,
                         'id'               => -1);
         Ajax::updateItemJsCode("viewcost".$ID."_$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         if (static::canCreate()) {
            echo "<div class='center firstbloc'>".
                   "<a class='vsubmit' href='javascript:viewAddCost".$ID."_$rand();'>";
            echo __('Add a new cost')."</a></div>\n";
         }
      }

      $total          = 0;
      $total_time     = 0;
      $total_costtime = 0;
      $total_fixed    = 0;
      $total_material = 0;

      if ($result = $DB->query($query)) {
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr class='noHover'>";
         if ($forproject) {
            echo "<th colspan='10'>"._n('Ticket cost', 'Ticket costs', $DB->numrows($result))."</th>";
         } else {
            echo "<th colspan='7'>".self::getTypeName($DB->numrows($result))."</th>";
            echo "<th>".__('Item duration')."</th>";
            echo "<th>".CommonITILObject::getActionTime($item->fields['actiontime'])."</th>";
         }
         echo "</tr>";

         if ($DB->numrows($result)) {
            echo "<tr>";
            if ($forproject) {
               echo "<th>".__('Ticket')."</th>";
               $ticket = new Ticket();
            }
            echo "<th>".__('Name')."</th>";
            echo "<th>".__('Begin date')."</th>";
            echo "<th>".__('End date')."</th>";
            echo "<th>".__('Budget')."</th>";
            echo "<th>".__('Duration')."</th>";
            echo "<th>".__('Time cost')."</th>";
            echo "<th>".__('Fixed cost')."</th>";
            echo "<th>".__('Material cost')."</th>";
            echo "<th>".__('Total cost')."</th>";
            echo "</tr>";

            Session::initNavigateListItems(static::getType(),
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                           sprintf(__('%1$s = %2$s'),
                                                   $item->getTypeName(1), $item->getName()));

            while ($data = $DB->fetch_assoc($result)) {
               echo "<tr class='tab_bg_2' ".
                      ($canedit
                       ? "style='cursor:pointer' onClick=\"viewEditCost".$data[static::$items_id]."_".
                         $data['id']."_$rand();\"": '') .">";
               $name = (empty($data['name'])? sprintf(__('%1$s (%2$s)'),
                                                      $data['name'], $data['id'])
                                            : $data['name']);

               if ($forproject) {
                  $ticket->getFromDB($data['tickets_id']);
                  echo "<td>".$ticket->getLink()."</td>";
               }
               echo "<td>";
               printf(__('%1$s %2$s'), $name,
                        Html::showToolTip($data['comment'], array('display' => false)));
               if ($canedit) {
                  echo "\n<script type='text/javascript' >\n";
                  echo "function viewEditCost" .$data[static::$items_id]."_". $data["id"]. "_$rand() {\n";
                  $params = array('type'            => static::getType(),
                                 'parenttype'       => static::$itemtype,
                                 static::$items_id  => $data[static::$items_id],
                                 'id'               => $data["id"]);
                  Ajax::updateItemJsCode("viewcost".$ID."_$rand",
                                         $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
                  echo "};";
                  echo "</script>\n";
               }
               echo "</td>";
               echo "<td>".Html::convDate($data['begin_date'])."</td>";
               echo "<td>".Html::convDate($data['end_date'])."</td>";
               echo "<td>".Dropdown::getDropdownName('glpi_budgets', $data['budgets_id'])."</td>";
               echo "<td>".CommonITILObject::getActionTime($data['actiontime'])."</td>";
               $total_time += $data['actiontime'];
               echo "<td class='numeric'>".Html::formatNumber($data['cost_time'])."</td>";
               $total_costtime += ($data['actiontime']*$data['cost_time']/HOUR_TIMESTAMP);
               echo "<td class='numeric'>".Html::formatNumber($data['cost_fixed'])."</td>";
               $total_fixed += $data['cost_fixed'];
               echo "<td class='numeric'>".Html::formatNumber($data['cost_material'])."</td>";
               $total_material += $data['cost_material'];
               $cost            = self::computeTotalCost($data['actiontime'], $data['cost_time'],
                                                         $data['cost_fixed'], $data['cost_material']);
               echo "<td class='numeric'>".Html::formatNumber($cost)."</td>";
               $total += $cost;
               echo "</tr>";
               Session::addToNavigateListItems(static::getType(), $data['id']);
            }
            $colspan = 4;
            if ($forproject) {
               $colspan++;
            }
            echo "<tr class='b noHover'><td colspan='$colspan' class='right'>".__('Total').'</td>';
            echo "<td>".CommonITILObject::getActionTime($total_time)."</td>";
            echo "<td class='numeric'>".Html::formatNumber($total_costtime)."</td>";
            echo "<td class='numeric'>".Html::formatNumber($total_fixed).'</td>';
            echo "<td class='numeric'>".Html::formatNumber($total_material).'</td>';
            echo "<td class='numeric'>".Html::formatNumber($total).'</td></tr>';
         } else {
            echo "<tr><th colspan='9'>".__('No item found')."</th></tr>";
         }
         echo "</table>";
      }
      echo "</div><br>";
      return $total;
   }


   /**
    * Get costs summary values
    *
    * @param $type    string  type
    * @param $ID      integer ID of the ticket
    *
    * @return array of costs and actiontime
   **/
   static function getCostsSummary($type, $ID) {
      global $DB;

      $query = "SELECT *
                FROM `".getTableForItemtype($type)."`
                WHERE `".static::$items_id."` = '$ID'
                ORDER BY `begin_date`";

      $tab = array('totalcost'   => 0,
                  'actiontime'   => 0,
                  'costfixed'    => 0,
                  'costtime'     => 0,
                  'costmaterial' => 0
             );

      foreach ($DB->request($query) as $data) {
         $tab['actiontime']   += $data['actiontime'];
         $tab['costfixed']    += $data['cost_fixed'];
         $tab['costmaterial'] += $data['cost_material'];
         $tab['costtime']     += ($data['actiontime']*$data['cost_time']/HOUR_TIMESTAMP);
         $tab['totalcost']    +=  self::computeTotalCost($data['actiontime'], $data['cost_time'],
                                                         $data['cost_fixed'], $data['cost_material']);
      }
      foreach ($tab as $key => $val) {
         $tab[$key] = Html::formatNumber($val);
      }
      return $tab;
   }


   /**
    * Computer total cost of a item
    *
    * @param $actiontime      float    actiontime
    * @param $cost_time       float    time cost
    * @param $cost_fixed      float    fixed cost
    * @param $cost_material   float    material cost
    * @param $edit            boolean  used for edit of computation ? (true by default)
    *
    * @return total cost formatted string
   **/
   static function computeTotalCost($actiontime, $cost_time, $cost_fixed, $cost_material,
                                     $edit=true) {

      return Html::formatNumber(($actiontime*$cost_time/HOUR_TIMESTAMP)+$cost_fixed+$cost_material,
                                $edit);
   }

}
?>
