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
      $nb = 0;
      switch ($item->getType()) {
         default:
            $field = $item->getType() == PDU::getType() ? 'pdus_id' : 'plugs_id';
            if ($_SESSION['glpishow_count_on_tabs']) {
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

   /*function showForm($ID, $options = []) {
      global $DB, $CFG_GLPI;

      $colspan = 4;

      echo "<div class='center'>";

      $this->initForm($ID, $this->fields);
      $this->showFormHeader();

      $rack = new Rack();
      $rack->getFromDB($this->fields['racks_id']);

      $rand = mt_rand();

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_itemtype$rand'>".__('Item type')."</label></td>";
      echo "<td>";
      Dropdown::showFromArray(
         'itemtype',
         array_combine($CFG_GLPI['rackable_types'], $CFG_GLPI['rackable_types']), [
            'display_emptychoice'   => true,
            'value'                 => $this->fields["itemtype"],
            'rand'                  => $rand
         ]
      );

      //get all used items
      $used = [];
      $iterator = $DB->request([
         'FROM'   => $this->getTable()
      ]);
      while ($row = $iterator->next()) {
         $used [$row['itemtype']][] = $row['items_id'];
      }

      //items part of an enclosure should not be listed
      $iterator = $DB->request([
         'FROM'   => Item_Enclosure::getTable()
      ]);
      while ($row = $iterator->next()) {
         $used[$row['itemtype']][] = $row['items_id'];
      }

      Ajax::updateItemOnSelectEvent(
         "dropdown_itemtype$rand",
         "items_id",
         $CFG_GLPI["root_doc"]."/ajax/dropdownAllItems.php", [
            'idtable'   => '__VALUE__',
            'name'      => 'items_id',
            'value'     => $this->fields['items_id'],
            'rand'      => $rand,
            'used'      => $used
         ]
      );

      //TODO: update possible positions according to selected item number of units
      //TODO: update positions on rack selection
      //TODO: update hpos from item model info is_half_rack
      //TODO: update orientation according to item model depth

      echo "</td>";
      echo "<td><label for='dropdown_items_id$rand'>".__('Item')."</label></td>";
      echo "<td id='items_id'>";
      if (isset($this->fields['itemtype']) && !empty($this->fields['itemtype'])) {
         $itemtype = $this->fields['itemtype'];
         $itemtype = new $itemtype();
         $itemtype::dropdown([
            'name'   => "items_id",
            'value'  => $this->fields['items_id'],
            'rand'   => $rand
         ]);
      } else {
         Dropdown::showFromArray(
            'items_id',
            [], [
               'display_emptychoice'   => true,
               'rand'                  => $rand
            ]
         );
      }

      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_racks_id$rand'>".__('Rack')."</label></td>";
      echo "<td>";
      Rack::dropdown(['value' => $this->fields["racks_id"], 'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_position$rand'>".__('Position')."</label></td>";
      echo "<td >";
      Dropdown::showNumber(
         'position', [
            'value'  => $this->fields["position"],
            'min'    => 1,
            'max'    => $rack->fields['number_units'],
            'step'   => 1,
            'used'   => $rack->getFilled($this->fields['itemtype'], $this->fields['items_id']),
            'rand'   => $rand
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_orientation$rand'>".__('Orientation (front rack point of view)')."</label></td>";
      echo "<td >";
      Dropdown::showFromArray(
         'orientation', [
            Rack::FRONT => __('Front'),
            Rack::REAR  => __('Rear')
         ], [
            'value' => $this->fields["orientation"],
            'rand' => $rand
         ]
      );
      echo "</td>";
      echo "<td><label for='bgcolor$rand'>".__('Background color')."</label></td>";
      echo "<td>";
      Html::showColorField(
         'bgcolor', [
            'value'  => $this->fields['bgcolor'],
            'rand'   => $rand
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_hpos$rand'>".__('Horizontal position (from rack point of view)')."</label></td>";
      echo "<td>";
      Dropdown::showFromArray(
         'hpos',
         [
            Rack::POS_NONE    => __('None'),
            Rack::POS_LEFT    => __('Left'),
            Rack::POS_RIGHT   => __('Right')
         ], [
            'value'  => $this->fields['hpos'],
            'rand'   =>$rand
         ]
      );
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
   }*/
}
