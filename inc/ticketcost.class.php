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

/// TicketCost class
/// since version 0.84
class TicketCost extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype  = 'Ticket';
   static public $items_id  = 'tickets_id';
   public $dohistory        = true;


   static function getTypeName($nb=0) {
      return _n('Cost', 'Costs', $nb);
   }


   static function canCreate() {

      return (Session::haveRight('ticketcost','w')
              && parent::canCreate());
   }


   static function canView() {
      return (Session::haveRight('ticketcost','r')
              && parent::canView());
   }

   /**
    * @since version 0.84
   **/
   static function canUpdate() {

      return (Session::haveRight('ticketcost','w')
              && parent::canUpdate());
   }


   /**
    * @since version 0.84
   **/
   static function canDelete() {

      return (Session::haveRight('ticketcost','w')
              && parent::canDelete());
   }


    /**
    * @see CommonDBChild::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      if (empty($input['end_date'])
          || ($input['end_date'] == 'NULL')
          || ($input['end_date'] < $input['begin_date'])) {

         $input['end_date'] = $input['begin_date'];
      }

      return parent::prepareInputForAdd($input);
   }


   /**
    * @see CommonDBTM::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      if (empty($input['end_date'])
          || ($input['end_date'] == 'NULL')
          || ($input['end_date'] < $input['begin_date'])) {

         $input['end_date'] = $input['begin_date'];
      }

      return parent::prepareInputForUpdate($input);
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      // can exists for template
      if (($item->getType() == 'Ticket')
          && Session::haveRight("ticketcost","r")) {

         if ($_SESSION['glpishow_count_on_tabs']) {
            return self::createTabEntry(self::getTypeName(2),
                                        countElementsInTable('glpi_ticketcosts',
                                                             "tickets_id = '".$item->getID()."'"));
         }
         return self::getTypeName(2);
      }
      return '';
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum          (default 1)
    * @param $withtemplate    (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      self::showForTicket($item, $withtemplate);
      return true;
   }


   /**
    * Init cost for creation based on previous cost
   **/
   function initBasedOnPrevious() {

      $ticket = new Ticket();
      if (!isset($this->fields['tickets_id']) || !$ticket->getFromDB($this->fields['tickets_id'])) {
         return false;
      }

      // Set actiontime to
      $this->fields['actiontime']
                 = max(0, $ticket->fields['actiontime']
                           - $this->getTotalActionTimeForTicket($this->fields['tickets_id']));
      $lastdata  = $this->getLastCostForTicket($this->fields['tickets_id']);

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
    * Get total actiNULL        11400   0.0000  0.0000  0.0000  on time used on costs for a ticket
    *
    * @param $tickets_id        integer  ID of the ticket
   **/
   function getTotalActionTimeForTicket($tickets_id) {
      global $DB;

      $query = "SELECT SUM(`actiontime`)
                FROM `".$this->getTable()."`
                WHERE `tickets_id` = '$tickets_id'";

      if ($result = $DB->query($query)) {
         return $DB->result($result, 0, 0);
      }

      return 0;
   }


   /**
    * Get last datas for a ticket
    *
    * @param $tickets_id        integer  ID of the ticket
   **/
   function getLastCostForTicket($tickets_id) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `tickets_id` = '$tickets_id'
                ORDER BY 'end_date' DESC, `id` DESC";

      if ($result = $DB->query($query)) {
         return $DB->fetch_assoc($result);
      }

      return array();
   }


   /**
    * Print the ticket cost form
    *
    * @param $ID        integer  ID of the item
    * @param $options   array    options used
   **/
   function showForm($ID, $options=array()) {

      if (isset($options['parent']) && !empty($options['parent'])) {
         $ticket = $options['parent'];
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $options['tickets_id'] = $ticket->getField('id');
         $this->check(-1,'w',$options);
         $this->initBasedOnPrevious();
      }

      if ($ID > 0) {
         $tickets_id = $this->fields["tickets_id"];
      } else {
         $tickets_id = $options['parent']->fields["id"];
      }

      $ticket = new Ticket();
      if (!$ticket->getFromDB($tickets_id)) {
         return false;
      }

      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      echo "<input type='hidden' name='tickets_id' value='".$ticket->fields['id']."'>";

      Html::autocompletionTextField($this,'name');
      echo "</td>";
      echo "<td>".__('Begin date')."</td>";
      echo "<td>";
      Html::showDateFormItem("begin_date", $this->fields['begin_date']);
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
      Html::showDateFormItem("end_date", $this->fields['end_date']);
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
    * Print the ticket costs
    *
    * @param $ticket                  Ticket object
    * @param $withtemplate boolean  Template or basic item (default '')
    *
    * @return Nothing (call to classes members)
   **/
   static function showForTicket(Ticket $ticket, $withtemplate='') {
      global $DB, $CFG_GLPI;

      $ID = $ticket->fields['id'];

      if (!$ticket->getFromDB($ID)
          || !$ticket->can($ID, "r")
          || !Session::haveRight('ticketcost', 'r')) {
         return false;
      }
      $canedit = Session::haveRight('ticketcost', 'w');

      echo "<div class='center'>";

      $query = "SELECT *
                FROM `glpi_ticketcosts`
                WHERE `tickets_id` = '$ID'
                ORDER BY `begin_date`";

      $rand   = mt_rand();

      if ($canedit) {
         echo "<div id='viewcost".$ID."_$rand'></div>\n";
         echo "<script type='text/javascript' >\n";
         echo "function viewAddCost".$ID."_$rand() {\n";
         $params = array('type'         => __CLASS__,
                         'parenttype'   => 'Ticket',
                         'tickets_id'   => $ID,
                         'id'           => -1);
         Ajax::updateItemJsCode("viewcost".$ID."_$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         echo "<div class='center firstbloc'>".
               "<a class='vsubmit' href='javascript:viewAddCost".$ID."_$rand();'>";
         echo __('Add a new cost')."</a></div>\n";
      }

      if ($result = $DB->query($query)) {
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr><th colspan='7'>".self::getTypeName($DB->numrows($result))."</th>";
         echo "<th>".__('Ticket duration')."</th>";
         echo "<th>".CommonITILObject::getActionTime($ticket->fields['actiontime'])."</th>";
         echo "</tr>";

         if ($DB->numrows($result)) {
            echo "<tr><th>".__('Name')."</th>";
            echo "<th>".__('Begin date')."</th>";
            echo "<th>".__('End date')."</th>";
            echo "<th>".__('Budget')."</th>";
            echo "<th>".__('Duration')."</th>";
            echo "<th>".__('Time cost')."</th>";
            echo "<th>".__('Fixed cost')."</th>";
            echo "<th>".__('Material cost')."</th>";
            echo "<th>".__('Total cost')."</th>";
            echo "</tr>";

         Session::initNavigateListItems(__CLASS__,
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                Ticket::getTypeName(1), $ticket->getName()));

            $total          = 0;
            $total_time     = 0;
            $total_costtime = 0;
            $total_fixed    = 0;
            $total_material = 0;

            while ($data = $DB->fetch_assoc($result)) {
               echo "<tr class='tab_bg_2' ".
                      ($canedit
                       ? "style='cursor:pointer' onClick=\"viewEditCost".$data['tickets_id']."_".
                         $data['id']."_$rand();\"": '') .">";
               $name = (empty($data['name'])? sprintf(__('%1$s (%2$s)'),
                                                      $data['name'], $data['id'])
                                            : $data['name']);
               echo "<td>";
               printf(__('%1$s %2$s'), $name,
                        Html::showToolTip($data['comment'], array('display' => false)));
               if ($canedit) {
                  echo "\n<script type='text/javascript' >\n";
                  echo "function viewEditCost" .$data['tickets_id']."_". $data["id"]. "_$rand() {\n";
                  $params = array('type'      => __CLASS__,
                                 'parenttype' => 'Ticket',
                                 'tickets_id' => $data["tickets_id"],
                                 'id'         => $data["id"]);
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
               Session::addToNavigateListItems(__CLASS__, $data['id']);
            }
            echo "<tr class='b'><td colspan='4' class='right'>".__('Total').'</td>';
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
   }

   /**
    * Get costs summary values
    *
    * @param $ID      integer ID of the ticket
    * @since version 0.84.3
    * @return array of costs and actiontime
   **/
   static function getCostsSummary($ID) {
      global $DB;
      
      $query = "SELECT *
                FROM `glpi_ticketcosts`
                WHERE `tickets_id` = '$ID'
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
    * Computer total cost of a ticket
    *
    * @param $actiontime      float    ticket actiontime
    * @param $cost_time       float    ticket time cost
    * @param $cost_fixed      float    ticket fixed cost
    * @param $cost_material   float    ticket material cost
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
