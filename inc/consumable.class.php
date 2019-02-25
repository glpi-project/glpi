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

use Glpi\Event;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

//!  Consumable Class
/**
  This class is used to manage the consumables.
  @see ConsumableItem
  @author Julien Dombre
**/
class Consumable extends CommonDBChild {

   // From CommonDBTM
   static protected $forward_entity_to = ['Infocom'];
   public $no_form_page                = true;

   static $rightname                   = 'consumable';

   // From CommonDBChild
   static public $itemtype             = 'ConsumableItem';
   static public $items_id             = 'consumableitems_id';

   /**
    * @since 0.84
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * since version 0.84
    *
    * @see CommonDBTM::getNameField()
   **/
   static function getNameField() {
      return 'id';
   }

   static function getTypeName($nb = 0) {
      return _n('Consumable', 'Consumables', $nb);
   }


   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Infocom::class,
         ]
      );
   }


   function prepareInputForAdd($input) {

      $item = new ConsumableItem();
      if ($item->getFromDB($input["consumableitems_id"])) {
         return ["consumableitems_id" => $item->fields["id"],
                      "entities_id"        => $item->getEntityID(),
                      "date_in"            => date("Y-m-d")];
      }
      return [];
   }


   function post_addItem() {

      Infocom::cloneItem('ConsumableItem', $this->fields["consumableitems_id"], $this->fields['id'],
                         $this->getType());
   }


   /**
    * send back to stock
   **/
   function backToStock(array $input, $history = 1) {
      global $DB;

      $result = $DB->update(
         $this->getTable(), [
            'date_out' => 'NULL'
         ], [
            'id' => $input['id']
         ]
      );
      if ($result) {
         return true;
      }
      return false;
   }


   /**
    * @since 0.84
    *
    * @see CommonDBTM::getPreAdditionalInfosForName
   **/
   function getPreAdditionalInfosForName() {

      $ci = new ConsumableItem();
      if ($ci->getFromDB($this->fields['consumableitems_id'])) {
         return $ci->getName();
      }
      return '';
   }


   /**
    * UnLink a consumable linked to a printer
    *
    * UnLink the consumable identified by $ID
    *
    * @param $ID           consumable identifier
    * @param $itemtype     itemtype of who we give the consumable (default '')
    * @param $items_id     ID of the item giving the consumable (default 0)
    *
    * @return boolean
   **/
   function out($ID, $itemtype = '', $items_id = 0) {
      global $DB;

      if (!empty($itemtype)
          && ($items_id > 0)) {

         $result = $DB->update(
            $this->getTable(), [
               'date_out'  => date('Y-m-d'),
               'itemtype'  => $itemtype,
               'items_id'  => $items_id
            ], [
               'id' => $ID
            ]
         );
         if ($result) {
            return true;
         }
      }
      return false;
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      global $CFG_GLPI;

      $input = $ma->getInput();
      switch ($ma->getAction()) {
         case 'give' :
            if (isset($input["entities_id"])) {
               Dropdown::showSelectItemFromItemtypes(['itemtype_name'
                                                              => 'give_itemtype',
                                                           'items_id_name'
                                                              => 'give_items_id',
                                                           'entity_restrict'
                                                              => $input["entities_id"],
                                                           'itemtypes'
                                                              => $CFG_GLPI["consumables_types"]]);
               echo "<br><br>".Html::submit(_x('button', 'Give'),
                                            ['name' => 'massiveaction']);
               return true;
            }
      }
      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'backtostock' :
            foreach ($ids as $id) {
               if ($item->can($id, UPDATE)) {
                  if ($item->backToStock(["id" => $id])) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            return;
         case 'give' :
            $input = $ma->getInput();
            if (($input["give_items_id"] > 0)
                && !empty($input['give_itemtype'])) {
               foreach ($ids as $key) {
                  if ($item->can($key, UPDATE)) {
                     if ($item->out($key, $input['give_itemtype'], $input["give_items_id"])) {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                     $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                  }
               }
               Event::log($item->fields['consumableitems_id'], "consumables", 5, "inventory",
                          //TRANS: %s is the user login
                          sprintf(__('%s gives a consumable'), $_SESSION["glpiname"]));
            } else {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   /**
    * count how many consumable for the consumable item $tID
    *
    * @param $tID integer  consumable item identifier.
    *
    * @return integer : number of consumable counted.
    **/
   static function getTotalNumber($tID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_consumables`
                WHERE `consumableitems_id` = '$tID'";
      $result = $DB->query($query);

      return $DB->numrows($result);
   }


   /**
    * count how many old consumable for the consumable item $tID
    *
    * @param $tID integer  consumable item identifier.
    *
    * @return integer : number of old consumable counted.
   **/
   static function getOldNumber($tID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_consumables`
                WHERE (`consumableitems_id` = '$tID'
                       AND `date_out` IS NOT NULL)";
      $result = $DB->query($query);

      return $DB->numrows($result);
   }


   /**
    * count how many consumable unused for the consumable item $tID
    *
    * @param $tID integer  consumable item identifier.
    *
    * @return integer : number of consumable unused counted.
   **/
   static function getUnusedNumber($tID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_consumables`
                WHERE (`consumableitems_id` = '$tID'
                       AND `date_out` IS NULL)";
      $result = $DB->query($query);

      return $DB->numrows($result);
   }


   /**
    * Get the consumable count HTML array for a defined consumable type
    *
    * @param $tID             integer  consumable item identifier.
    * @param $alarm_threshold integer  threshold alarm value.
    * @param $nohtml          integer  Return value without HTML tags. (default 0)
    *
    * @return string to display
   **/
   static function getCount($tID, $alarm_threshold, $nohtml = 0) {

      // Get total
      $total = self::getTotalNumber($tID);

      if ($total != 0) {
         $unused = self::getUnusedNumber($tID);
         $old    = self::getOldNumber($tID);

         $highlight = "";
         if ($unused <= $alarm_threshold) {
            $highlight = "class='tab_bg_1_2'";
         }
         //TRANS: For consumable. %1$d is total number, %2$d is unused number, %3$d is old number
         $tmptxt = sprintf(__('Total: %1$d, New: %2$d, Used: %3$d'), $total, $unused, $old);
         if ($nohtml) {
            $out = $tmptxt;
         } else {
            $out = "<div $highlight>".$tmptxt."</div>";
         }
      } else {
         if ($nohtml) {
            $out = __('No consumable');
         } else {
            $out = "<div class='tab_bg_1_2'><i>".__('No consumable')."</i></div>";
         }
      }
      return $out;
   }


   /**
    * Check if a Consumable is New (not used, in stock)
    *
    * @param $cID integer  consumable ID.
   **/
   static function isNew($cID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_consumables`
                WHERE (`id` = '$cID'
                       AND `date_out` IS NULL)";
      $result = $DB->query($query);

      return ($DB->numrows($result) == 1);
   }


   /**
    * Check if a consumable is Old (used, not in stock)
    *
    * @param $cID integer  consumable ID.
   **/
   static function isOld($cID) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_consumables`
                WHERE (`id` = '$cID'
                       AND `date_out` IS NOT NULL)";
      $result = $DB->query($query);

      return ($DB->numrows($result) == 1);
   }


   /**
    * Get the localized string for the status of a consumable
    *
    * @param $cID integer  consumable ID.
    *
    * @return string : dict value for the consumable status.
   **/
   static function getStatus($cID) {

      if (self::isNew($cID)) {
         return _nx('consumable', 'New', 'New', 1);

      } else if (self::isOld($cID)) {
         return _nx('consumable', 'Used', 'Used', 1);
      }
   }


   /**
    * Print out a link to add directly a new consumable from a consumable item.
    *
    * @param $consitem  ConsumableItem object
    *
    * @return Nothing (displays)
   **/
   static function showAddForm(ConsumableItem $consitem) {
      global $CFG_GLPI;

      $ID = $consitem->getField('id');

      if (!$consitem->can($ID, UPDATE)) {
         return false;
      }

      if ($ID > 0) {
         echo "<div class='firstbloc'>";
         echo "<form method='post' action=\"".static::getFormURL()."\">";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><td class='tab_bg_2 center'>";
         echo "<input type='hidden' name='consumableitems_id' value='$ID'>\n";
         Dropdown::showNumber('to_add', ['value' => 1,
                                              'min'   => 1,
                                              'max'   => 100]);
         echo " <input type='submit' name='add_several' value=\""._sx('button', 'Add consumables')."\"
                class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }
   }


   /**
    * Print out the consumables of a defined type
    *
    * @param $consitem           ConsumableItem object
    * @param $show_old  boolean  show old consumables or not. (default 0)
    *
    * @return Nothing (displays)
   **/
   static function showForConsumableItem(ConsumableItem $consitem, $show_old = 0) {
      global $DB, $CFG_GLPI;

      $tID = $consitem->getField('id');
      if (!$consitem->can($tID, READ)) {
         return false;
      }

      if (isset($_GET["start"])) {
         $start = $_GET["start"];
      } else {
         $start = 0;
      }

      $canedit = $consitem->can($tID, UPDATE);
      $rand = mt_rand();
      $where = ['consumableitems_id' => $tID];
      $order = ['date_in', 'id'];
      if (!$show_old) { // NEW
         $where += ['date_out' => 'NULL'];
      } else { //OLD
         $where += ['NOT'   => ['date_out' => 'NULL']];
         $order = ['date_out DESC'] + $order;
      }

      $number = countElementsInTable("glpi_consumables", $where);

      $iterator = $DB->request([
         'FROM'   => self::getTable(),
         'WHERE'  => $where,
         'ORDER'  => $order,
         'START'  => (int)$start,
         'LIMIT'  => (int)$_SESSION['glpilist_limit']
      ]);

      echo "<div class='spaced'>";

      // Display the pager
      Html::printAjaxPager(Consumable::getTypeName(Session::getPluralNumber()), $start, $number);

      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $actions = [];
         if ($consitem->can($tID, PURGE)) {
            $actions['delete'] = _x('button', 'Delete permanently');
         }
         $actions['Infocom'.MassiveAction::CLASS_ACTION_SEPARATOR.'activate']
            = __('Enable the financial and administrative information');

         if ($show_old) {
            $actions['Consumable'.MassiveAction::CLASS_ACTION_SEPARATOR.'backtostock']
                     = __('Back to stock');
         } else {
            $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'give'] = _x('button', 'Give');
         }
         $entparam = ['entities_id' => $consitem->getEntityID()];
         if ($consitem->isRecursive()) {
            $entparam = ['entities_id' => getSonsOf('glpi_entities', $consitem->getEntityID())];
         }
         $massiveactionparams = ['num_displayed'    => min($_SESSION['glpilist_limit'], $number),
                           'specific_actions' => $actions,
                           'container'        => 'mass'.__CLASS__.$rand,
                           'extraparams'      => $entparam];
         Html::showMassiveActions($massiveactionparams);
         echo "<input type='hidden' name='consumableitems_id' value='$tID'>\n";
      }

      echo "<table class='tab_cadre_fixehov'>";
      if (!$show_old) {
         echo "<tr><th colspan=".($canedit?'5':'4').">";
         echo self::getCount($tID, -1);
         echo "</th></tr>";
      } else { // Old
         echo "<tr><th colspan='".($canedit?'7':'6')."'>".__('Used consumables')."</th></tr>";
      }

      if ($number) {
         $i = 0;
         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';
         if ($canedit) {
            $header_begin  .= "<th width='10'>";
            $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_end    .= "</th>";
         }
         $header_end .= "<th>".__('ID')."</th>";
         $header_end .= "<th>"._x('item', 'State')."</th>";
         $header_end .= "<th>".__('Add date')."</th>";
         if ($show_old) {
            $header_end .= "<th>".__('Use date')."</th>";
            $header_end .= "<th>".__('Given to')."</th>";
         }
         $header_end .= "<th width='200px'>".__('Financial and administrative information')."</th>";
         $header_end .= "</tr>";
         echo $header_begin.$header_top.$header_end;

         while ($data = $iterator->next()) {
            $date_in  = Html::convDate($data["date_in"]);
            $date_out = Html::convDate($data["date_out"]);

            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
               echo "</td>";
            }
            echo "<td class='center'>".$data["id"]."</td>";
            echo "<td class='center'>".self::getStatus($data["id"])."</td>";
            echo "<td class='center'>".$date_in."</td>";
            if ($show_old) {
               echo "<td class='center'>".$date_out."</td>";
               echo "<td class='center'>";
               if ($item = getItemForItemtype($data['itemtype'])) {
                  if ($item->getFromDB($data['items_id'])) {
                     echo $item->getLink();
                  }
               }
               echo "</td>";
            }
            echo "<td class='center'>";
            Infocom::showDisplayLink('Consumable', $data["id"]);
            echo "</td>";
            echo "</tr>";
         }
         echo $header_begin.$header_bottom.$header_end;
      }
      echo "</table>";
      if ($canedit && $number) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }

      echo "</div>";
   }


   /**
    * Show the usage summary of consumables by user
    **/
   static function showSummary() {
      global $DB;

      if (!Consumable::canView()) {
         return false;
      }

      $query = "SELECT COUNT(*) AS count, `consumableitems_id`, `itemtype`, `items_id`
                FROM `glpi_consumables`
                WHERE `date_out` IS NOT NULL
                      AND `consumableitems_id` IN (SELECT `id`
                                                   FROM `glpi_consumableitems` ".
                                                   getEntitiesRestrictRequest("WHERE",
                                                                           "glpi_consumableitems").")
                GROUP BY `itemtype`, `items_id`, `consumableitems_id`";
      $used = [];

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetch_assoc($result)) {
               $used[$data['itemtype'].'####'.$data['items_id']][$data["consumableitems_id"]]
                  = $data["count"];
            }
         }
      }
      $query = "SELECT COUNT(*) AS count, `consumableitems_id`
                FROM `glpi_consumables`
                WHERE `date_out` IS NULL
                      AND `consumableitems_id` IN (SELECT `id`
                                                   FROM `glpi_consumableitems` ".
                                                   getEntitiesRestrictRequest("WHERE",
                                                                           "glpi_consumableitems").")
                GROUP BY `consumableitems_id`";
      $new = [];

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetch_assoc($result)) {
               $new[$data["consumableitems_id"]] = $data["count"];
            }
         }
      }

      $types = [];
      $query = "SELECT *
                FROM `glpi_consumableitems` ".
                getEntitiesRestrictRequest("WHERE", "glpi_consumableitems");

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($data = $DB->fetch_assoc($result)) {
               $types[$data["id"]] = $data["name"];
            }
         }
      }
      asort($types);
      $total = [];
      if (count($types) > 0) {
         // Produce headline
         echo "<div class='center'><table class='tab_cadrehov'><tr>";

         // Type
         echo "<th>".__('Give to')."</th>";

         foreach ($types as $key => $type) {
            echo "<th>$type</th>";
            $total[$key] = 0;
         }
         echo "<th>".__('Total')."</th>";
         echo "</tr>";

         // new
         echo "<tr class='tab_bg_2'><td class='b'>".__('In stock')."</td>";
         $tot = 0;
         foreach ($types as $id_type => $type) {
            if (!isset($new[$id_type])) {
               $new[$id_type] = 0;
            }
            echo "<td class='center'>".$new[$id_type]."</td>";
            $total[$id_type] += $new[$id_type];
            $tot             += $new[$id_type];
         }
         echo "<td class='numeric'>".$tot."</td>";
         echo "</tr>";

         foreach ($used as $itemtype_items_id => $val) {
            echo "<tr class='tab_bg_2'><td>";
            list($itemtype,$items_id) = explode('####', $itemtype_items_id);
            $item = new $itemtype();
            if ($item->getFromDB($items_id)) {
               //TRANS: %1$s is a type name - %2$s is a name
               printf(__('%1$s - %2$s'), $item->getTypeName(1), $item->getNameID());
            }
            echo "</td>";
            $tot = 0;
            foreach ($types as $id_type => $type) {
               if (!isset($val[$id_type])) {
                  $val[$id_type] = 0;
               }
               echo "<td class='center'>".$val[$id_type]."</td>";
               $total[$id_type] += $val[$id_type];
               $tot             += $val[$id_type];
            }
            echo "<td class='numeric'>".$tot."</td>";
            echo "</tr>";
         }
         echo "<tr class='tab_bg_1'><td class='b'>".__('Total')."</td>";
         $tot = 0;
         foreach ($types as $id_type => $type) {
            $tot += $total[$id_type];
            echo "<td class='numeric'>".$total[$id_type]."</td>";
         }
         echo "<td class='numeric'>".$tot."</td>";
         echo "</tr>";
         echo "</table></div>";

      } else {
         echo "<div class='center b'>".__('No consumable found')."</div>";
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate && Consumable::canView()) {
         $nb = 0;
         switch ($item->getType()) {
            case 'ConsumableItem' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb =  self::countForConsumableItem($item);
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }


   /**
    * @param $item   string  ConsumableItem object
   **/
   static function countForConsumableItem(ConsumableItem $item) {

      return countElementsInTable(['glpi_consumables'], ['glpi_consumables.consumableitems_id' => $item->getField('id')]);
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'ConsumableItem' :
            self::showAddForm($item);
            self::showForConsumableItem($item);
            self::showForConsumableItem($item, 1);
            return true;
      }
   }

   function getRights($interface = 'central') {
      $ci = new ConsumableItem();
      return $ci->getRights($interface);
   }
}
