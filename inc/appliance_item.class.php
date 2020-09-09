<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

class Appliance_Item extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1     = 'Appliance';
   static public $items_id_1     = 'appliances_id';
   static public $take_entity_1  = false;

   static public $itemtype_2     = 'itemtype';
   static public $items_id_2     = 'items_id';
   static public $take_entity_2  = true;

   static public $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;


   static function getTypeName($nb = 0) {
      return _n('Appliance item', 'Appliances items', $nb);
   }


   function cleanDBonPurge() {
      $temp = new ApplianceRelation();
      $temp->deleteByCriteria(['appliances_items_id' => $this->fields['id']]);
   }


   static function countForAppliance(Appliance $item) {
      if (!count($item->getTypes())) {
         return 0;
      }
      return countElementsInTable(
         'glpi_appliances_items', [
            'itemtype'        => $item->getTypes(),
            'appliances_id'   => $item->getID()
         ]
      );
   }


   static function countForItem(CommonDBTM $item) {
      return countElementsInTable(
         'glpi_appliances_items', [
            'itemtype' => $item->getType(),
            'items_id' => $item->getID()
         ]
      );
   }


   /**
    * Show the appliances associated with a device
    *
    * Called from the device form (applicatif tab)
    *
    * @param CommonDBTM $item          type of the device
    * @param integer    $withtemplate  (default '')
   **/
   static function showForItem($item, $withtemplate = '') {
      global $DB;

      $ID       = $item->getField('id');
      $itemtype = get_class($item);
      $canread  = $item->can($ID, READ);
      $canedit  = $item->can($ID, UPDATE);

      $query = [
         'FIELDS'    => [
            'glpi_appliances_items.id AS entID',
            'glpi_appliances.*'
         ],
         'FROM'      => 'glpi_appliances_items',
         'LEFT JOIN' => [
            'glpi_appliances' => [
               'ON' => [
                  Appliance::getTable()   => 'id',
                  self::getTable()        => 'appliances_id'
               ]
            ],
            'glpi_entities' => [
               'ON' => [
                  Entity::getTable()      => 'id',
                  Appliance::getTable()   => 'entities_id'
               ]
            ]
         ],
         'WHERE'     => [
            'glpi_appliances_items.items_id' => $ID,
            'glpi_appliances_items.itemtype' => $itemtype
         ] + getEntitiesRestrictCriteria('glpi_appliances', 'entities_id', $item->getEntityID(), true)
      ];

      $result = $DB->request($query);

      $result_app = $DB->request([
         'SELECT' => 'id',
         'FROM'   => self::getTable(),
         'WHERE'  => ['items_id' => $ID]
      ]);
      $number_app = count($result_app);

      if ($number_app >0) {
         $colsup = 1;
      } else {
         $colsup = 0;
      }

      if (Session::isMultiEntitiesMode()) {
         $colsup += 1;
      }

      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='".(5+$colsup)."'>".__('Associate')."</th></tr>";
      echo "<tr><th>".__('Name')."</th>";
      if (Session::isMultiEntitiesMode()) {
         echo "<th>".Entity::getTypeName(1)."</th>";
      }
      echo "<th>".Group::getTypeName(1)."</th>";
      echo "<th>".__('Type')."</th>";
      if ($number_app > 0) {
         echo "<th>".__('Item to link')."</th>";
      }
      echo "<th>".__('Comments')."</th>";

      if ($canedit &&$withtemplate < 2) {
         echo "<th>&nbsp;</th>";
      }
      echo "</tr>";
      $used = [];

      while ($data = $result->next()) {
         $appliancesID = $data["id"];
         $used[]       = $appliancesID;

         echo "<tr class='tab_bg_1".($data["is_deleted"]=='1'?"_2":"")."'>";
         $name = $data["name"];
         if (($withtemplate != 3)
             && $canread
             && (in_array($data['entities_id'], $_SESSION['glpiactiveentities'])
                 || $data["is_recursive"])) {

            echo "<td class='center'>";
            echo "<a href='".Appliance::getFormURLWithID($data['id'])."'>";
            if ($_SESSION["glpiis_ids_visible"]) {
               printf(__('%1$s (%2$s)'), $name, $data["id"]);
            } else {
               echo $name;
            }
            echo "</a></td>";
         } else {
            echo "<td class='center'>";
            if ($_SESSION["glpiis_ids_visible"]) {
               printf(__('%1$s (%2$s)'), $name, $data["id"]);
            } else {
               echo $name;
            }
            echo "</td>";
         }
         if (Session::isMultiEntitiesMode()) {
            echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities", $data['entities_id'])."</td>";
         }
         echo "<td class='center'>".Dropdown::getDropdownName("glpi_groups", $data["groups_id"])."</td>";
         echo "<td class='center'>".Dropdown::getDropdownName("glpi_appliancetypes", $data["appliancetypes_id"])."</td>";

         if ($number_app > 0) {
            // add or delete a relation to an appliance
            echo "<td class='center'>";
            ApplianceRelation::showList(
               $data["relationtype"],
               $data["entID"],
               $item->fields["entities_id"],
               $canedit
            );
            echo "</td>";
         }

         echo "<td class='center'>".$data["comment"]."</td>";

         if ($canedit) {
            echo "<td class='center tab_bg_2'>";
            Html::showSimpleForm(
               Appliance::getFormURL(),
               'deleteappliance', __('Delete permanently'),
               ['id' => $data['entID']]
            );
            echo "</td>";
         }
         echo "</tr>";
      }

      if ($canedit) {
         if ($item->isRecursive()) {
            $entities = getSonsOf('glpi_entities', $item->getEntityID());
         } else {
            $entities = $item->getEntityID();
         }

         $req = $DB->request([
            'FROM'  => Appliance::getTable(),
            'COUNT' => 'cpt',
            'WHERE' => ['is_deleted' => 0] + getEntitiesRestrictCriteria(Appliance::getTable(), '', $entities, true)
         ]);
         $nb     = count($req);

         if (($withtemplate < 2)
             && ($nb > count($used))) {
            echo "<tr class='tab_bg_1'>";
            echo "<td class='right' colspan=5>";

            // needed to use the button "additem"
            echo "<form method='post' action=\"".Appliance::getFormURL()."\">";
            echo "<input type='hidden' name='item' value='".$ID."'>".
                 "<input type='hidden' name='itemtype' value='$itemtype'>";
            Dropdown::show(
               'Appliance', [
                  'name'   => "conID",
                  'entity' => $entities,
                  'used'   => $used
               ]
            );

            echo "<input type='submit' name='additem' value='".__('Add')."' class='submit'>";
            Html::closeForm();

            echo "</td>";
            echo "<td class='right' colspan='".($colsup)."'></td>";
            echo "</tr>";
         }
      }
      echo "</table></div>";
   }


   static function showAddForm(Appliance $appli) {
      $ID = $appli->getField('id');
      if (!$appli->can($ID, UPDATE)) {
         return false;
      }
      $rand = mt_rand();
      if ($ID > 0) {
         echo "<div class='firstbloc'>";
         echo "<form method='post' name='appliances_form$rand' id='appliances_form$rand' action=\"".Appliance::getFormURL()."\">";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><td class='center tab_bg_2' width='20%'>";
         echo "<input type='hidden' name='conID' value='$ID'>\n";
         Dropdown::showSelectItemFromItemtypes([
            'items_id_name'   => 'item',
            'itemtypes'       => $appli->getTypes(true),
            'entity_restrict' => ($appli->fields['is_recursive']
                                    ? getSonsOf('glpi_entities', $appli->fields['entities_id'])
                                    : $appli->fields['entities_id']),
            'checkright'      => true
         ]);
         echo "</td>";
         echo "<td class='center' class='tab_bg_2'>";
         echo "<input type='submit' name='additem' value='".__('Add')."' class='submit'>";
         echo "</td></tr></table>";
         Html::closeForm();
         echo "</div>";
      }
   }


   /**
    * Show the Device associated with an appliancd
    *
    * @param Appliance $appli Appliance object
    *
    * @return boolean
   **/
   static function showForAppliance(Appliance $appli) {
      global $DB;

      $instID = $appli->fields['id'];

      if (!$appli->can($instID, READ)) {
         return false;
      }

      $canedit = $appli->can($instID, UPDATE);

      $result = $DB->request([
         'SELECT'    => 'itemtype',
         'DISTINCT'  => true,
         'FROM'      => 'glpi_appliances_items',
         'WHERE'     => ['appliances_id' => $instID]
      ]);
      $number = count($result);

      $rand = mt_rand();

      echo "<div class='spaced'>";
      if ($canedit) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = ['num_displayed'    => $number,
                                 'container'        => 'mass'.__CLASS__.$rand];
         Html::showMassiveActions($massiveactionparams);
         echo "<input type='hidden' name='conID' value='$instID'>\n";
      }

      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='tab_bg_1'>";
      if ($canedit) {
          echo "<th width='10'>";
          Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
          echo "</th>";
      }
      echo "<th>".__('Type')."</th>";
      echo "<th>".__('Name')."</th>";
      if (Session::isMultiEntitiesMode()) {
         echo "<th>".Entity::getTypeName(1)."</th>";
      }
      if (isset($appli->fields["relationtype"])) {
         echo "<th>".__('Item to link')."</th>";
      }
      echo "<th>".__('Serial number')."</th>";
      echo "<th>".__('Inventory number')."</th>";
      echo "</tr>";

      foreach ($result as $row) {
         $type = $row['itemtype'];

         if (!($item = getItemForItemtype($type))) {
            continue;
         }
         if ($item->canView()) {
            // Ticket and knowbaseitem can't be associated to an appliance
            $column = "name";

            $query = [
               'SELECT'    => [
                  $item->getTable().'.*',
                  'glpi_appliances_items.id AS IDD',
                  'glpi_entities.id AS entity'
               ],
               'FROM'      => 'glpi_appliances_items',
               'LEFT JOIN' => [
                  getTableForItemType($type) =>[
                     'ON' => [
                        $item->getTable()       => 'id',
                        'glpi_appliances_items' => 'items_id'], [
                           'glpi_appliances_items.itemtype' => $type
                        ]
                  ],
                  'glpi_entities' => [
                     'ON' => [
                        'glpi_entities'   => 'id',
                        $item->getTable() => 'entities_id']
                  ]
               ],
               'WHERE'     => [
                  'glpi_appliances_items.appliances_id' => $instID
               ] + getEntitiesRestrictCriteria($item->getTable())];

            if ($item->maybeTemplate()) {
               $query['WHERE'][$item->getTable().'.is_template'] = 0;
            }
            $query['ORDER'] = ['glpi_entities.completename', $item->getTable().'.'.$column];

            if ($result_linked = $DB->request($query)) {
               if (count($result_linked)) {
                  Session::initNavigateListItems(
                     $type,
                     Appliance::getTypeName(Session::getPluralNumber()) . " = ".$appli->getNameID()
                  );

                  foreach ($result_linked as $data) {
                     $item->getFromDB($data["id"]);
                     Session::addToNavigateListItems($type, $data["id"]);
                     $name = $item->getLink();

                     echo "<tr class='tab_bg_1'>";
                     if ($canedit) {
                        echo "<td width='10'>";
                        Html::showMassiveActionCheckBox(__CLASS__, $data["IDD"]);
                        echo "</td>";
                     }
                     echo "<td class='center'>".$item->getTypeName(1)."</td>";
                     echo "<td class='center' ".
                           (isset($data['deleted']) && $data['deleted']?"class='tab_bg_2_2'":"").">".
                           $name."</td>";
                     if (Session::isMultiEntitiesMode()) {
                        echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities", $data['entity']).
                              "</td>";
                     }

                     if (isset($appli->fields["relationtype"])) {
                        echo "<td class='center'>".ApplianceRelation::getTypeName($appli->fields["relationtype"]);
                        ApplianceRelation::showList(
                           $appli->fields["relationtype"],
                           $data["IDD"],
                           $item->fields["entities_id"],
                           false
                        );
                        echo "</td>";
                     }

                     echo "<td class='center'>".($data["serial"] ?? '-')."</td>";
                     echo "<td class='center'>".($data["otherserial"] ?? '-')."</td>";
                     echo "</tr>";
                  }
               }
            }
         }
      }
      echo "</table>";
      if ($canedit && $number) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!$withtemplate) {
         if (($item->getType() == 'Appliance') && count(Appliance::getTypes(false))) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(
                  _n('Associated item', 'Associated items', Session::getPluralNumber()),
                  self::countForAppliance($item)
               );
            }
            return _n('Associated item', 'Associated items', Session::getPluralNumber());

         } else if (in_array($item->getType(), Appliance::getTypes(true)) && Session::haveRight('appliance', READ)) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(
                  Appliance::getTypeName(2),
                  self::countForItem($item)
               );
            }
            return Appliance::getTypeName(2);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType()=='Appliance') {
         self::showAddForm($item);
         self::showForAppliance($item);

      } else if (in_array($item->getType(), Appliance::getTypes(true))) {
         self::showForItem($item, $withtemplate);

      }
      return true;
   }


    /**
     * @param $appliances_id   integer
     * @param $items_id                          integer
     * @param $itemtype                          string
     *
     * @return bool
    **/
   function getFromDBbyAppliancesAndItem($appliances_id, $items_id, $itemtype) {
      global $DB;

      $result = $DB->request([
         'FROM'  => $this->getTable(),
         'WHERE' => [
            'appliances_id' => $appliances_id,
            'itemtype' => $items_id,
            'items_id' => $itemtype
         ]
      ]);
      if (count($result) != 1) {
         return false;
      }
      foreach ($result as $id => $row) {
         $this->fields[$id] = $row;
      }
      if (is_array($this->fields) && count($this->fields)) {
         return true;
      }
      return false;
   }


   function deleteItemByAppliancesAndItem($appliances_id, $items_id, $itemtype) {
      if ($this->getFromDBbyAppliancesAndItem($appliances_id, $items_id, $itemtype)) {
         $this->delete(['id'=>$this->fields["id"]]);
      }
   }


   function getForbiddenStandardMassiveAction() {
      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

}
