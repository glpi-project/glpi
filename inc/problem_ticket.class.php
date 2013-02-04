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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class Problem_Ticket extends CommonDBRelation{

   // From CommonDBRelation
   public $itemtype_1 = 'Problem';
   public $items_id_1 = 'problems_id';

   public $itemtype_2 = 'Ticket';
   public $items_id_2 = 'tickets_id';

   var $checks_only_for_itemtype1 = true;

   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['setup'][624].' '.$LANG['problem'][0].'-'.$LANG['job'][38];
      }
      return $LANG['setup'][620].' '.$LANG['problem'][0].'-'.$LANG['job'][38];
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
    */
   function getSearchOptions() {
      return parent::getSearchOptions();
   }

   function post_addItem() {
      global $CFG_GLPI;
      
      $donotif = $CFG_GLPI["use_mailing"];

      if (isset($this->input["_no_notif"]) && $this->input["_no_notif"]) {
         $donotif = false;
      }
      if ($donotif) {
         $problem = new Problem();
         if ($problem->getFromDB($this->input["problems_id"])) {
            $options = array();
            NotificationEvent::raiseEvent("update", $problem, $options);
         }
      }

      parent::post_addItem();
   }

   function post_deleteFromDB() {
      global $CFG_GLPI;

      $donotif = $CFG_GLPI["use_mailing"];

      if (isset($this->input["_no_notif"]) && $this->input["_no_notif"]) {
         $donotif = false;
      }
      if ($donotif) {
         $problem = new Problem();
         if ($problem->getFromDB($this->fields["problems_id"])) {
            $options = array();
            NotificationEvent::raiseEvent("update", $problem, $options);
         }
      }

      parent::post_deleteFromDB();
   }
   
   /**
    * Show tickets for a problem
    *
    * @param $problem Problem object
   **/
   static function showForProblem(Problem $problem) {
      global $DB, $CFG_GLPI, $LANG;

      $ID = $problem->getField('id');
      if (!$problem->can($ID,'r')) {
         return false;
      }

      $canedit = $problem->can($ID,'w');

      $rand = mt_rand();
      echo "<form name='problemticket_form$rand' id='problemticket_form$rand' method='post'
             action='";
      echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";

      echo "<div class='center'><table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='10'>".$LANG['title'][28]."</th>";
      if ($problem->isRecursive()) {
         echo "<th>".$LANG['entity'][0]."</th>";
      }
      echo "</tr>";

      $query = "SELECT DISTINCT `glpi_problems_tickets`.`id` AS linkID,
                                `glpi_tickets`.*
                FROM `glpi_problems_tickets`
                LEFT JOIN `glpi_tickets`
                     ON (`glpi_problems_tickets`.`tickets_id` = `glpi_tickets`.`id`)
                WHERE `glpi_problems_tickets`.`problems_id` = '$ID'
                ORDER BY `glpi_tickets`.`name`";
      $result = $DB->query($query);

      $used = array();

      if ($DB->numrows($result) >0) {

         Ticket::commonListHeader(HTML_OUTPUT);
         Session::initNavigateListItems('Ticket',
                                        $LANG['problem'][0] ." = ". $problem->fields["name"]);
         $i=0;
         while ($data = $DB->fetch_array($result)) {
            $used[$data['id']] = $data['id'];
            Session::addToNavigateListItems('Ticket', $data["id"]);
            Ticket::showShort($data['id'],false,HTML_OUTPUT, $i, $data['linkID']);
            $i++;
         }
      }

      if ($canedit) {
         echo "<tr class='tab_bg_2'><td class='right'  colspan='8'>";
         echo "<input type='hidden' name='problems_id' value='$ID'>";
         $condition = "`glpi_tickets`.`status` NOT IN ('".implode("', '",
                                                                  array_merge(Ticket::getSolvedStatusArray(),
                                                                              Ticket::getClosedStatusArray()))."')";         
         Dropdown::show('Ticket', array('used'        => $used,
                                        'entity'      => $problem->getEntityID(),
                                        'entity_sons' => $problem->isRecursive(),
                                        'condition'   => $condition,
                                        'displaywith' => array('id')));
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "</td></tr>";
      }

      echo "</table></div>";

      if ($canedit) {
         Html::openArrowMassives("problemticket_form$rand", true);
         Html::closeArrowMassives(array('delete' => $LANG['buttons'][6]));
      }
      Html::closeForm();
   }


   /**
    * Show problems for a ticket
    *
    * @param $ticket Ticket object
   **/
   static function showForTicket(Ticket $ticket) {
      global $DB, $CFG_GLPI, $LANG;

      $ID = $ticket->getField('id');
      if (!$ticket->can($ID,'r')) {
         return false;
      }

      $canedit = $ticket->can($ID,'w');

      $rand = mt_rand();
      echo "<form name='problemticket_form$rand' id='problemticket_form$rand' method='post'
             action='";
      echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";

      echo "<div class='center'><table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='9'>".$LANG['Menu'][7]."&nbsp;-&nbsp;";
      echo "<a href='".Toolbox::getItemTypeFormURL('Problem')."?tickets_id=$ID'>".$LANG['problem'][7].
           "</a>";
      echo "</th></tr>";
      echo "<tr><th colspan='9'>".$LANG['common'][57]."</th>";
      echo "</tr>";

      $query = "SELECT DISTINCT `glpi_problems_tickets`.`id` AS linkID,
                                `glpi_problems`.*
                FROM `glpi_problems_tickets`
                LEFT JOIN `glpi_problems`
                     ON (`glpi_problems_tickets`.`problems_id` = `glpi_problems`.`id`)
                WHERE `glpi_problems_tickets`.`tickets_id` = '$ID'
                ORDER BY `glpi_problems`.`name`";
      $result = $DB->query($query);

      $used = array();

      if ($DB->numrows($result) >0) {

         Problem::commonListHeader(HTML_OUTPUT);
         Session::initNavigateListItems('Problem',
                                        $LANG['job'][38] ." = ". $ticket->fields["name"]);
         $i=0;
         while ($data = $DB->fetch_array($result)) {
            $used[$data['id']] = $data['id'];
            Session::addToNavigateListItems('Problem', $data["id"]);
            Problem::showShort($data['id'], HTML_OUTPUT, $i, $data['linkID']);
            $i++;
         }
      }

      if ($canedit) {
         echo "<tr class='tab_bg_2'><td class='right'  colspan='7'>";
         echo "<input type='hidden' name='tickets_id' value='$ID'>";
         $condition = "`glpi_problems`.`status` NOT IN ('".implode("', '",
                                                                  array_merge(Problem::getSolvedStatusArray(),
                                                                              Problem::getClosedStatusArray()))."')";
         Dropdown::show('Problem', array('used'      => $used,
                                         'entity'    => $ticket->getEntityID(),
                                         'condition' => $condition));
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "</td></tr>";
      }

      echo "</table></div>";

      if ($canedit) {
         Html::openArrowMassives("problemticket_form$rand", true);
         Html::closeArrowMassives(array('delete' => $LANG['buttons'][6]));
      }
      Html::closeForm();
   }


}
?>