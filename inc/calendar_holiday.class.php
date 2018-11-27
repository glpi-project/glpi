<?php
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
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class Calendar_Holiday extends CommonDBRelation {

   public $auto_message_on_action = false;

   // From CommonDBRelation
   static public $itemtype_1 = 'Calendar';
   static public $items_id_1 = 'calendars_id';
   static public $itemtype_2 = 'Holiday';
   static public $items_id_2 = 'holidays_id';

   static public $checkItem_2_Rights = self::DONT_CHECK_ITEM_RIGHTS;


   /**
    * @since 0.84
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * Show holidays for a calendar
    *
    * @param $calendar Calendar object
   **/
   static function showForCalendar(Calendar $calendar) {
      global $DB;

      $ID = $calendar->getField('id');
      if (!$calendar->can($ID, READ)) {
         return false;
      }

      $canedit = $calendar->can($ID, UPDATE);

      $rand    = mt_rand();

      $iterator = $DB->request([
         'SELECT DISTINCT' => 'glpi_calendars_holidays.id AD linkID',
         'FIELDS'          => 'glpi_holidays.*',
         'FROM'            => 'glpi_calendars_holidays',
         'LEFT JOIN'       => [
            'glpi_holidays'   => [
               'ON' => [
                  'glpi_calendars_holidays'  => 'holidays_id',
                  'glpi_holidays'            => 'id'
               ]
            ]
         ],
         'WHERE'           => [
            'glpi_calendars_holidays.calendars_id' => $ID
         ],
         'ORDERBY'         => 'glpi_holidays.name'
      ]);

      $numrows = count($iterator);
      $holidays = [];
      $used     = [];
      while ($data = $iterator->next()) {
         $holidays[$data['id']] = $data;
         $used[$data['id']]     = $data['id'];
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='calendarsegment_form$rand' id='calendarsegment_form$rand' method='post'
                action='";
         echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='7'>".__('Add a close time')."</tr>";
         echo "<tr class='tab_bg_2'><td class='right'  colspan='4'>";
         echo "<input type='hidden' name='calendars_id' value='$ID'>";
         Holiday::dropdown(['used'   => $used,
                                 'entity' => $calendar->fields["entities_id"]]);
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";

      if ($canedit && $numrows) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $numrows),
                           'container'     => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr>";
      if ($canedit && $numrows) {
         echo "<th width='10'>";
         echo Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         echo "</th>";
      }
      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Start')."</th>";
      echo "<th>".__('End')."</th>";
      echo "<th>".__('Recurrent')."</th>";
      echo "</tr>";

      $used = [];

      if ($numrows) {

         Session::initNavigateListItems('Holiday',
         //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'), Calendar::getTypeName(1),
                                                $calendar->fields["name"]));

         foreach ($holidays as $data) {
            Session::addToNavigateListItems('Holiday', $data["id"]);
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
               echo "<td>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["linkID"]);
               echo "</td>";
            }
            echo "<td><a href='".Toolbox::getItemTypeFormURL('Holiday')."?id=".$data['id']."'>".
                       $data["name"]."</a></td>";
            echo "<td>".Html::convDate($data["begin_date"])."</td>";
            echo "<td>".Html::convDate($data["end_date"])."</td>";
            echo "<td>".Dropdown::getYesNo($data["is_perpetual"])."</td>";
            echo "</tr>";
         }
      }
      echo "</table>";

      if ($canedit && $numrows) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   /**
    * Duplicate all holidays from a calendar to its clone
    *
    * @param $oldid
    * @param $newid
   **/
   static function cloneCalendar($oldid, $newid) {
      global $DB;

      $result = $DB->request(
         [
            'FROM'   => self::getTable(),
            'WHERE'  => [
               'calendars_id' => $oldid,
            ]
         ]
      );

      foreach ($result as $data) {
         $ch                   = new self();
         unset($data['id']);
         $data['calendars_id'] = $newid;
         $data['_no_history']  = true;

         $ch->add($data);
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Calendar' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable($this->getTable(), ['calendars_id' => $item->getID()]);
               }
               return self::createTabEntry(_n('Close time', 'Close times', Session::getPluralNumber()),
                                            $nb);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType()=='Calendar') {
         self::showForCalendar($item);
      }
      return true;
   }
}

