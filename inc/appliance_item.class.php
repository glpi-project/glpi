<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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
class Appliance_Item extends CommonDBRelation {
   use Glpi\Features\Clonable;

   static public $itemtype_1 = 'Appliance';
   static public $items_id_1 = 'appliances_id';
   static public $take_entity_1 = false;

   static public $itemtype_2 = 'itemtype';
   static public $items_id_2 = 'items_id';
   static public $take_entity_2 = true;

   public function getCloneRelations() :array {
      return [
         Appliance_Item_Relation::class
      ];
   }

   static function getTypeName($nb = 0) {
      return _n('Item', 'Items', $nb);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!Appliance::canView()) {
         return '';
      }

      $nb = 0;
      if ($item->getType() == Appliance::class) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            if (!$item->isNewItem()) {
               $nb = self::countForMainItem($item);
            }
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);

      } else if (in_array($item->getType(), Appliance::getTypes(true))) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = self::countForItem($item);
         }
         return self::createTabEntry(Appliance::getTypeName(Session::getPluralNumber()), $nb);
      }
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case Appliance::class:
            self::showItems($item);
            break;
         default :
            if (in_array($item->getType(), Appliance::getTypes())) {
               self::showForItem($item, $withtemplate);
            }
      }
      return true;
   }

   /**
    * Print enclosure items
    *
    * @param Appliance $appliance  Appliance object wanted
    *
    * @return void|boolean (display) Returns false if there is a rights error.
   **/
   static function showItems(Appliance $appliance) {
      global $DB;

      $ID = $appliance->fields['id'];
      $rand = mt_rand();

      if (!$appliance->getFromDB($ID)
          || !$appliance->can($ID, READ)) {
         return false;
      }
      $canedit = $appliance->canEdit($ID);

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
            $appliance->getTypeName(1),
            $appliance->getName()
         )
      );

      if ($appliance->canAddItem('itemtype')) {
         echo "<div class='firstbloc'>";
         echo "<form method='post' name='appliances_form$rand'
                     id='appliances_form$rand'
                     action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'>";
         echo "<th colspan='2'>" .
               __('Add an item') . "</th></tr>";

         echo "<tr class='tab_bg_1'><td class='center'>";
         Dropdown::showSelectItemFromItemtypes(
               ['items_id_name'   => 'items_id',
                'itemtypes'       => Appliance::getTypes(true),
                'entity_restrict' => ($appliance->fields['is_recursive']
                                      ? getSonsOf('glpi_entities',
                                       $appliance->fields['entities_id'])
                                       : $appliance->fields['entities_id']),
                'checkright'      => true,
               ]);
         echo "</td><td class='center' class='tab_bg_1'>";
         echo Html::hidden('appliances_id', ['value' => $ID]);
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
         $header .= "<th>".__("Serial")."</th>";
         $header .= "<th>".__("Inventory number")."</th>";
         $header .= "<th>".Appliance_Item_Relation::getTypeName(Session::getPluralNumber())."</th>";
         $header .= "</tr>";
         echo $header;

         foreach ($items as $row) {
            $item = new $row['itemtype'];
            $item->getFromDB($row['items_id']);
            echo "<tr lass='tab_bg_1'>";
            if ($canedit) {
               echo "<td>";
               Html::showMassiveActionCheckBox(__CLASS__, $row["id"]);
               echo "</td>";
            }
            echo "<td>" . $item->getTypeName(1) . "</td>";
            echo "<td>" . $item->getLink() . "</td>";
            echo "<td>" . ($item->fields['serial'] ?? "") . "</td>";
            echo "<td>" . ($item->fields['otherserial'] ?? "") . "</td>";
            echo "<td class='relations_list'>";
            echo Appliance_Item_Relation::showListForApplianceItem($row["id"], $canedit);
            echo "</td>";
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

         echo Appliance_Item_Relation::getListJSForApplianceItem($appliance, $canedit);
      }
   }

   /**
    * Print an HTML array of appliances associated to an object
    *
    * @since 9.5.2
    *
    * @param CommonDBTM $item         CommonDBTM object wanted
    * @param boolean    $withtemplate not used (to be deleted)
    *
    * @return void
   **/
   static function showForItem(CommonDBTM $item, $withtemplate = 0) {

      $itemtype = $item->getType();
      $ID       = $item->fields['id'];

      if (!Appliance::canView()
          || !$item->can($ID, READ)) {
         return;
      }

      $canedit = $item->can($ID, UPDATE);
      $rand = mt_rand();

      $iterator = self::getListForItem($item);
      $number = count($iterator);

      $appliances = [];
      $used      = [];
      while ($data = $iterator->next()) {
         $appliances[$data['id']] = $data;
         $used[$data['id']]      = $data['id'];
      }
      if ($canedit && ($withtemplate != 2)) {
         echo "<div class='firstbloc'>";
         echo "<form name='applianceitem_form$rand' id='applianceitem_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         echo "<input type='hidden' name='items_id' value='$ID'>";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add to an appliance')."</th></tr>";

         echo "<tr class='tab_bg_1'><td>";
         Appliance::dropdown([
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

      $header .= "<th>".__('Name')."</th>";
      $header .= "<th>".Appliance_Item_Relation::getTypeName(Session::getPluralNumber())."</th>";
      $header .= "</tr>";

      if ($number > 0) {
         echo $header;
         Session::initNavigateListItems(__CLASS__,
                              //TRANS : %1$s is the itemtype name,
                              //         %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $item->getTypeName(1), $item->getName()));
         foreach ($appliances as $data) {
            $cID         = $data["id"];
            Session::addToNavigateListItems(__CLASS__, $cID);
            $assocID     = $data["linkid"];
            $app         = new Appliance();
            $app->getFromResultSet($data);
            echo "<tr class='tab_bg_1".($app->fields["is_deleted"]?"_2":"")."'>";
            if ($canedit && ($withtemplate != 2)) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $assocID);
               echo "</td>";
            }
            echo "<td class='b'>";
            $name = $app->fields["name"];
            if ($_SESSION["glpiis_ids_visible"]
                || empty($app->fields["name"])) {
               $name = sprintf(__('%1$s (%2$s)'), $name, $app->fields["id"]);
            }
            echo "<a href='".Appliance::getFormURLWithID($cID)."'>".$name."</a>";
            echo "</td>";
            echo "<td class='relations_list'>";
            echo Appliance_Item_Relation::showListForApplianceItem($assocID, $canedit);
            echo "</td>";

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

      echo Appliance_Item_Relation::getListJSForApplianceItem($item, $canedit);
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
         $error_detected[] = __('An appliance is required');
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
      $types = Appliance::getTypes();
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
      $specificities['itemtypes'] = Appliance::getTypes();

      return $specificities;
   }

   function cleanDBonPurge() {
      $this->deleteChildrenAndRelationsFromDb(
         [
            Appliance_Item_Relation::class,
         ]
      );
   }
}
