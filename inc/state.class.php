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
 * State Class
**/
class State extends CommonTreeDropdown {

   protected $visibility_fields    = ['Computer'         => 'is_visible_computer',
                                      'SoftwareVersion'  => 'is_visible_softwareversion',
                                      'Monitor'          => 'is_visible_monitor',
                                      'Printer'          => 'is_visible_printer',
                                      'Peripheral'       => 'is_visible_peripheral',
                                      'Phone'            => 'is_visible_phone',
                                      'NetworkEquipment' => 'is_visible_networkequipment',
                                      'SoftwareLicense'  => 'is_visible_softwarelicense',
                                      'Line'             => 'is_visible_line',
                                      'Certificate'      => 'is_visible_certificate',
                                      'Rack'             => 'is_visible_rack',
                                      'Enclosure'        => 'is_visible_enclosure',
                                      'Pdu'              => 'is_visible_pdu',];
   public $can_be_translated       = true;

   static $rightname               = 'state';



   static function getTypeName($nb = 0) {
      return _n('Status of items', 'Statuses of items', $nb);
   }


   /**
    * @since 0.85
    *
    * @see CommonTreeDropdown::getAdditionalFields()
   **/
   function getAdditionalFields() {

      $fields   = parent::getAdditionalFields();
      $fields[] = ['label' => __('Visibility'),
                        'name'  => 'header',
                        'list'  => false];

      foreach ($this->visibility_fields as $type => $field) {
         $fields[] = ['name'  => $field,
                           'label' => $type::getTypeName(Session::getPluralNumber()),
                           'type'  => 'bool',
                           'list'  => true];
      }
      return $fields;
   }


   /**
    * Dropdown of states for behaviour config
    *
    * @param $name            select name
    * @param $lib    string   to add for -1 value (default '')
    * @param $value           default value (default 0)
   **/
   static function dropdownBehaviour($name, $lib = "", $value = 0) {
      global $DB;

      $elements = ["0" => __('Keep status')];

      if ($lib) {
         $elements["-1"] = $lib;
      }

      $queryStateList = "SELECT `id`, `name`
                         FROM `glpi_states`
                         ORDER BY `name`";
      $result = $DB->query($queryStateList);

      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_assoc($result)) {
            $elements[$data["id"]] = sprintf(__('Set status: %s'), $data["name"]);
         }
      }
      Dropdown::showFromArray($name, $elements, ['value' => $value]);
   }


   static function showSummary() {
      global $DB, $CFG_GLPI;

      $state_type = $CFG_GLPI["state_types"];
      $states     = [];

      foreach ($state_type as $key=>$itemtype) {
         if ($item = getItemForItemtype($itemtype)) {
            if (!$item->canView()) {
               unset($state_type[$key]);

            } else {
               $table = getTableForItemType($itemtype);
               $query = "SELECT `states_id`, COUNT(*) AS cpt
                         FROM `$table` ".
                         getEntitiesRestrictRequest("WHERE", $table)."
                              AND `is_deleted` = 0
                              AND `is_template` = 0
                         GROUP BY `states_id`";

               if ($result = $DB->query($query)) {
                  if ($DB->numrows($result) > 0) {
                     while ($data = $DB->fetch_assoc($result)) {
                        $states[$data["states_id"]][$itemtype] = $data["cpt"];
                     }
                  }
               }
            }
         }
      }

      if (count($states)) {
         // Produce headline
         echo "<div class='center'><table class='tab_cadrehov'><tr>";

         // Type
         echo "<th>".__('Status')."</th>";

         foreach ($state_type as $key => $itemtype) {
            if ($item = getItemForItemtype($itemtype)) {
               echo "<th>".$item->getTypeName(Session::getPluralNumber())."</th>";
               $total[$itemtype] = 0;
            } else {
               unset($state_type[$key]);
            }
         }

         echo "<th>".__('Total')."</th>";
         echo "</tr>";
         $query = "SELECT *
                   FROM `glpi_states` ".
                   getEntitiesRestrictRequest("WHERE", "glpi_states", '', '', true)."
                   ORDER BY `completename`";
         $result = $DB->query($query);

         // No state
         $tot = 0;
         echo "<tr class='tab_bg_2'><td>---</td>";
         foreach ($state_type as $itemtype) {
            echo "<td class='numeric'>";

            if (isset($states[0][$itemtype])) {
               echo $states[0][$itemtype];
               $total[$itemtype] += $states[0][$itemtype];
               $tot              += $states[0][$itemtype];
            } else {
               echo "&nbsp;";
            }

            echo "</td>";
         }
         echo "<td class='numeric b'>$tot</td></tr>";

         while ($data = $DB->fetch_assoc($result)) {
            $tot = 0;
            echo "<tr class='tab_bg_2'><td class='b'>";

            $opt = ['reset'    => 'reset',
                        'sort'     => 1,
                        'start'    => 0,
                        'criteria' => ['0' => ['value' => '$$$$'.$data['id'],
                                                         'searchtype' => 'contains',
                                                         'field' => 31]]];
            echo "<a href='".$CFG_GLPI['root_doc']."/front/allassets.php?".Toolbox::append_params($opt, '&amp;')."'>".$data["completename"]."</a></td>";

            foreach ($state_type as $itemtype) {
               echo "<td class='numeric'>";

               if (isset($states[$data["id"]][$itemtype])) {
                  echo $states[$data["id"]][$itemtype];
                  $total[$itemtype] += $states[$data["id"]][$itemtype];
                  $tot              += $states[$data["id"]][$itemtype];
               } else {
                  echo "&nbsp;";
               }

               echo "</td>";
            }
            echo "<td class='numeric b'>$tot</td>";
            echo "</tr>";
         }
         echo "<tr class='tab_bg_2'><td class='center b'>".__('Total')."</td>";
         $tot = 0;

         foreach ($state_type as $itemtype) {
            echo "<td class='numeric b'>".$total[$itemtype]."</td>";
            $tot += $total[$itemtype];
         }

         echo "<td class='numeric b'>$tot</td></tr>";
         echo "</table></div>";

      } else {
         echo "<div class='center b'>".__('No item found')."</div>";
      }
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::getEmpty()
   **/
   function getEmpty() {

      parent::getEmpty();
      //initialize is_visible_* fields at true to keep the same behavior as in older versions
      foreach ($this->visibility_fields as $type => $field) {
         $this->fields[$field] = 1;
      }
   }


   function cleanDBonPurge() {
      Rule::cleanForItemCriteria($this);
   }


   /**
    * @since 0.85
    *
    * @see CommonTreeDropdown::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {
      if (!isset($input['states_id'])) {
         $input['states_id'] = 0;
      }
      if (!$this->isUnique($input)) {
         Session::addMessageAfterRedirect(
            sprintf(__('%1$s must be unique!'), $this->getType(1)),
            false,
            ERROR
         );
         return false;
      }

      $input = parent::prepareInputForAdd($input);

      $state = new self();
      // Get visibility information from parent if not set
      if (isset($input['states_id']) && $state->getFromDB($input['states_id'])) {
         foreach ($this->visibility_fields as $type => $field) {
            if (!isset($input[$field]) && isset($state->fields[$field])) {
               $input[$field] = $state->fields[$field];
            }
         }
      }
      return $input;
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '21',
         'table'              => $this->getTable(),
         'field'              => 'is_visible_computer',
         'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'), Computer::getTypeName(Session::getPluralNumber())),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '22',
         'table'              => $this->getTable(),
         'field'              => 'is_visible_softwareversion',
         'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'),
                                     SoftwareVersion::getTypeName(Session::getPluralNumber())),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '23',
         'table'              => $this->getTable(),
         'field'              => 'is_visible_monitor',
         'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'), Monitor::getTypeName(Session::getPluralNumber())),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '24',
         'table'              => $this->getTable(),
         'field'              => 'is_visible_printer',
         'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'), Printer::getTypeName(Session::getPluralNumber())),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '25',
         'table'              => $this->getTable(),
         'field'              => 'is_visible_peripheral',
         'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'), Peripheral::getTypeName(Session::getPluralNumber())),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '26',
         'table'              => $this->getTable(),
         'field'              => 'is_visible_phone',
         'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'), Phone::getTypeName(Session::getPluralNumber())),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '27',
         'table'              => $this->getTable(),
         'field'              => 'is_visible_networkequipment',
         'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'),
                                     NetworkEquipment::getTypeName(Session::getPluralNumber())),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '28',
         'table'              => $this->getTable(),
         'field'              => 'is_visible_softwarelicense',
         'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'),
                                     SoftwareLicense::getTypeName(Session::getPluralNumber())),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '29',
         'table'              => $this->getTable(),
         'field'              => 'is_visible_certificate',
         'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'),
                                     Certificate::getTypeName(Session::getPluralNumber())),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '30',
         'table'              => $this->getTable(),
         'field'              => 'is_visible_rack',
         'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'),
                                     Rack::getTypeName(Session::getPluralNumber())),
         'datatype'           => 'bool'
      ];

      return $tab;
   }

   function prepareInputForUpdate($input) {
      if (!$this->isUnique($input)) {
         Session::addMessageAfterRedirect(
            sprintf(__('%1$s must be unique per level!'), $this->getType(1)),
            false,
            ERROR
         );
         return false;
      }
      return parent::prepareInputForUpdate($input);
   }

   public function isUnique($input) {
      global $DB;

      $unicity_fields = ['states_id', 'name'];

      $has_changed = false;
      $where = [];
      foreach ($unicity_fields as $unicity_field) {
         if (!isset($this->fields[$unicity_field]) || $input[$unicity_field] != $this->fields[$unicity_field]) {
            $has_changed = true;
         }
         $where[$unicity_field] = $input[$unicity_field];
      }
      if (!$has_changed) {
         //state has not changed; this is OK.
         return true;
      }

      $query = [
         'FROM'   => $this->getTable(),
         'COUNT'  => 'cpt',
         'WHERE'  => $where
      ];
      $row = $DB->request($query)->next();
      return (int)$row['cpt'] == 0;
   }
}
