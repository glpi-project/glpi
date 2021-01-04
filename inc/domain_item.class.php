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
   die("Sorry. You can't access this file directly");
}

class Domain_Item extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1 = "Domain";
   static public $items_id_1 = 'domains_id';

   static public $itemtype_2 = 'itemtype';
   static public $items_id_2 = 'items_id';

   static $rightname = 'domain';

   static function cleanForItem(CommonDBTM $item) {
      $temp = new self();
      $temp->deleteByCriteria(
         ['itemtype' => $item->getType(),
               'items_id' => $item->getField('id')]
      );
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!$withtemplate) {
         if ($item->getType() == 'Domain'
             && count(Domain::getTypes(false))
         ) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(_n('Associated item', 'Associated items', Session::getPluralNumber()), self::countForDomain($item));
            }
            return _n('Associated item', 'Associated items', Session::getPluralNumber());
         } else if ($item->getType()== 'DomainRelation' || in_array($item->getType(), Domain::getTypes(true))
                    && Session::haveRight('domain', READ)
         ) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(Domain::getTypeName(Session::getPluralNumber()), self::countForItem($item));
            }
            return Domain::getTypeName(2);
         }
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Domain') {
         self::showForDomain($item);
      } else if (in_array($item->getType(), Domain::getTypes(true))
         || $item->getType() == DomainRelation::getType()
      ) {
         self::showForItem($item);
      }
      return true;
   }

   static function countForDomain(Domain $item) {
      $types = $item->getTypes();
      if (count($types) == 0) {
         return 0;
      }
      return countElementsInTable(
         'glpi_domains_items', [
            "domains_id"   => $item->getID(),
            "itemtype"     => $types
         ]
      );
   }

   static function countForItem(CommonDBTM $item) {
      $criteria = [];
      if ($item instanceof DomainRelation) {
         $criteria = ['domainrelations_id' => $item->fields['id']];
      } else {
         $criteria = [
            'itemtype'  => $item->getType(),
            'items_id'  => $item->fields['id']
         ];
      }

      return countElementsInTable(
         self::getTable(),
         $criteria
      );

   }

   function getFromDBbyDomainsAndItem($domains_id, $items_id, $itemtype) {
      $criteria = ['domains_id' => $domains_id];
      $item = new $itemtype;
      if ($item instanceof DomainRelation) {
         $criteria += ['domainrelations_id' => $items_id];
      } else {
         $criteria += [
            'itemtype'  => $itemtype,
            'items_id'  => $items_id
         ];
      }

      return $this->getFromDBByCrit($criteria);
   }

   function addItem($values) {
      $this->add([
         'domains_id'         => $values['domains_id'],
         'items_id'           => $values['items_id'],
         'itemtype'           => $values['itemtype'],
         'domainrelations_id' => $values['domainrelations_id']
      ]);

   }

   function deleteItemByDomainsAndItem($domains_id, $items_id, $itemtype) {
      if ($this->getFromDBbyDomainsAndItem($domains_id, $items_id, $itemtype)) {
         $this->delete(['id' => $this->fields["id"]]);
      }
   }

   /**
    * Show items linked to a domain
    *
    * @param Domain $domain Domain object
    *
    * @return void|boolean (display) Returns false if there is a rights error.
    **/
   public static function showForDomain(Domain $domain) {
      global $DB;

      $instID = $domain->fields['id'];
      if (!$domain->can($instID, READ)) {
         return false;
      }
      $canedit = $domain->can($instID, UPDATE);
      $rand    = mt_rand();

      $iterator = $DB->request([
         'SELECT'    => 'itemtype',
         'DISTINCT'  => true,
         'FROM'      => self::getTable(),
         'WHERE'     => ['domains_id' => $instID],
         'ORDER'     => 'itemtype',
         'LIMIT'     => count(Domain::getTypes(true))
      ]);

      $number = count($iterator);

      if (Session::isMultiEntitiesMode()) {
         $colsup = 1;
      } else {
         $colsup = 0;
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form method='post' name='domain_form$rand'
         id='domain_form$rand'  action='" . Toolbox::getItemTypeFormURL("Domain") . "'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='" . ($canedit ? (5 + $colsup) : (4 + $colsup)) . "'>" .
              __('Add an item') . "</th></tr>";

         echo "<tr class='tab_bg_1'><td colspan='" . (3 + $colsup) . "' class='center'>";
         Dropdown::showSelectItemFromItemtypes(['items_id_name' => 'items_id',
                                                     'itemtypes'     => Domain::getTypes(true),
                                                     'entity_restrict'
                                                                     => ($domain->fields['is_recursive']
                                                        ? getSonsOf('glpi_entities',
                                                                    $domain->fields['entities_id'])
                                                                     : $domain->fields['entities_id']),
                                                     'checkright'
                                                                     => true,
                                               ]);

         Dropdown::show(
            'DomainRelation', [
               'name'   => "domainrelations_id",
               'value'  => DomainRelation::BELONGS
            ]
         );
         echo "</td><td colspan='2' class='center' class='tab_bg_1'>";
         echo "<input type='hidden' name='domains_id' value='$instID'>";
         echo "<input type='submit' name='additem' value=\"" . _sx('button', 'Add') . "\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
         $massiveactionparams = [];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr>";

      if ($canedit && $number) {
         echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
      }

      echo "<th>" . _n('Type', 'Types', 1) . "</th>";
      echo "<th>" . __('Name') . "</th>";
      if (Session::isMultiEntitiesMode()) {
         echo "<th>" . Entity::getTypeName(1) . "</th>";
      }
      echo "<th>" . DomainRelation::getTypeName(1) . "</th>";
      echo "<th>" . __('Serial number') . "</th>";
      echo "<th>" . __('Inventory number') . "</th>";
      echo "</tr>";

      while ($data = $iterator->next()) {
         $itemtype = $data['itemtype'];
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         if ($item->canView()) {
            $itemTable = getTableForItemType($itemtype);
            $linked_criteria = [
               'SELECT' => [
                  "$itemTable.*",
                  'glpi_domains_items.id AS items_id',
                  'glpi_domains_items.domainrelations_id',
                  'glpi_entities.id AS entity'
               ],
               'FROM'   => self::getTable(),
               'INNER JOIN'   => [
                  $itemTable  => [
                     'ON'  => [
                        $itemTable  => 'id',
                        self::getTable()  => 'items_id'
                     ]
                  ]
               ],
               'LEFT JOIN'    => [
                  'glpi_entities'   => [
                     'ON'  => [
                        'glpi_entities'   => 'id',
                        $itemTable        => 'entities_id'
                     ]
                  ]
               ],
               'WHERE'        => [
                  self::getTable() . '.itemtype'   => $itemtype,
                  self::getTable() . '.domains_id' => $instID
               ] + getEntitiesRestrictCriteria($itemTable, '', '', $item->maybeRecursive())
            ];

            if ($item->maybeTemplate()) {
               $linked_criteria['WHERE']["$itemTable.is_template"] = 0;
            }

            $linked_iterator = $DB->request($linked_criteria);

            if (count($linked_iterator)) {
               Session::initNavigateListItems($itemtype, Domain::getTypeName(2) . " = " . $domain->fields['name']);

               while ($data = $linked_iterator->next()) {

                  $item->getFromDB($data["id"]);

                  Session::addToNavigateListItems($itemtype, $data["id"]);

                  $ID = "";

                  if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                     $ID = " (" . $data["id"] . ")";
                  }

                  $link = Toolbox::getItemTypeFormURL($itemtype);
                  $name = "<a href=\"" . $link . "?id=" . $data["id"] . "\">"
                           . $data["name"] . "$ID</a>";

                  echo "<tr class='tab_bg_1'>";

                  if ($canedit) {
                     echo "<td width='10'>";
                     Html::showMassiveActionCheckBox(__CLASS__, $data["items_id"]);
                     echo "</td>";
                  }
                  echo "<td class='center'>" . $item->getTypeName(1) . "</td>";

                  echo "<td class='center' " . (isset($data['is_deleted']) && $data['is_deleted'] ? "class='tab_bg_2_2'" : "") .
                        ">" . $name . "</td>";
                  if (Session::isMultiEntitiesMode()) {
                     echo "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $data['entity']) . "</td>";
                  }
                  echo "<td class='center'>" . Dropdown::getDropdownName("glpi_domainrelations", $data['domainrelations_id']) . "</td>";
                  echo "<td class='center'>" . (isset($data["serial"]) ? "" . $data["serial"] . "" : "-") . "</td>";
                  echo "<td class='center'>" . (isset($data["otherserial"]) ? "" . $data["otherserial"] . "" : "-") . "</td>";

                  echo "</tr>";
               }
            }
         }
      }
      echo "</table>";

      if ($canedit && $number) {
         $paramsma['ontop'] = false;
         Html::showMassiveActions($paramsma);
         Html::closeForm();
      }
      echo "</div>";

   }

   /**
    * Show domains associated to an item
    *
    * @param $item            CommonDBTM object for which associated domains must be displayed
    * @param $withtemplate (default '')
    *
    * @return bool
    */
   static function showForItem(CommonDBTM $item, $withtemplate = '') {
      global $DB;

      $ID = $item->getField('id');

      if ($item->isNewID($ID)) {
         return false;
      }
      if (!Session::haveRight('domain', READ)) {
         return false;
      }

      if (!$item->can($item->fields['id'], READ)) {
         return false;
      }

      if (empty($withtemplate)) {
         $withtemplate = 0;
      }

      $canedit      = $item->canAddItem('Domain');
      $rand         = mt_rand();
      $is_recursive = $item->isRecursive();

      $criteria = [
         'SELECT'    => [
            'glpi_domains_items.id AS assocID',
            'glpi_domains_items.domainrelations_id',
            'glpi_entities.id AS entity',
            'glpi_domains.name AS assocName',
            'glpi_domains.*'

         ],
         'FROM'      => self::getTable(),
         'LEFT JOIN' => [
            Domain::getTable()   => [
               'ON'  => [
                  Domain::getTable()   => 'id',
                  self::getTable()     => 'domains_id'
               ]
            ],
            Entity::getTable()   => [
               'ON'  => [
                  Domain::getTable()   => 'entities_id',
                  Entity::getTable()   => 'id'
               ]
            ]
         ],
         'WHERE'     => [],//to be filled
         'ORDER'     => 'assocName'
      ];

      if ($item instanceof DomainRelation) {
         $criteria['WHERE'] = ['glpi_domains_items.domainrelations_id' => $ID];
      } else {
         $criteria['WHERE'] = [
            'glpi_domains_items.itemtype' => $item->getType(),
            'glpi_domains_items.items_id' => $ID
         ];
      }
      $criteria['WHERE'] += getEntitiesRestrictCriteria(Domain::getTable(), '', '', true);

      $iterator = $DB->request($criteria);

      $number = count($iterator);
      $i      = 0;

      $domains = [];
      $domain  = new Domain();
      $used    = [];
      while ($data = $iterator->next()) {
         $domains[$data['assocID']] = $data;
         $used[$data['id']]         = $data['id'];
      }

      if (!($item instanceof DomainRelation) && $canedit && $withtemplate < 2) {
         // Restrict entity for knowbase
         $entities = "";
         $entity   = $_SESSION["glpiactive_entity"];

         if ($item->isEntityAssign()) {
            /// Case of personal items : entity = -1 : create on active entity (Reminder case))
            if ($item->getEntityID() >= 0) {
               $entity = $item->getEntityID();
            }

            if ($item->isRecursive()) {
               $entities = getSonsOf('glpi_entities', $entity);
            } else {
               $entities = $entity;
            }
         }

         $domain_iterator = $DB->request([
            'COUNT'  => 'cpt',
            'FROM'   => Domain::getTable(),
            'WHERE'  => ['is_deleted' => 0] + getEntitiesRestrictCriteria(Domain::getTable(), '', $entities, true)
         ]);
         $result = $domain_iterator->next();
         $nb     = $result['cpt'];

         echo "<div class='firstbloc'>";

         if (Session::haveRight('domain', READ)
             && ($nb > count($used))
         ) {
            echo "<form name='domain_form$rand' id='domain_form$rand' method='post'
                   action='" . Toolbox::getItemTypeFormURL('Domain') . "'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='4' class='center'>";
            echo "<input type='hidden' name='entities_id' value='$entity'>";
            echo "<input type='hidden' name='is_recursive' value='$is_recursive'>";
            echo "<input type='hidden' name='itemtype' value='" . $item->getType() . "'>";
            echo "<input type='hidden' name='items_id' value='$ID'>";
            if ($item->getType() == 'Ticket') {
               echo "<input type='hidden' name='tickets_id' value='$ID'>";
            }

            Dropdown::show(
               'DomainRelation', [
                  'name'   => "domainrelations_id",
                  'value'  => DomainRelation::BELONGS,
                  'display_emptychoice'   => false
               ]
            );

            Domain::dropdownDomains([
               'entity' => $entities,
               'used'   => $used
            ]);

            echo "</td><td class='center' width='20%'>";
            echo "<input type='submit' name='additem' value=\"" .
                 __('Associate a domain') . "\" class='submit'>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            Html::closeForm();
         }

         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number && ($withtemplate < 2)) {
         Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
         $massiveactionparams = ['num_displayed' => $number];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr>";
      if ($canedit && $number && ($withtemplate < 2)) {
         echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
      }
      echo "<th>" . __('Name') . "</th>";
      if (Session::isMultiEntitiesMode()) {
         echo "<th>" . Entity::getTypeName(1) . "</th>";
      }
      echo "<th>" . __('Group in charge') . "</th>";
      echo "<th>" . __('Technician in charge') . "</th>";
      echo "<th>" . _n('Type', 'Types', 1) . "</th>";
      if (!$item instanceof DomainRelation) {
         echo "<th>" . DomainRelation::getTypeName(1) . "</th>";
      }
      echo "<th>" . __('Creation date') . "</th>";
      echo "<th>" . __('Expiration date') . "</th>";
      echo "</tr>";
      $used = [];

      if ($number) {
         Session::initNavigateListItems('Domain',
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $item->getTypeName(1), $item->getName()));

         foreach ($domains as $data) {
            $domainID = $data["id"];
            $link     = NOT_AVAILABLE;

            if ($domain->getFromDB($domainID)) {
               $link = $domain->getLink();
            }

            Session::addToNavigateListItems('Domain', $domainID);

            $used[$domainID] = $domainID;

            echo "<tr class='tab_bg_1" . ($data["is_deleted"] ? "_2" : "") . "'>";
            if ($canedit && ($withtemplate < 2)) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["assocID"]);
               echo "</td>";
            }
            echo "<td class='center'>$link</td>";
            if (Session::isMultiEntitiesMode()) {
               echo "<td class='center'>" . Dropdown::getDropdownName("glpi_entities", $data['entities_id']) . "</td>";
            }
            echo "<td class='center'>" . Dropdown::getDropdownName("glpi_groups", $data["groups_id_tech"]) . "</td>";
            echo "<td class='center'>" . getUserName($data["users_id_tech"]) . "</td>";
            echo "<td class='center'>" . Dropdown::getDropdownName("glpi_domaintypes", $data["domaintypes_id"]) . "</td>";
            if (!$item instanceof DomainRelation) {
               echo "<td class='center'>" . Dropdown::getDropdownName("glpi_domainrelations", $data["domainrelations_id"]) . "</td>";
            }
            echo "<td class='center'>" . Html::convDate($data["date_creation"]) . "</td>";
            if ($data["date_expiration"] <= date('Y-m-d')
                && !empty($data["date_expiration"])
            ) {
               echo "<td class='center'><div class='deleted'>" . Html::convDate($data["date_expiration"]) . "</div></td>";
            } else if (empty($data["date_expiration"])) {
               echo "<td class='center'>" . __('Does not expire') . "</td>";
            } else {
               echo "<td class='center'>" . Html::convDate($data["date_expiration"]) . "</td>";
            }
            echo "</tr>";
            $i++;
         }
      }

      echo "</table>";
      if ($canedit && $number && ($withtemplate < 2)) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }

   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => '2',
         'table'              => DomainRelation::getTable(),
         'field'              => 'name',
         'name'               => DomainRelation::getTypeName(),
         'datatype'           => 'itemlink',
         'itemlink_type'      => $this->getType(),
      ];

      return $tab;
   }
}
