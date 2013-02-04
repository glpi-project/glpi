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

class Change_Ticket extends CommonDBRelation{

   // From CommonDBRelation
   public $itemtype_1 = 'Change';
   public $items_id_1 = 'changes_id';

   public $itemtype_2 = 'Ticket';
   public $items_id_2 = 'tickets_id';

   var $checks_only_for_itemtype1 = true;


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['setup'][624].' '.$LANG['change'][0].'-'.$LANG['job'][38];
      }
      return $LANG['setup'][620].' '.$LANG['change'][0].'-'.$LANG['job'][38];
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
    */
   function getSearchOptions() {
      return parent::getSearchOptions();
   }


   /**
    * Show tickets for a change
    *
    * @param $change Change object
   **/
   static function showForChange(Change $change) {
      global $DB, $CFG_GLPI, $LANG;

      $ID = $change->getField('id');
      if (!$change->can($ID,'r')) {
         return false;
      }

      $canedit = $change->can($ID,'w');
      $rand    = mt_rand();
      echo "<form name='changeticket_form$rand' id='changeticket_form$rand' method='post'
             action='";
      echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      $colspan = 1;

      echo "<div class='center'><table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='2'>".$LANG['common'][57]."</th>";
      if ($change->isRecursive()) {
         echo "<th>".$LANG['entity'][0]."</th>";
         $colspan++;
      }
      echo "</tr>";

      $query = "SELECT DISTINCT `glpi_changes_tickets`.`id` AS linkID,
                                `glpi_tickets`.*
                FROM `glpi_changes_tickets`
                LEFT JOIN `glpi_tickets`
                     ON (`glpi_changes_tickets`.`tickets_id` = `glpi_tickets`.`id`)
                WHERE `glpi_changes_tickets`.`changes_id` = '$ID'
                ORDER BY `glpi_tickets`.`name`";
      $result = $DB->query($query);

      $used = array();
      if ($DB->numrows($result) >0) {
         Session::initNavigateListItems('Ticket',
                                        $LANG['change'][0] ." = ". $change->fields["name"]);

         while ($data = $DB->fetch_array($result)) {
            $used[$data['id']] = $data['id'];
            Session::addToNavigateListItems('Ticket', $data["id"]);
            echo "<tr class='tab_bg_1'>";
            echo "<td width='10'>";
            if ($canedit) {
               echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1'>";
            } else {
               echo "&nbsp;";
            }
            echo "</td>";
            echo "<td><a href='".Toolbox::getItemTypeFormURL('Ticket')."?id=".$data['id']."'>".
                      $data["name"]."</a></td>";
            if ($change->isRecursive()) {
               echo "<td>".Dropdown::getDropdownName('glpi_entities',$data["entities_id"])."</td>";
            }
            echo "</tr>";
         }
      }

      if ($canedit) {
         echo "<tr class='tab_bg_2'><td class='right'  colspan='$colspan'>";
         echo "<input type='hidden' name='changes_id' value='$ID'>";
         Dropdown::show('Ticket', array('used'        => $used,
                                        'entity'      => $change->getEntityID(),
                                        'entity_sons' => $change->isRecursive()));
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "</td></tr>";
      }

      echo "</table></div>";

      if ($canedit) {
         Html::openArrowMassives("changeticket_form$rand", true);
         Html::closeArrowMassives(array('delete' => $LANG['buttons'][6]));
      }
      Html::closeForm();
   }


   /**
    * Show changes for a ticket
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
      $rand    = mt_rand();
      echo "<form name='changeticket_form$rand' id='changeticket_form$rand' method='post'
             action='";
      echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      $colspan = 1;

      echo "<div class='center'><table class='tab_cadre_fixehov'>";
      echo "<tr><th colspan='2'>".$LANG['Menu'][8]."&nbsp;-&nbsp;";
      echo "<a href='".Toolbox::getItemTypeFormURL('Change')."?tickets_id=$ID'>".$LANG['change'][1];
      echo "</a></th></tr>";

      echo "<tr><th colspan='2'>".$LANG['common'][57]."</th>";
      echo "</tr>";

      $query = "SELECT DISTINCT `glpi_changes_tickets`.`id` AS linkID,
                                `glpi_changes`.*
                FROM `glpi_changes_tickets`
                LEFT JOIN `glpi_changes`
                     ON (`glpi_changes_tickets`.`changes_id` = `glpi_changes`.`id`)
                WHERE `glpi_changes_tickets`.`tickets_id` = '$ID'
                ORDER BY `glpi_changes`.`name`";
      $result = $DB->query($query);

      $used = array();
      if ($DB->numrows($result) >0) {
         Session::initNavigateListItems('Change', $LANG['job'][38] ." = ". $ticket->fields["name"]);

         while ($data = $DB->fetch_array($result)) {
            $used[$data['id']] = $data['id'];
            Session::addToNavigateListItems('Change', $data["id"]);
            echo "<tr class='tab_bg_1'>";
            echo "<td width='10'>";
            if ($canedit) {
               echo "<input type='checkbox' name='item[".$data["linkID"]."]' value='1'>";
            } else {
               echo "&nbsp;";
            }
            echo "</td>";
            echo "<td><a href='".Toolbox::getItemTypeFormURL('Change')."?id=".$data['id']."'>".
                      $data["name"]."</a></td>";
            echo "</tr>";
         }
      }

      if ($canedit) {
         echo "<tr class='tab_bg_2'><td class='right'  colspan='$colspan'>";
         echo "<input type='hidden' name='tickets_id' value='$ID'>";
         Dropdown::show('Change', array('used'   => $used,
                                        'entity' => $ticket->getEntityID()));
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "</td></tr>";
      }

      echo "</table></div>";

      if ($canedit) {
         Html::openArrowMassives("changeticket_form$rand", true);
         Html::closeArrowMassives(array('delete' => $LANG['buttons'][6]));
      }
      Html::closeForm();
   }


}
?>