<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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
   die("Sorry. You can't access directly to this file");
}

class Item_OperatingSystem extends CommonDBRelation {

   static public $itemtype_1 = 'OperatingSystem';
   static public $items_id_1 = 'operatingsystems_id';
   static public $itemtype_2 = 'itemtype';
   static public $items_id_2 = 'items_id';
   static public $checkItem_1_Rights = self::DONT_CHECK_ITEM_RIGHTS;


   static function getTypeName($nb = 0) {
      return _n('Item operating system', 'Item operating systems', $nb);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $nb = 0;
      switch ($item->getType()) {
         default:
            if ($_SESSION['glpishow_count_on_tabs']) {
               $nb = self::countForItem($item);
            }
            return self::createTabEntry(OperatingSystem::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      self::showForItem($item, $withtemplate);
   }

   /**
    * Get operating systems related to a given item
    *
    * @param CommonDBTM $item  Item instance
    * @param string     $sort  Field to sort on
    * @param string     $order Sort order
    *
    * @return DBmysqlIterator
    */
   public static function getFromItem(CommonDBTM $item, $sort = null, $order = null): DBmysqlIterator {
      global $DB;

      if ($sort === null) {
         $sort = "glpi_items_operatingsystems.id";
      }
      if ($order === null) {
         $order = 'ASC';
      }

      $iterator = $DB->request([
         'SELECT'    => [
            'glpi_items_operatingsystems.id AS assocID',
            'glpi_operatingsystems.name',
            'glpi_operatingsystemversions.name AS version',
            'glpi_operatingsystemarchitectures.name AS architecture',
            'glpi_operatingsystemservicepacks.name AS servicepack'
         ],
         'FROM'      => 'glpi_items_operatingsystems',
         'LEFT JOIN' => [
            'glpi_operatingsystems'             => [
               'ON' => [
                  'glpi_items_operatingsystems' => 'operatingsystems_id',
                  'glpi_operatingsystems'       => 'id'
               ]
            ],
            'glpi_operatingsystemservicepacks'  => [
               'ON' => [
                  'glpi_items_operatingsystems'       => 'operatingsystemservicepacks_id',
                  'glpi_operatingsystemservicepacks'  => 'id'
               ]
            ],
            'glpi_operatingsystemarchitectures' => [
               'ON' => [
                  'glpi_items_operatingsystems'       => 'operatingsystemarchitectures_id',
                  'glpi_operatingsystemarchitectures' => 'id'
               ]
            ],
            'glpi_operatingsystemversions'      => [
               'ON' => [
                  'glpi_items_operatingsystems'    => 'operatingsystemversions_id',
                  'glpi_operatingsystemversions'   => 'id'
               ]
            ]
         ],
         'WHERE'     => [
            'glpi_items_operatingsystems.itemtype' => $item->getType(),
            'glpi_items_operatingsystems.items_id' => $item->getID()
         ],
         'ORDERBY'   => "$sort $order"
      ]);
      return $iterator;
   }

   /**
    * Print the item's operating system form
    *
    * @param CommonDBTM $item Item instance
    *
    * @since 9.2
    *
    * @return void
   **/
   static function showForItem(CommonDBTM $item, $withtemplate = 0) {
      global $DB;

      //default options
      $params = ['rand' => mt_rand()];

      $columns = [
         __('Name'),
         _n('Version', 'Versions', 1),
         _n('Architecture', 'Architectures', 1),
         OperatingSystemServicePack::getTypeName(1)
      ];

      if (isset($_GET["order"]) && ($_GET["order"] == "ASC")) {
         $order = "ASC";
      } else {
         $order = "DESC";
      }

      if ((isset($_GET["sort"]) && !empty($_GET["sort"]))
         && isset($columns[$_GET["sort"]])) {
         $sort = $_GET["sort"];
      } else {
         $sort = "glpi_items_operatingsystems.id";
      }

      if (empty($withtemplate)) {
         $withtemplate = 0;
      }

      $iterator = self::getFromItem($item, $sort, $order);
      $number = count($iterator);
      $i      = 0;

      $os = [];
      foreach ($iterator as $data) {
         $os[$data['assocID']] = $data;
      }

      $canedit = $item->canEdit($item->getID());

      //multi OS for an item is not an existing feature right now.
      /*if ($canedit && $number >= 1
          && !(!empty($withtemplate) && ($withtemplate == 2))) {
         echo "<div class='center firstbloc'>".
            "<a class='btn btn-primary' href='" . Toolbox::getItemTypeFormURL(self::getType()) . "?items_id=" . $item->getID() .
            "&amp;itemtype=" . $item->getType() . "&amp;withtemplate=" . $withtemplate."'>";
         echo __('Add an operating system');
         echo "</a></div>\n";
      }*/

      if ($number <= 1) {
         $id = -1;
         $instance = new self();
         if ($number > 0) {
            $id = array_keys($os)[0];
         } else {
            //set itemtype and items_id
            $instance->fields['itemtype']    = $item->getType();
            $instance->fields['items_id']    = $item->getID();
            $instance->fields['entities_id'] = $item->fields['entities_id'];
         }
         $instance->showForm($id, ['canedit' => $canedit]);
         return;
      }

      echo "<div class='spaced'>";
      if ($canedit
          && $number
          && ($withtemplate < 2)) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$params['rand']);
         $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $number),
                                      'container'      => 'mass'.__CLASS__.$params['rand']];
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";

      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit
          && $number
          && ($withtemplate < 2)) {
         $header_top    .= "<th width='11'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$params['rand']);
         $header_top    .= "</th>";
         $header_bottom .= "<th width='11'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$params['rand']);
         $header_bottom .= "</th>";
      }

      foreach ($columns as $key => $val) {
         $header_end .= "<th".($sort == $key ? " class='order_$order'" : '').">".
                        "<a href='javascript:reloadTab(\"sort=$key&amp;order=".
                          (($order == "ASC") ?"DESC":"ASC")."&amp;start=0\");'>$val</a></th>";
      }

      $header_end .= "</tr>";
      echo $header_begin.$header_top.$header_end;

      if ($number) {
         foreach ($os as $data) {
            $linkname = $data['name'];
            if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
               $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["assocID"]);
            }
            $link = Toolbox::getItemTypeFormURL(self::getType());
            $name = "<a href=\"".$link."?id=".$data["assocID"]."\">".$linkname."</a>";

            echo "<tr class='tab_bg_1'>";
            if ($canedit
                && ($withtemplate < 2)) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["assocID"]);
               echo "</td>";
            }
            echo "<td class='center'>{$name}</td>";
            echo "<td class='center'>{$data['version']}</td>";
            echo "<td class='center'>{$data['architecture']}</td>";
            echo "<td class='center'>{$data['servicepack']}</td>";

            echo "</tr>";
            $i++;
         }
         echo $header_begin.$header_bottom.$header_end;
      }

      echo "</table>";
      if ($canedit && $number && ($withtemplate < 2)) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";

   }

   function getConnexityItem($itemtype, $items_id, $getFromDB = true, $getEmpty = true,
                             $getFromDBOrEmpty = true) {
      //overrided to set $getFromDBOrEmpty to true
      return parent::getConnexityItem($itemtype, $items_id, $getFromDB, $getEmpty, $getFromDBOrEmpty);
   }

   function showForm($ID, array $options = []) {
      $colspan = 4;

      echo "<div class='center'>";

      $this->initForm($ID, $this->fields);
      $this->showFormHeader(['formtitle' => false]);

      $rand = mt_rand();

      echo "<tr class='headerRow'><th colspan='".$colspan."'>";
      echo OperatingSystem::getTypeName(1);
      echo Html::hidden('itemtype', ['value' => $this->fields['itemtype']]);
      echo Html::hidden('items_id', ['value' => $this->fields['items_id']]);
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_operatingsystems_id$rand'>".__('Name')."</label></td>";
      echo "<td>";
      OperatingSystem::dropdown(['value' => $this->fields["operatingsystems_id"], 'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_operatingsystemversions_id$rand'>"._n('Version', 'Versions', 1)."</label></td>";
      echo "<td >";
      OperatingSystemVersion::dropdown(['value' => $this->fields["operatingsystemversions_id"], 'rand' => $rand]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_operatingsystemarchitectures_id$rand'>"._n('Architecture', 'Architectures', 1)."</label></td>";
      echo "<td >";
      OperatingSystemArchitecture::dropdown(['value'
                                                 => $this->fields["operatingsystemarchitectures_id"], 'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_operatingsystemservicepacks_id$rand'>".OperatingSystemServicePack::getTypeName(1)."</label></td>";
      echo "<td >";
      OperatingSystemServicePack::dropdown(['value'
                                                 => $this->fields["operatingsystemservicepacks_id"], 'rand' => $rand]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_operatingsystemkernelversions_id$rand'>"._n('Kernel', 'Kernels', 1)."</label></td>";
      echo "<td >";
      OperatingSystemKernelVersion::dropdown([
         'value'  => $this->fields['operatingsystemkernelversions_id'],
         'rand'   => $rand,
         'displaywith'  => ['operatingsystemkernels_id']
      ]);
      echo "</td>";

      echo "<td><label for='dropdown_operatingsystemeditions_id$rand'>" . _n('Edition', 'Editions', 1) . "</label></td>";
      echo "<td >";
      OperatingSystemEdition::dropdown([
         'value'  => $this->fields['operatingsystemeditions_id'],
         'rand'   => $rand
      ]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='textfield_licenseid$rand'>".__('Product ID')."</label></td>";
      echo "<td >";
      echo Html::input(
         'licenseid',
         [
            'value' => $this->fields['licenseid'],
            'id'    => "textfield_licenseid$rand",
         ]
      );
      echo "</td>";

      echo "<td><label for='textfield_license_number$rand'>".__('Serial number')."</label></td>";
      echo "<td >";
      echo Html::input(
         'license_number',
         [
            'value' => $this->fields['license_number'],
            'id'    => "textfield_license_number$rand",
         ]
      );
      echo "</td></tr>";
      $options['formfooter'] = false;
      $this->showFormButtons($options);
   }

   protected function computeFriendlyName() {
      $item = getItemForItemtype($this->fields['itemtype']);
      $item->getFromDB($this->fields['items_id']);
      $name = $item->getTypeName(1) . ' ' . $item->getName();

      return $name;
   }

   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'license_number',
         'name'               => __('Serial number'),
         'datatype'           => 'string',
         'massiveaction'      => false,
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'licenseid',
         'name'               => __('Product ID'),
         'datatype'           => 'string',
         'massiveaction'      => false,
      ];

      return $tab;
   }

   public static function rawSearchOptionsToAdd($itemtype) {
      $tab = [];
      $tab[] = [
          'id'                => 'operatingsystem',
          'name'              => __('Operating System')
      ];

      $tab[] = [
         'id'                 => '45',
         'table'              => 'glpi_operatingsystems',
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_operatingsystems',
               'joinparams'         => [
                  'jointype'           => 'itemtype_item',
                  'specific_itemtype'  => $itemtype
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '46',
         'table'              => 'glpi_operatingsystemversions',
         'field'              => 'name',
         'name'               => _n('Version', 'Versions', 1),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_operatingsystems',
               'joinparams'         => [
                  'jointype'           => 'itemtype_item',
                  'specific_itemtype'  => $itemtype
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '41',
         'table'              => 'glpi_operatingsystemservicepacks',
         'field'              => 'name',
         'name'               => OperatingSystemServicePack::getTypeName(1),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_operatingsystems',
               'joinparams'         => [
                  'jointype'           => 'itemtype_item',
                  'specific_itemtype'  => $itemtype
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '43',
         'table'              => 'glpi_items_operatingsystems',
         'field'              => 'license_number',
         'name'               => __('Serial number'),
         'datatype'           => 'string',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'itemtype_item',
            'specific_itemtype'  => $itemtype
         ]
      ];

      $tab[] = [
         'id'                 => '44',
         'table'              => 'glpi_items_operatingsystems',
         'field'              => 'licenseid',
         'name'               => __('Product ID'),
         'datatype'           => 'string',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'itemtype_item',
            'specific_itemtype'  => $itemtype
         ]
      ];

      $tab[] = [
         'id'                 => '61',
         'table'              => 'glpi_operatingsystemarchitectures',
         'field'              => 'name',
         'name'               => _n('Architecture', 'Architectures', 1),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_operatingsystems',
               'joinparams'         => [
                  'jointype'           => 'itemtype_item',
                  'specific_itemtype'  => $itemtype
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '64',
         'table'              => 'glpi_operatingsystemkernels',
         'field'              => 'name',
         'name'               => _n('Kernel', 'Kernels', 1),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_operatingsystemkernelversions',
               'joinparams'         => [
                  'beforejoin'   => [
                     'table'        => 'glpi_items_operatingsystems',
                     'joinparams'   => [
                        'jointype'           => 'itemtype_item',
                        'specific_itemtype'  => $itemtype
                     ]
                  ]
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '48',
         'table'              => 'glpi_operatingsystemkernelversions',
         'field'              => 'name',
         'name'               => _n('Kernel version', 'Kernel versions', 1),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_operatingsystems',
               'joinparams'         => [
                  'jointype'           => 'itemtype_item',
                  'specific_itemtype'  => $itemtype
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '63',
         'table'              => 'glpi_operatingsystemeditions',
         'field'              => 'name',
         'name'               => _n('Edition', 'Editions', 1),
         'datatype'           => 'dropdown',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_operatingsystems',
               'joinparams'         => [
                  'jointype'           => 'itemtype_item',
                  'specific_itemtype'  => $itemtype
               ]
            ]
         ]
      ];

      return $tab;
   }


   static function getRelationMassiveActionsSpecificities() {
      global $CFG_GLPI;

      $specificities              = parent::getRelationMassiveActionsSpecificities();

      $specificities['itemtypes'] = $CFG_GLPI['operatingsystem_types'];
      return $specificities;
   }
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'update':
            static::showFormMassiveUpdate($ma);
            return true;
      }

      return parent::showMassiveActionsSubForm($ma);
   }

   static function showFormMassiveUpdate($ma) {
      global $CFG_GLPI;

      $rand = mt_rand();
      Dropdown::showFromArray(
         'os_field', [
            'OperatingSystem'             => __('Name'),
            'OperatingSystemVersion'      => _n('Version', 'Versions', 1),
            'OperatingSystemArchitecture' => _n('Architecture', 'Architectures', 1),
            'OperatingSystemKernel'       => OperatingSystemKernel::getTypeName(1),
            'OperatingSystemKernelVersion'=> OperatingSystemKernelVersion::getTypeName(1),
            'OperatingSystemEdition'      => _n('Edition', 'Editions', 1)
         ], [
            'display_emptychoice'   => true,
            'rand'                  => $rand
         ]
      );

      Ajax::updateItemOnSelectEvent(
         "dropdown_os_field$rand",
         "results_os_field$rand",
         $CFG_GLPI["root_doc"].
         "/ajax/dropdownMassiveActionOs.php",
         [
            'itemtype'  => '__VALUE__',
            'rand'      => $rand
         ]
      );
      echo "<span id='results_os_field$rand'></span> \n";
   }

   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'update':
            $input = $ma->getInput();
            unset($input['update']);
            unset($input['os_field']);
            $ios = new Item_OperatingSystem();
            foreach ($ids as $id) {
               if ($item->getFromDB($id)) {
                  if ($item->can($id, UPDATE, $input)) {
                     $exists = $ios->getFromDBByCrit([
                        'itemtype'  => $item->getType(),
                        'items_id'  => $item->getID()
                     ]);
                     $ok = false;
                     if ($exists) {
                        $ok = $ios->update(['id'  => $ios->getID()] + $input);
                     } else {
                        $ok = $ios->add(['itemtype' => $item->getType(), 'items_id' => $item->getID()] + $input);
                     }

                     if ($ok != false) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }

                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
               }
            }
            break;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }

   public function prepareInputForAdd($input) {
      $item = getItemForItemtype($input['itemtype']);
      $item->getFromDB($input['items_id']);
      $input['entities_id'] = $item->fields['entities_id'];
      $input['is_recursive'] = $item->fields['is_recursive'];
      return $input;
   }
}
