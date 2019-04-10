<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
**/

class Pdu_Plug extends CommonDBRelation {

   static public $itemtype_1 = 'Pdu';
   static public $items_id_1 = 'pdus_id';
   static public $itemtype_2 = 'Plug';
   static public $items_id_2 = 'plugs_id';
   static public $checkItem_1_Rights = self::DONT_CHECK_ITEM_RIGHTS;
   static public $mustBeAttached_1      = false;
   static public $mustBeAttached_2      = false;

   static function getTypeName($nb = 0) {
      return _n('PDU plug', 'PDU plugs', $nb);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
       global $IS_TWIG;

      $nb = 0;
      switch ($item->getType()) {
         default:
            $field = $item->getType() == PDU::getType() ? 'pdus_id' : 'plugs_id';
            if ($_SESSION['glpishow_count_on_tabs'] && !$IS_TWIG) {
               $nb = countElementsInTable(
                  self::getTable(),
                  [$field  => $item->getID()]
               );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      self::showItems($item, $withtemplate);
   }

   /**
    * Print items
    *
    * @param  PDU $pdu PDU instance
    *
    * @return void
    */
   static function showItems(PDU $pdu) {
      global $DB, $CFG_GLPI;

      $ID = $pdu->getID();
      $rand = mt_rand();

      if (!$pdu->getFromDB($ID)
          || !$pdu->can($ID, READ)) {
         return false;
      }
      $canedit = $pdu->canEdit($ID);

      $items = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'pdus_id' => $pdu->getID()
         ]
      ]);
      $link = new self();

      Session::initNavigateListItems(
         self::getType(),
         //TRANS : %1$s is the itemtype name,
         //        %2$s is the name of the item (used for headings of a list)
         sprintf(
            __('%1$s = %2$s'),
            $pdu->getTypeName(1),
            $pdu->getName()
         )
      );

      $items = iterator_to_array($items);

      if ($canedit) {
         $rand = mt_rand();
         echo "\n<form id='form_device_add$rand' name='form_device_add$rand'
               action='".Toolbox::getItemTypeFormURL(__CLASS__)."' method='post'>\n";
         echo "\t<input type='hidden' name='pdus_id' value='$ID'>\n";
         //echo "\t<input type='hidden' name='itemtype' value='".$item->getType()."'>\n";
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_1'><td>";
         echo "<label for='dropdown_plugs_id$rand'>" .__('Add a new plug')."</label></td><td>";
         Plug::dropdown([
            'name'   => "plugs_id",
            'rand'   => $rand
         ]);
         echo "</td><td>";
         echo "<label for='number_plugs'>" . __('Number');
         echo "</td><td>";
         echo Html::input(
            'number_plugs', [
               'id'     => 'number_plugs',
               'type'   => 'number',
               'min'    => 1
            ]
         );
         echo "</td><td>";
         echo "<input type='submit' class='submit' name='add' value='"._sx('button', 'Add')."'>";
         echo "</td></tr></table>";
         Html::closeForm();
      }

      if (!count($items)) {
         echo "<table class='tab_cadre_fixe'><tr><th>".__('No plug found')."</th></tr>";
         echo "</table>";
      } else {
         if ($canedit) {
            $massiveactionparams = [
               'num_displayed'   => min($_SESSION['glpilist_limit'], count($items)),
               'container'       => 'mass'.__CLASS__.$rand
            ];
            Html::showMassiveActions($massiveactionparams);
         }

         echo "<table class='tab_cadre_fixehov' id='mass".__CLASS__.$rand."'>";
         $header = "<tr>";
         if ($canedit) {
            $header .= "<th width='10'>";
            $header .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header .= "</th>";
         }
         $header .= "<th>".__('Name')."</th>";
         $header .= "<th>".__('Number')."</th>";
         $header .= "</tr>";

         echo $header;
         foreach ($items as $row) {
            $item = new Plug;
            $item->getFromDB($row['plugs_id']);
            echo "<tr lass='tab_bg_1'>";
            if ($canedit) {
               echo "<td>";
               Html::showMassiveActionCheckBox(__CLASS__, $row["id"]);
               echo "</td>";
            }
            echo "<td>" . $item->getLink() . "</td>";
            echo "<td>{$row['number_plugs']}</td>";
            echo "</tr>";
         }
         echo $header;
         echo "</table>";

         if ($canedit && count($items)) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
         }
         if ($canedit) {
            Html::closeForm();
         }
      }
   }
}
