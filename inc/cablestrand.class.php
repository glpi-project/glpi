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

/// Class CableStrand
class CableStrand extends CommonDropdown {


   static function getTypeName($nb = 0) {
      return _n('Cable strand', 'Cable strands', $nb);
   }


   static function getFieldLabel() {
      return _n('Cable strand', 'Cable strands', 1);
   }

   function defineTabs($options = []) {

      $ong = parent::defineTabs($options);
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }

   function cleanDBonPurge() {
      Rule::cleanForItemAction($this);
      Rule::cleanForItemCriteria($this, '_cablestrands_id%');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               $ong    = [];
               $ong[1] = _n('Item', 'Items', Session::getPluralNumber());
               return $ong;
         }
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 :
               $item->showItems();
               break;
         }
      }
      return true;
   }

      /**
    * Print the HTML array of items for a location
    *
    * @since 0.85
    *
    * @return void
   **/
   function showItems() {
      global $DB;

      $cablestrands_id = $this->fields['id'];

      if (!$this->can($cablestrands_id, READ)) {
         return false;
      }

      $queries = [];
      $item = new Cable();

      $table = getTableForItemType($item->getType());
      $itemtype_criteria = [
         'SELECT' => [
            "$table.id",
            new \QueryExpression($DB->quoteValue($item->getType()) . ' AS ' . $DB->quoteName('type')),
         ],
         'FROM'   => $table,
         'WHERE'  => [
            "$table.cablestrands_id"   => $cablestrands_id,
         ]
      ];
      if ($item->maybeDeleted()) {
         $itemtype_criteria['WHERE']['is_deleted'] = 0;
      }
      $queries[] = $itemtype_criteria;

      $criteria = count($queries) === 1 ? $queries[0] : ['FROM' => new \QueryUnion($queries)];
      $start  = (isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0);
      $criteria['START'] = $start;
      $criteria['LIMIT'] = $_SESSION['glpilist_limit'];

      $iterator = $DB->request($criteria);

      // Execute a second request to get the total number of rows
      unset($criteria['SELECT']);
      unset($criteria['START']);
      unset($criteria['LIMIT']);

      $criteria['COUNT'] = 'total';
      $number = $DB->request($criteria)->next()['total'];

      if ($number) {
         echo "<div class='spaced'>";
         Html::printAjaxPager('', $start, $number);

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>"._n('Type', 'Types', 1)."</th>";
         echo "<th>".Entity::getTypeName(1)."</th>";
         echo "<th>".__('Name')."</th>";
         echo "<th>".__('Inventory number')."</th>";
         echo "<th>".sprintf(__('%s (%s)'), _n('Associated item', 'Associated items', 1), __('Endpoint B'))."</th>";
         echo "<th>".sprintf(__('%s (%s)'), Socket::getTypeName(1), __('Endpoint B'))."</th>";
         echo "<th>".sprintf(__('%s (%s)'), _n('Associated item', 'Associated items', 1), __('Endpoint A'))."</th>";
         echo "<th>".sprintf(__('%s (%s)'), Socket::getTypeName(1), __('Endpoint A'))."</th>";
         echo "</tr>";

         while ($data = $iterator->next()) {
            if (!($item = getItemForItemtype($data['type']) || !$item->getFromDB($data['id']))) {
               trigger_error(sprintf('Unable to load item %s (%s).', $data['type'], $data['id']), E_USER_WARNING);
               continue;
            }
            echo "<tr class='tab_bg_1'><td>".$item->getTypeName()."</td>";
            echo "<td>".Dropdown::getDropdownName("glpi_entities", $item->getEntityID())."</td>";
            echo "<td>".$item->getLink()."</td>";
            echo "<td>".(isset($item->fields["otherserial"])? "".$item->fields["otherserial"]."" :"-")."</td>";
            echo "<td>";
            if ($item->fields["items_id_endpoint_b"] > 0) {
               if (!($front_item = getItemForItemtype($item->fields["itemtype_endpoint_b"]) || !$front_item->getFromDB($item->fields["items_id_endpoint_b"]))) {
                  trigger_error(sprintf('Unable to load item %s (%s).', $item->fields["itemtype_endpoint_b"], $item->fields["items_id_endpoint_b"]), E_USER_WARNING);
               } else {
                  echo $front_item->getLink();
               }
            }
            echo "</td>";
            echo "<td>";
            if ($item->fields["sockets_id_endpoint_b"] > 0) {
               $front_socket = new Socket();
               if (!$front_socket->getFromDB($item->fields["sockets_id_endpoint_b"])) {
                  trigger_error(sprintf('Unable to load item %s (%s).', Socket::getType(), $item->fields["sockets_id_endpoint_b"]), E_USER_WARNING);
               } else {
                  echo $front_socket->getLink();
               }
            }
            echo "</td>";
            echo "<td>";
            if ($item->fields["items_id_endpoint_a"] > 0) {
               if (!($rear_item = getItemForItemtype($item->fields["itemtype_endpoint_a"]) || !$rear_item->getFromDB($item->fields["items_id_endpoint_a"]))) {
                  trigger_error(sprintf('Unable to load item %s (%s).', $item->fields["itemtype_endpoint_a"], $item->fields["items_id_endpoint_a"]), E_USER_WARNING);
               } else {
                  echo $rear_item->getLink();
               }
            }
            echo "</td>";
            echo "<td>";
            if ($item->fields["sockets_id_endpoint_a"] > 0) {
               if (!$rear_socket->getFromDB($item->fields["sockets_id_endpoint_a"])) {
                  trigger_error(sprintf('Unable to load item %s (%s).', Socket::getType(), $item->fields["sockets_id_endpoint_a"]), E_USER_WARNING);
               } else {
                  echo $rear_socket->getLink();
               }
               echo $rear_socket->getLink();
            }
            echo "</td>";
            echo"</tr>";
         }
      } else {
         echo "<p class='center b'>".__('No item found')."</p>";
      }
      echo "</table></div>";

   }
}
