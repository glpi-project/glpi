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

/**
 * DCRoom Class
**/
class DCRoom extends CommonDBTM {
   use DCBreadcrumb;

   // From CommonDBTM
   public $dohistory                   = true;
   protected $usenotepad               = true;
   static $rightname                   = 'datacenter';

   static function getTypeName($nb = 0) {
      //TRANS: Test of comment for translation (mark : //TRANS)
      return _n('Server room', 'Server rooms', $nb);
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addStandardTab('Rack', $ong, $options)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Contract_Item', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('Ticket', $ong, $options)
         ->addStandardTab('Item_Problem', $ong, $options)
         ->addStandardTab('Change_Item', $ong, $options)
         ->addStandardTab('Log', $ong, $options);
      ;
      return $ong;
   }

   function showForm($ID, $options = []) {
      global $DB, $CFG_GLPI;
      $rand = mt_rand();

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='textfield_name$rand'>".__('Name')."</label></td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name", ['rand' => $rand]);
      echo "</td>";

      echo "<td><label for='dropdown_locations_id$rand'>".__('Location')."</label></td>";
      echo "<td>";
      Location::dropdown([
         'value'  => $this->fields["locations_id"],
         'entity' => $this->fields["entities_id"],
         'rand'   => $rand
      ]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_datacenters_id$rand'>".__('Datacenter')."</label></td>";

      echo "<td>";
      $datacenters = $DB->request([
         'SELECT' => ['id', 'name'],
         'FROM'   => Datacenter::getTable()
      ]);
      $datacenters_list = [];
      while ($row = $datacenters->next()) {
         $datacenters_list[$row['id']] = $row['name'];
      }
      Dropdown::showFromArray(
         "datacenters_id",
         $datacenters_list, [
            'value'                 => $this->fields["datacenters_id"],
            'rand'                  => $rand,
            'display_emptychoice'   => true
         ]
      );
      Ajax::updateItemOnSelectEvent(
         "dropdown_datacenters_id$rand",
         "dropdown_locations_id$rand",
         $CFG_GLPI["root_doc"]."/ajax/dropdownLocation.php", [
            'items_id' => '__VALUE__',
            'itemtype' => 'Datacenter'
         ]
      );
      echo "</td>";
      echo "<td colspan='2'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_vis_cols$rand'>" . __('Number of columns') . "</label></td><td>";
      Dropdown::showNumber(
         "vis_cols", [
            'value'  => $this->fields["vis_cols"],
            'min'    => 1,
            'max'    => 100,
            'step'   => 1,
            'rand'   => $rand
         ]
      );
      echo "</td>";
      echo "<td><label for='dropdown_vis_rows$rand'>" . __('Number of rows') . "</label></td><td>";
      Dropdown::showNumber(
         "vis_rows", [
            'value'  => $this->fields["vis_rows"],
            'min'    => 1,
            'max'    => 100,
            'step'   => 1,
            'rand'   => $rand
         ]
      );
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for=''>" . __('Background picture (blueprint)') . "</label></td><td>";

      if (!empty($this->fields['blueprint'])) {
         echo Html::image($this->fields['blueprint'], [
            'style' => 'max-width: 100px; max-height: 50px;',
            'class' => 'picture_square'
         ]);
         echo "&nbsp;";
         echo Html::getCheckbox([
            'title' => __('Clear'),
            'name'  => '_blank_blueprint'
         ]);
         echo "&nbsp;".__('Clear');

      } else {
         echo Html::file([
            'name'       => 'blueprint',
            'onlyimages' => true,
         ]);
      }

      echo "</td>";
      echo "<td colspan = '2'></td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return true;
   }

   function prepareInputForAdd($input) {
      return $this->manageBlueprint($input);
   }

   function prepareInputForUpdate($input) {
      return $this->manageBlueprint($input);
   }

   /**
    * Add/remove blueprint picture
    * @param  array $input the form input
    * @return array        the altered input
    */
   function manageBlueprint($input) {
      global $CFG_GLPI;

      if (isset($input["_blank_blueprint"])
          && $input["_blank_blueprint"]) {
         $input['blueprint'] = '';
      }

      if (isset($input["_blueprint"])) {
         $filename = array_shift($input["_blueprint"]);
         $src      = GLPI_TMP_DIR."/".$filename;
         $prefix   = '';
         if (isset($input["_prefix_blueprint"])) {
            $prefix = array_shift($input["_prefix_blueprint"]);
         }
         $filename = str_replace($prefix, '', $filename);
         $dest     = GLPI_PICTURE_DIR."/".$filename;
         $moved    = false;

         if (is_file($dest)) {
            @unlink($dest);
         }
         $moved = rename($src, $dest);

         if ($moved) {
            $input['blueprint'] = $CFG_GLPI["root_doc"].
                                  "/front/document.send.php?file=_pictures/$filename";
         }
      }

      return $input;
   }

   function rawSearchOptions() {
      global $CFG_GLPI;

      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false // implicit key==1
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false, // implicit field is id
         'datatype'           => 'number'
      ];

      $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

      $tab[] = [
         'id'                 => '4',
         'table'              => Datacenter::getTable(),
         'field'              => 'name',
         'name'               => __('Datacenter'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'vis_cols',
         'name'               => __('Number of columns'),
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'vis_rows',
         'name'               => __('Number of rows'),
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '121',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      return $tab;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      switch ($item->getType()) {
         case Datacenter::getType():
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
               $nb =countElementsInTable(
                  self::getTable(), [
                     'datacenters_id'  => $item->getID(),
                     'is_deleted'      => 0
                  ]
               );
            }
            return self::createTabEntry(
               self::getTypeName(Session::getPluralNumber()),
               $nb
            );
            break;
      }
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      self::showForDatacenter($item);
   }

   /**
    * Print datacenter's roms
    *
    * @param Datacenter $datacenter Datacenter object
    *
    * @return void
   **/
   static function showForDatacenter(Datacenter $datacenter) {
      global $DB, $CFG_GLPI;

      $ID = $datacenter->getID();
      $rand = mt_rand();

      if (!$datacenter->getFromDB($ID)
          || !$datacenter->can($ID, READ)) {
         return false;
      }
      $canedit = $datacenter->canEdit($ID);

      $rooms = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'datacenters_id' => $datacenter->getID()
         ]
      ]);

      echo "<div class='firstbloc'>";
      Html::showSimpleForm(self::getFormURL(),
                           '_add_fromitem', __('New room for this datacenter...'),
                           ['datacenters_id' => $datacenter->getID()]);
      echo "</div>";

      if ($canedit) {
         $massiveactionparams = [
            'num_displayed'   => min($_SESSION['glpilist_limit'], count($rooms)),
            'container'       => 'mass'.__CLASS__.$rand
         ];
         Html::showMassiveActions($massiveactionparams);
      }

      Session::initNavigateListItems(
         self::getType(),
         //TRANS : %1$s is the itemtype name,
         //        %2$s is the name of the item (used for headings of a list)
         sprintf(
            __('%1$s = %2$s'),
            $datacenter->getTypeName(1),
            $datacenter->getName()
         )
      );

      if (!count($rooms)) {
         echo "<table class='tab_cadre_fixe'><tr><th>".__('No server room found')."</th></tr>";
         echo "</table>";
      } else {
         echo "<table class='tab_cadre_fixehov'>";
         $header = "<tr>";
         if ($canedit) {
            $header .= "<th width='10'>";
            $header .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header .= "</th>";
         }
         $header .= "<th>".__('Name')."</th>";
         $header .= "</tr>";

         $dcroom = new self();
         echo $header;
         while ($room = $rooms->next()) {
            $dcroom->getFromResultSet($room);
            echo "<tr lass='tab_bg_1'>";
            if ($canedit) {
               echo "<td>";
               Html::showMassiveActionCheckBox(__CLASS__, $room["id"]);
               echo "</td>";
            }
            echo "<td>" . $dcroom->getLink() . "</td>";
            echo "</tr>";
         }
         echo $header;
         echo "</table>";
      }

      if ($canedit && count($rooms)) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
      }
      if ($canedit) {
         Html::closeForm();
      }
   }

   /**
    * Get already filled places
    *
    * @param string $current Current position to exclude; defaults to null
    *
    * @return array [x => [pos_x], y => [pos_y]]]
    */
   public function getFilled($current = null) {
      global $DB;

      $iterator = $DB->request([
         'FROM'   => Rack::getTable(),
         'WHERE'  => [
            'dcrooms_id'   => $this->getID(),
            'is_deleted'   => 0
         ]
      ]);

      $filled = [];
      while ($rack = $iterator->next()) {
         if (preg_match('/(\d+),\s?(\d+)/', $rack['position'])) {
            $position = $rack['position'];
            if (empty($current) || $current != $position) {
               $filled[$position] = $position;
            }
         }
      }

      return $filled;
   }

   /**
    * Get all possible positions for current room
    *
    * @return array
    */
   public function getAllPositions() {
      $positions = [];
      for ($x = 1; $x < (int)$this->fields['vis_cols'] + 1; ++$x) {
         for ($y = 1; $y < (int)$this->fields['vis_rows'] + 1; ++$y) {
            $positions["$x,$y"] = sprintf(
               __('col: %1$s, row: %2$s'),
               Toolbox::getBijectiveIndex($x),
               $y
            );
         }
      }
      return $positions;
   }
}
