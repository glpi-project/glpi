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

// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"ticketsatisfaction.php")) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

$entity = new Entity();

if (isset($_POST['inquest_config']) && isset($_POST['entities_id'])) {
   if ($entity->getFromDB($_POST['entities_id'])) {
      $inquest_config = $entity->getfield('inquest_config');
      $inquest_delay  = $entity->getfield('inquest_delay');
      $inquest_rate   = $entity->getfield('inquest_rate');
      $max_closedate  = $entity->getfield('max_closedate');
   } else {
      $inquest_config = $_POST['inquest_config'];
      $inquest_delay  = -1;
      $inquest_rate   = -1;
      $max_closedate  = '';
   }

   if ($_POST['inquest_config'] > 0) {
      echo "<table class='tab_cadre_fixe' width='50%'>";
      echo "<tr class='tab_bg_1'><td width='50%'>".__('Create survey after')."</td>";
      echo "<td>";
      Dropdown::showInteger('inquest_delay', $inquest_delay, 1, 90, 1,
                            array('0'    => __('As soon as possible')),
                            array('unit' => 'day'));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>".
           "<td>".__('Rate to trigger survey')."</td>";
      echo "<td>";
      Dropdown::showInteger('inquest_rate', $inquest_rate, 10, 100, 10,
                            array(0      => __('Disabled')),
                            array('unit' => '%'));
      echo "</td></tr>";

      if ($max_closedate != '') {
         echo "<tr class='tab_bg_1'><td>". __('For tickets closed after')."</td>";
         echo "<td>" . Html::convDateTime($max_closedate)."</td></tr>";
      }

      if ($_POST['inquest_config'] == 2) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Valid tags')."</td><td>".
               "[TICKET_ID] [TICKET_NAME] [TICKET_CREATEDATE] [TICKET_SOLVEDATE] ".
               "[REQUESTTYPE_ID] [REQUESTTYPE_NAME] [ITEMTYPE] [ITEM_ID] [ITEM_NAME] ".
               "[TICKET_PRIORITY] [TICKETCATEGORY_ID] [TICKETCATEGORY_NAME] [TICKETTYPE_ID] ".
               "[TICKETTYPE_NAME] [SOLUTIONTYPE_ID] [SOLUTIONTYPE_NAME] ".
               "[SLA_ID] [SLA_NAME] [SLALEVEL_ID] [SLALEVEL_NAME]</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . __('URL') . "</td>";
         echo "<td>";
         Html::autocompletionTextField($entity, "inquest_URL");
         echo "</td></tr>";
      }

      echo "</table>";
   }
}
?>