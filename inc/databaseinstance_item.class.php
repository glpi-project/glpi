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

class DatabaseInstance_Item extends CommonDBRelation {
   use Glpi\Features\Clonable;

   static public $itemtype_1 = 'DatabaseInstance';
   static public $items_id_1 = 'databaseinstances_id';
   static public $take_entity_1 = false;

   static public $itemtype_2 = 'itemtype';
   static public $items_id_2 = 'items_id';
   static public $take_entity_2 = true;

   static function getTypeName($nb = 0) {
      return _n('Item', 'Items', $nb);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!DatabaseInstance::canView()) {
         return '';
      }

      $nb = 0;
      if ($item->getType() == DatabaseInstance::class) {
         if ($_SESSION['glpishow_count_on_tabs'] && !$item->isNewItem()) {
            $nb = self::countForMainItem($item);
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);

      } else if (in_array($item->getType(), DatabaseInstance::getTypes(true))) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = self::countForItem($item);
         }
         return self::createTabEntry(DatabaseInstance::getTypeName(Session::getPluralNumber()), $nb);
      }
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case DatabaseInstance::class:
            self::showItems($item);
            break;
         default :
            if (in_array($item->getType(), DatabaseInstance::getTypes())) {
               self::showForItem($item, $withtemplate);
            }
      }
      return true;
   }

   /**
    * Print database items
    *
    * @param DatabaseInstance $database  Database object wanted
    *
    * @return void|boolean (display) Returns false if there is a rights error.
   **/
   static function showItems(DatabaseInstance $database) {
      global $DB;

      $ID = $database->fields['id'];
      $rand = mt_rand();

      if (!$database->getFromDB($ID)
          || !$database->can($ID, READ)) {
         return false;
      }
      $canedit = $database->canEdit($ID);

      $items = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => [
            self::$items_id_1 => $ID
         ]
      ]);

      Session::initNavigateListItems(
         self::getType(),
         //TRANS : %1$s is the itemtype name,
         //        %2$s is the name of the item (used for headings of a list)
         sprintf(
            __('%1$s = %2$s'),
            $database->getTypeName(1),
            $database->getName()
         )
      );

      if ($database->canAddItem('itemtype')) {
         echo "<div class='firstbloc'>";
         echo "<form method='post' name='databases_form$rand'
                     id='databases_form$rand'
                     action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'>";
         echo "<th colspan='2'>" .
               __('Add an item') . "</th></tr>";

         echo "<tr class='tab_bg_1'><td class='center'>";
         Dropdown::showSelectItemFromItemtypes(
               ['items_id_name'   => 'items_id',
                'itemtypes'       => DatabaseInstance::getTypes(true),
                'entity_restrict' => ($database->fields['is_recursive']
                                      ? getSonsOf('glpi_entities',
                                       $database->fields['entities_id'])
                                       : $database->fields['entities_id']),
                'checkright'      => true,
               ]);
         echo "</td><td class='center' class='tab_bg_1'>";
         echo Html::hidden('databaseinstances_id', ['value' => $ID]);
         echo Html::submit(_x('button', 'Add'), ['name' => 'add']);
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      $items = iterator_to_array($items);

      if (!count($items)) {
         echo "<table class='tab_cadre_fixe'><tr><th>".__('No item found')."</th></tr>";
         echo "</table>";
      } else {
         if ($canedit) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = [
               'num_displayed'   => min($_SESSION['glpilist_limit'], count($items)),
               'container'       => 'mass'.__CLASS__.$rand
            ];
            Html::showMassiveActions($massiveactionparams);
         }

         echo "<table class='tab_cadre_fixehov'>";
         $header = "<tr>";
         if ($canedit) {
            $header .= "<th width='10'>";
            $header .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header .= "</th>";
         }
         $header .= "<th>".__('Itemtype')."</th>";
         $header .= "<th>"._n('Item', 'Items', 1)."</th>";
         $header .= "</tr>";
         echo $header;

         foreach ($items as $row) {
            if (!($item = getItemForItemtype($row['itemtype']))) {
               continue;
            }
            $item->getFromDB($row['items_id']);
            echo "<tr lass='tab_bg_1'>";
            if ($canedit) {
               echo "<td>";
               Html::showMassiveActionCheckBox(__CLASS__, $row["id"]);
               echo "</td>";
            }
            echo "<td>" . $item->getTypeName(1) . "</td>";
            echo "<td>" . $item->getLink() . "</td>";
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

   /**
    * Print an HTML array of databases associated to an object
    *
    * @since 10.0.0
    *
    * @param CommonDBTM $item         CommonDBTM object wanted
    * @param boolean    $withtemplate not used (to be deleted)
    *
    * @return void
   **/
   static function showForItem(CommonDBTM $item, $withtemplate = 0) {

      $itemtype = $item->getType();
      $ID       = $item->fields['id'];

      if (!DatabaseInstance::canView()
          || !$item->can($ID, READ)) {
         return;
      }

      $canedit = $item->can($ID, UPDATE);
      $rand = mt_rand();

      $iterator = self::getListForItem($item);
      $number = count($iterator);

      $databases = [];
      $used      = [];
      while ($data = $iterator->next()) {
         $databases[$data['id']] = $data;
         $database = new DatabaseInstance();
         $database->getFromDB($data['id']);
         $instances = DatabaseInstance_Item::getItemsAssociatedTo($database::getType(), $data['id']);
         $databases[$data['id']]['instances'] = $instances;
         $used[$data['id']]      = $data['id'];
      }
      if ($canedit && ($withtemplate != 2)) {
         echo "<div class='firstbloc'>";
         echo "<form name='databaseitem_form$rand' id='databaseitem_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         echo "<input type='hidden' name='items_id' value='$ID'>";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add link to a database')."</th></tr>";

         echo "<tr class='tab_bg_1'><td>";
         DatabaseInstance::dropdown([
            'entity'  => $item->getEntityID(),
            'used'    => $used
         ]);

         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($withtemplate != 2) {
         if ($canedit && $number) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = ['num_displayed' => min($_SESSION['glpilist_limit'], $number),
                                         'container'     => 'mass'.__CLASS__.$rand];
            Html::showMassiveActions($massiveactionparams);
         }
      }
      echo "<table class='tab_cadre_fixehov'>";

      $header = "<tr>";
      if ($canedit && $number && ($withtemplate != 2)) {
         $header    .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header    .= "</th>";
      }

      $header .= "<th>".__('Database name')."</th>";
      $header .= "<th>".__('Instance name')."</th>";
      $header .= "<th>"._n('Port', 'Ports', 1)."</th>";
      $header .= "<th>".__('Size')."</th>";
      $header .= "<th>".__('Is active')."</th>";
      $header .= "</tr>";

      if ($number > 0) {
         echo $header;
         Session::initNavigateListItems(__CLASS__,
                              //TRANS : %1$s is the itemtype name,
                              //         %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $item->getTypeName(1), $item->getName()));
         foreach ($databases as $data) {
            $cID         = $data["id"];
            Session::addToNavigateListItems(__CLASS__, $cID);
            $assocID     = $data["linkid"];
            $database    = new DatabaseInstance();
            $database->getFromResultSet($data);
            echo "<tr class='tab_bg_1" . ($database->fields["is_deleted"] ? "_2" : "") . "'>";
            if ($canedit && ($withtemplate != 2)) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $assocID);
               echo "</td>";
            }
            echo "<td class='b'>";
            $name = $database->fields["name"];
            if ($_SESSION["glpiis_ids_visible"]
               || empty($database->fields["name"])) {
               $name = sprintf(__('%1$s (%2$s)'), $name, $database->fields["id"]);
            }
            echo "<a href='" . DatabaseInstance::getFormURLWithID($cID) . "'>" . $name . "</a>";
            echo "</td>";
            echo "<td>".$database->fields['name']."</td>";
            echo "<td>".$database->fields['port']."</td>";
            echo "<td>".$database->fields['size']."</td>";
            echo "<td>".Dropdown::getYesNo($database->fields['is_active'])."</td>";
            echo "</tr>";
         }
         echo $header;
         echo "</table>";
      } else {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".__('No item found')."</th></tr></table>";
      }

      echo "</table>";
      if ($canedit && $number && ($withtemplate != 2)) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   function prepareInputForAdd($input) {
      return $this->prepareInput($input);
   }

   function prepareInputForUpdate($input) {
      return $this->prepareInput($input);
   }

   /**
    * Prepares input (for update and add)
    *
    * @param array $input Input data
    *
    * @return array
    */
   private function prepareInput($input) {
      $error_detected = [];

      //check for requirements
      if (($this->isNewItem() && (!isset($input['itemtype']) || empty($input['itemtype'])))
          || (isset($input['itemtype']) && empty($input['itemtype']))) {
         $error_detected[] = __('An item type is required');
      }
      if (($this->isNewItem() && (!isset($input['items_id']) || empty($input['items_id'])))
          || (isset($input['items_id']) && empty($input['items_id']))) {
         $error_detected[] = __('An item is required');
      }
      if (($this->isNewItem() && (!isset($input[self::$items_id_1]) || empty($input[self::$items_id_1])))
          || (isset($input[self::$items_id_1]) && empty($input[self::$items_id_1]))) {
         $error_detected[] = __('A database is required');
      }

      if (count($error_detected)) {
         foreach ($error_detected as $error) {
            Session::addMessageAfterRedirect(
               $error,
               true,
               ERROR
            );
         }
         return false;
      }

      return $input;
   }

   public static function countForMainItem(CommonDBTM $item, $extra_types_where = []) {
      $types = DatabaseInstance::getTypes();
      $clause = [];
      if (count($types)) {
         $clause = ['itemtype' => $types];
      } else {
         $clause = [new \QueryExpression('true = false')];
      }
      $extra_types_where = array_merge(
         $extra_types_where,
         $clause
      );
      return parent::countForMainItem($item, $extra_types_where);
   }

   function getForbiddenStandardMassiveAction() {
      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      $forbidden[] = 'CommonDBConnexity:unaffect';
      $forbidden[] = 'CommonDBConnexity:affect';
      return $forbidden;
   }

   static function getRelationMassiveActionsSpecificities() {
      global $CFG_GLPI;

      $specificities              = parent::getRelationMassiveActionsSpecificities();
      $specificities['itemtypes'] = DatabaseInstance::getTypes();

      return $specificities;
   }
}
