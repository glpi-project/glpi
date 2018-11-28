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
 * Infocom class
**/
class Infocom extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype        = 'itemtype';
   static public $items_id        = 'items_id';
   public $dohistory              = true;
   public $auto_message_on_action = false; // Link in message can't work'
   static public $logs_for_parent = false;
   static $rightname              = 'infocom';

   //Option to automatically fill dates
   const ON_STATUS_CHANGE   = 'STATUS';
   const COPY_WARRANTY_DATE = 1;
   const COPY_BUY_DATE      = 2;
   const COPY_ORDER_DATE    = 3;
   const COPY_DELIVERY_DATE = 4;
   const ON_ASSET_IMPORT    = 5;


   /**
    * Check if given object can have Infocom
    *
    * @since 0.85
    *
    * @param $item  an object or a string
    *
    * @return true if $object is an object that can have Infocom
    *
   **/
   static function canApplyOn($item) {
      global $CFG_GLPI;

      // All devices are subjects to infocom !
      if (is_a($item, 'Item_Devices', true)) {
         return true;
      }

      // We also allow direct items to check
      if ($item instanceof CommonGLPI) {
         $item = $item->getType();
      }

      if (in_array($item, $CFG_GLPI['infocom_types'])) {
         return true;
      }

      return false;
   }


   /**
    * Get all the types that can have an infocom
    *
    * @since 0.85
    *
    * @return array of the itemtypes
   **/
   static function getItemtypesThatCanHave() {
      global $CFG_GLPI;

      return array_merge($CFG_GLPI['infocom_types'],
                         Item_Devices::getDeviceTypes());
   }


   static function getTypeName($nb = 0) {
      //TRANS: Always plural
      return __('Financial and administrative information');
   }


   function post_getEmpty() {

      $this->fields["alert"] = Entity::getUsedConfig("use_infocoms_alert",
                                                     $this->fields["entities_id"],
                                                     "default_infocom_alert", 0);
   }


   function getLogTypeID() {
      return [$this->fields['itemtype'], $this->fields['items_id']];
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      // Can exists on template
      if (Session::haveRight(self::$rightname, READ)) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Supplier' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = self::countForSupplier($item);
               }
               return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb);

            default :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_infocoms',
                                             ['itemtype' => $item->getType(),
                                              'items_id' => $item->getID()]);
               }
               return self::createTabEntry(__('Management'), $nb);
         }
      }
      return '';
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum          (default 1)
    * @param $withtemplate    (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'Supplier' :
            $item->showInfocoms();
            break;

         default :
            self::showForItem($item, $withtemplate);
      }
      return true;
   }


   /**
    * @param $item   Supplier  object
   **/
   static function countForSupplier(Supplier $item) {

      return countElementsInTable(
         'glpi_infocoms',
         [
            'suppliers_id' => $item->getField('id'),
            'NOT' => ['itemtype' => ['ConsumableItem', 'CartridgeItem', 'Software']]
         ] + getEntitiesRestrictCriteria('glpi_infocoms', '', $_SESSION['glpiactiveentities'])
      );
   }

   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'sink_type' :
            return self::getAmortTypeName($values[$field]);

         case 'alert' :
            return self::getAlertName($values[$field]);

      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case "sink_type" :
            return self::dropdownAmortType($name, $values[$field], false);

         case "alert" :
            $options['name']  = $name;
            $options['value'] = $values[$field];
            return self::dropdownAlert($options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Retrieve an item from the database for a device
    *
    * @param $itemtype  type of the device to retrieve infocom
    * @param $ID        ID of the device to retrieve infocom
    *
    * @return true if succeed else false
   **/
   function getFromDBforDevice ($itemtype, $ID) {

      if ($this->getFromDBByCrit([
         $this->getTable() . '.items_id'  => $ID,
         $this->getTable() . '.itemtype'  => $itemtype
      ])) {
         return true;
      }
      $this->getEmpty();
      $this->fields["items_id"] = $ID;
      $this->fields["itemtype"] = $itemtype;
      return false;
   }


   /**
    * @see CommonDBChild::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {
      global $CFG_GLPI;
      if (!$this->getFromDBforDevice($input['itemtype'], $input['items_id'])) {
         if ($item = static::getItemFromArray(static::$itemtype, static::$items_id, $input)) {
            $input['alert'] = Entity::getUsedConfig('default_infocom_alert', $item->getEntityID());
            return parent::prepareInputForAdd($input);
         }
      }
      return false;
   }


   /**
    * Fill, if necessary, automatically some dates when status changes
    *
    * @param item          CommonDBTM object: the item whose status have changed
    * @param action_add    true if object is added, false if updated (true by default)
    *
    * @return nothing
   **/
   static function manageDateOnStatusChange(CommonDBTM $item, $action_add = true) {
      global $CFG_GLPI;

      $itemtype = get_class($item);
      $changes  = $item->fields;

      //Autofill date on item's status change ?
      $infocom = new self();
      $infocom->getFromDB($changes['id']);
      $tmp           = ['itemtype' => $itemtype,
                             'items_id' => $changes['id']];
      $add_or_update = false;

      //For each date that can be automatically filled
      foreach (self::getAutoManagemendDatesFields() as $date => $date_field) {
         $resp   = [];
         $result = Entity::getUsedConfig($date, $changes['entities_id']);

         //Date must be filled if status corresponds to the one defined in the config
         if (preg_match('/'.self::ON_STATUS_CHANGE.'_(.*)/', $result, $values)
             && ($values[1] == $changes['states_id'])) {
            $add_or_update    = true;
            $tmp[$date_field] = $_SESSION["glpi_currenttime"];
         }
      }

      //One date or more has changed
      if ($add_or_update) {

         if (!$infocom->getFromDBforDevice($itemtype, $changes['id'])) {
            $infocom->add($tmp);
         } else {
            $tmp['id'] = $infocom->fields['id'];
            $infocom->update($tmp);
         }
      }
   }


   /**
    * Automatically manage copying one date to another is necessary
    *
    * @param infocoms   array of item's infocom to modify
    * @param field            the date to modify (default '')
    * @param action           the action to peform (copy from another date) (default 0)
    * @param params     array of additional parameters needed to perform the task
    *
    * @return nothing
   **/
   static function autofillDates(&$infocoms = [], $field = '', $action = 0, $params = []) {

      if (isset($infocoms[$field])) {
         switch ($action) {
            default :
            case 0 :
               break;

            case self::COPY_WARRANTY_DATE :
               if (isset($infocoms['warranty_date'])) {
                  $infocoms[$field] = $infocoms['warranty_date'];
               }
               break;

            case self::COPY_BUY_DATE :
               if (isset($infocoms['buy_date'])) {
                  $infocoms[$field] = $infocoms['buy_date'];
               }
               break;

            case self::COPY_ORDER_DATE :
               if (isset($infocoms['order_date'])) {
                  $infocoms[$field] = $infocoms['order_date'];
               }
               break;

            case self::COPY_DELIVERY_DATE :
               if (isset($infocoms['delivery_date'])) {
                  $infocoms[$field] = $infocoms['delivery_date'];
               }
               break;
         }
      }
   }


   /**
    * Return all infocom dates that could be automaticall filled
    *
    * @return an array with all dates (configuration field & real field)
   **/
   static function getAutoManagemendDatesFields() {

      return ['autofill_buy_date'         => 'buy_date',
                   'autofill_use_date'         => 'use_date',
                   'autofill_delivery_date'    => 'delivery_date',
                   'autofill_warranty_date'    => 'warranty_date',
                   'autofill_order_date'       => 'order_date',
                   'autofill_decommission_date' => 'decommission_date'];
   }


   /**
    * @see CommonDBChild::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      //Check if one or more dates needs to be updated
      foreach (self::getAutoManagemendDatesFields() as $key => $field) {
         $result = Entity::getUsedConfig($key, $this->fields['entities_id']);

         //Only update date if it's empty in DB. Otherwise do nothing
         if (($result > 0)
             && !isset($this->fields[$field])) {
            self::autofillDates($input, $field, $result);
         }
      }

      return parent::prepareInputForUpdate($input);
   }


   function pre_updateInDB() {

      // Clean end alert if warranty_date is after old one
      // Or if duration is greater than old one
      if ((isset($this->oldvalues['warranty_date'])
           && ($this->oldvalues['warranty_date'] < $this->fields['warranty_date']))
          || (isset($this->oldvalues['warranty_duration'])
              && ($this->oldvalues['warranty_duration'] < $this->fields['warranty_duration']))) {

         $alert = new Alert();
         $alert->clear($this->getType(), $this->fields['id'], Alert::END);
      }
      // Check budgets link validity
      if ((in_array('budgets_id', $this->updates)
           || in_array('buy_date', $this->updates))
          && $this->fields['budgets_id']
          && ($budget = getItemForItemtype('Budget'))
          && $budget->getFromDB($this->fields['budgets_id'])) {

         if ((!is_null($budget->fields['begin_date'])
              && $this->fields['buy_date'] < $budget->fields['begin_date'])
             || (!is_null($budget->fields['end_date'])
                 && ($this->fields['buy_date'] > $budget->fields['end_date']))) {

            $msg = sprintf(__('Purchase date incompatible with the associated budget. %1$s not in budget period: %2$s / %3$s'),
                           Html::convDate($this->fields['buy_date']),
                           Html::convDate($budget->fields['begin_date']),
                           Html::convDate($budget->fields['end_date']));
            Session::addMessageAfterRedirect($msg, false, ERROR);
         }
      }
   }

   /**
    * @since 0.84
   **/
   function cleanDBonPurge() {

      $class = new Alert();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }


   /**
    * @param $name
   **/
   static function cronInfo($name) {
      return ['description' => __('Send alarms on financial and administrative information')];
   }


   /**
    * Cron action on infocom : alert on expired warranty
    *
    * @param $task to log, if NULL use display (default NULL)
    *
    * @return 0 : nothing to do 1 : done with success
   **/
   static function cronInfocom($task = null) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["use_notifications"]) {
         return 0;
      }

      $message        = [];
      $cron_status    = 0;
      $items_infos    = [];
      $items_messages = [];

      foreach (Entity::getEntitiesToNotify('use_infocoms_alert') as $entity => $value) {
         $before    = Entity::getUsedConfig('send_infocoms_alert_before_delay', $entity);
         $query_end = "SELECT `glpi_infocoms`.*
                       FROM `glpi_infocoms`
                       LEFT JOIN `glpi_alerts` ON (`glpi_infocoms`.`id` = `glpi_alerts`.`items_id`
                                                   AND `glpi_alerts`.`itemtype` = 'Infocom'
                                                   AND `glpi_alerts`.`type`='".Alert::END."')
                       WHERE (`glpi_infocoms`.`alert` & ".pow(2, Alert::END).") >'0'
                             AND `glpi_infocoms`.`entities_id`='".$entity."'
                             AND `glpi_infocoms`.`warranty_duration`>'0'
                             AND `glpi_infocoms`.`warranty_date` IS NOT NULL
                             AND DATEDIFF(ADDDATE(`glpi_infocoms`.`warranty_date`,
                                                  INTERVAL (`glpi_infocoms`.`warranty_duration`)
                                                           MONTH),
                                          CURDATE() ) <= '$before'
                             AND `glpi_alerts`.`date` IS NULL";

         foreach ($DB->request($query_end) as $data) {
            if ($item_infocom = getItemForItemtype($data["itemtype"])) {
               if ($item_infocom->getFromDB($data["items_id"])) {
                  $entity   = $data['entities_id'];
                  $warranty = self::getWarrantyExpir($data["warranty_date"], $data["warranty_duration"]);
                  //TRANS: %1$s is a type, %2$s is a name (used in croninfocom)
                  $name    = sprintf(__('%1$s - %2$s'), $item_infocom->getTypeName(1),
                                     $item_infocom->getName());
                  //TRANS: %1$s is the warranty end date and %2$s the name of the item
                  $message = sprintf(__('Item reaching the end of warranty on %1$s: %2$s'),
                                     $warranty, $name)."<br>";

                  $data['warrantyexpiration']        = $warranty;
                  $data['item_name']                 = $item_infocom->getName();
                  $items_infos[$entity][$data['id']] = $data;

                  if (!isset($items_messages[$entity])) {
                     $items_messages[$entity] = __('No item reaching the end of warranty.')."<br>";
                  }
                  $items_messages[$entity] .= $message;
               }
            }
         }
      }

      foreach ($items_infos as $entity => $items) {
         if (NotificationEvent::raiseEvent("alert", new self(), ['entities_id' => $entity,
                                                                      'items'       => $items])) {
            $message     = $items_messages[$entity];
            $cron_status = 1;
            if ($task) {
               $task->log(sprintf(__('%1$s: %2$s')."\n",
                                  Dropdown::getDropdownName("glpi_entities", $entity), $message));
               $task->addVolume(1);
            } else {
               Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'),
                                                        Dropdown::getDropdownName("glpi_entities",
                                                                                  $entity),
                                                        $message));
            }

            $alert             = new Alert();
            $input["itemtype"] = 'Infocom';
            $input["type"]     = Alert::END;
            foreach ($items as $id => $item) {
               $input["items_id"] = $id;
               $alert->add($input);
               unset($alert->fields['id']);
            }

         } else {
            $entityname = Dropdown::getDropdownName('glpi_entities', $entity);
            //TRANS: %s is entity name
            $msg = sprintf(__('%1$s: %2$s'), $entityname, __('send infocom alert failed'));
            if ($task) {
               $task->log($msg);
            } else {
               Session::addMessageAfterRedirect($msg, false, ERROR);
            }
         }
      }
      return $cron_status;
   }


   /**
    * Get the possible value for infocom alert
    *
    * @since 0.84 (before in alert.class)
    *
    * @param $val if not set, ask for all values, else for 1 value (default NULL)
    *
    * @return array or string
   **/
   static function getAlertName($val = null) {

      $tmp[0]                  = Dropdown::EMPTY_VALUE;
      $tmp[pow(2, Alert::END)] = __('Warranty expiration date');

      if (is_null($val)) {
         return $tmp;
      }
      // Default value for display
      $tmp[0] = ' ';

      if (isset($tmp[$val])) {
         return $tmp[$val];
      }
      // If not set and is a string return value
      if (is_string($val)) {
         return $val;
      }
      return NOT_AVAILABLE;
   }


   /**
    * @param $options array
   **/
   static function dropdownAlert($options) {

      $p['name']           = 'alert';
      $p['value']          = 0;
      $p['display']        = true;
      $p['inherit_parent'] = false;

      if (count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $tab = [];
      if ($p['inherit_parent']) {
         $tab[Entity::CONFIG_PARENT] = __('Inheritance of the parent entity');
      }

      $tab += self::getAlertName();

      return Dropdown::showFromArray($p['name'], $tab, $p);
   }


   /**
    * Dropdown of amortissement type for infocoms
    *
    * @param $name      select name
    * @param $value     default value (default 0)
    * @param $display   display or get string (true by default)
   **/
   static function dropdownAmortType($name, $value = 0, $display = true) {

      $values = [2 => __('Linear'),
                      1 => __('Decreasing')];

      return Dropdown::showFromArray($name, $values,
                                     ['value'               => $value,
                                           'display'             => $display,
                                           'display_emptychoice' => true]);
   }


   /**
    * Get amortissement type name for infocoms
    *
    * @param $value status ID
   **/
   static function getAmortTypeName($value) {

      switch ($value) {
         case 2 :
            return __('Linear');

         case 1 :
            return __('Decreasing');

         case 0 :
            return " ";
      }
   }


   /**
    * Calculate TCO and TCO by month for an item
    *
    * @param $ticket_tco    Tco part of tickets
    * @param $value
    * @param $date_achat    (default '')
    *
    * @return float
   **/
   static function showTco($ticket_tco, $value, $date_achat = "") {
      if ($ticket_tco == NOT_AVAILABLE) {
         return '-';
      }

      // Affiche le TCO ou le TCO mensuel pour un mat??riel
      $totalcost = $ticket_tco;

      if ($date_achat) { // on veut donc le TCO mensuel
         // just to avoid IDE warning
         $date_Y = $date_m = $date_d = 0;

         sscanf($date_achat, "%4s-%2s-%2s", $date_Y, $date_m, $date_d);

         $timestamp2 = mktime(0, 0, 0, $date_m, $date_d, $date_Y);
         $timestamp  = mktime(0, 0, 0, date("m"), date("d"), date("Y"));

         $diff = floor(($timestamp - $timestamp2) / (MONTH_TIMESTAMP)); // Mois d'utilisation

         if ($diff) {
            return Html::formatNumber((($totalcost+$value)/$diff)); // TCO mensuel
         }
         return "";
      }
      return Html::formatNumber(($totalcost+$value)); // TCO
   }// fin showTCO


   /**
    * Show infocom link to display modal
    *
    * @param $itemtype   integer  item type
    * @param $device_id  integer  item ID
    *
    * @return float
   **/
   static function showDisplayLink($itemtype, $device_id) {
      global $DB, $CFG_GLPI;

      if (!Session::haveRight(self::$rightname, READ)
          || !($item = getItemForItemtype($itemtype))) {
         return false;
      }

      $query = "SELECT COUNT(*)
                FROM `glpi_infocoms`
                WHERE `items_id` = '$device_id'
                      AND `itemtype` = '$itemtype'";

      $add    = "add";
      $text   = __('Add');
      $result = $DB->query($query);
      if ($DB->result($result, 0, 0) > 0) {
         $add  = "";
         $text = _x('button', 'Show');
      } else if (!Infocom::canUpdate()) {
         return false;
      }

      if ($item->canView()) {
         echo "<span onClick=\"".Html::jsGetElementbyID('infocom'.$itemtype.$device_id).".
                dialog('open');\" style='cursor:pointer'>
               <img src=\"".$CFG_GLPI["root_doc"]."/pics/dollar$add.png\" alt=\"$text\" title=\"$text\">
               </span>";
         Ajax::createIframeModalWindow('infocom'.$itemtype.$device_id,
                                       Infocom::getFormURL().
                                          "?itemtype=$itemtype&items_id=$device_id",
                                       ['height' => 600]);
      }
   }


   /**
    * Calculate amortization values
    *
    * @param $value      Purchase value
    * @param $duration   Amortise duration
    * @param $fiscaldate Begin of fiscal excercise
    * @param $buydate    Buy date
    * @param $usedate    Date of use
    *
    * @return array|boolean
    */
   static public function linearAmortise($value, $duration, $fiscaldate, $buydate = '', $usedate = '') {
      //Set timezone to UTC; see https://stackoverflow.com/a/40358744
      $TZ = 'UTC';

      try {
         if ($fiscaldate == '') {
            throw new \RuntimeException('Empty date');
         }
         $fiscaldate = new \DateTime($fiscaldate, new DateTimeZone($TZ));
      } catch (\Exception $e) {
         Session::addMessageAfterRedirect(
            __('Please fill you fiscal year date in preferences.'),
            false,
            ERROR
         );
         return false;
      }

      //get begin date. Work on use date if provided.
      try {
         if ($buydate == '' && $usedate == '') {
            throw new \RuntimeException('Empty date');
         }
         if ($usedate != '') {
            $usedate = new \DateTime($usedate, new DateTimeZone($TZ));
         } else {
            $usedate = new \DateTime($buydate, new DateTimeZone($TZ));
         }
      } catch (\Exception $e) {
         Session::addMessageAfterRedirect(
            __('Please fill either buy or use date in preferences.'),
            false,
            ERROR
         );
         return false;
      }

      $now = new \DateTime('now', new DateTimeZone($TZ));

      $elapsed_years = $now->format('Y') - $usedate->format('Y');

      $annuity = $value * (1 / $duration);
      $years = [];
      for ($i = 0; $i <= $elapsed_years; ++$i) {
         $begin_value      = $value;
         $current_annuity  = $annuity;
         $fiscal_end       = new \DateTime(
            $fiscaldate->format('d-m-') . ($usedate->format('Y') + $i),
            new DateTimeZone($TZ)
         );

         if ($i == 0) {
            //first year, calculate prorata
            if ($fiscal_end < $usedate) {
               $fiscal_end->modify('+1 year');
            }
            $days = $fiscal_end->diff($usedate);
            $days = $days->format('%m') * 30 + $days->format('%d');
            $current_annuity = $annuity * $days / 360;
         } else if ($i == $duration) {
            $current_annuity = $value;
         }
         if ($i > $duration) {
            $value = 0;
            $current_annuity = 0;
         } else {
            //calculate annuity
            //full year case
            $value -= $current_annuity;
         }

         $years[$usedate->format('Y') + $i] = [
            'start_value'  => (double)$begin_value,
            'value'        => $value,
            'annuity'      => $current_annuity
         ];
      }

      return $years;
   }

   /**
    * Maps new amortise format to old one...
    * To not rewrite all the old method.
    *
    * @param array $values New format amortise values
    * @param boolean $current True to get only current year, false to get the whole array
    *
    * @return array|doulbe
    */
   public static function mapOldAmortiseFormat($values, $current = true) {

      if ($current === true) {
         return $values[date('Y')]['value'];
      }

      $old = [
         'annee'     => [],
         'annuite'   => [],
         'vcnetdeb'  => [],
         'vcnetfin'  => []
      ];
      foreach ($values as $year => $value) {
         $old['annee'][]      = $year;
         $old['annuite'][]    = $value['annuity'];
         $old['vcnetdeb'][]   = $value['start_value'];
         $old['vcnetfin'][]   = $value['value'];
      }

      return $old;
   }

   /**
    * Calculate amortissement for an item
    *
    * @param $type_amort    type d'amortisssment "lineaire=2" ou "degressif=1"
    * @param $va            valeur d'acquisition
    * @param $duree         duree d'amortissement
    * @param $coef          coefficient d'amortissement
    * @param $date_achat    Date d'achat
    * @param $date_use      Date d'utilisation
    * @param $date_tax      date du debut de l'annee fiscale
    * @param $view          "n" pour l'annee en cours ou "all" pour le tableau complet (default 'n')
    *
    * @return float or array
   **/
   static function Amort($type_amort, $va, $duree, $coef, $date_achat, $date_use, $date_tax,
                         $view = "n") {
      // By Jean-Mathieu Doleans qui s'est un peu pris le chou :p

      // Attention date mise en service/dateachat ->amort lineaire  et $prorata en jour !!
      // amort degressif au prorata du nombre de mois.
      // Son point de depart est le 1er jour du mois d'acquisition et non date de mise en service

      if ($type_amort == "2") {
         $values = self::linearAmortise($va, $duree, $date_tax, $date_achat, $date_use);
         if ($values == false) {
            return '-';
         }
         return self::mapOldAmortiseFormat($values, $view != 'all');
      }

      $prorata             = 0;
      $ecartfinmoiscourant = 0;
      $ecartmoisexercice   = 0;
      $date_Y  =  $date_m  =  $date_d  =  $date_H  =  $date_i  =  $date_s  =  0;
      sscanf($date_achat, "%4s-%2s-%2s %2s:%2s:%2s",
             $date_Y, $date_m, $date_d,
             $date_H, $date_i, $date_s); // un traitement sur la date mysql pour recuperer l'annee

      // un traitement sur la date mysql pour les infos necessaires
      $date_Y2 = $date_m2 = $date_d2 = $date_H2 = $date_i2 = $date_s2=0;
      sscanf($date_tax, "%4s-%2s-%2s %2s:%2s:%2s",
             $date_Y2, $date_m2, $date_d2,
             $date_H2, $date_i2, $date_s2);
      $date_Y2 = date("Y");

      switch ($type_amort) {
         case "1" :
            //########################### Calcul amortissement degressif ###########################
            if (($va > 0)
                && ($duree > 0)
                && ($coef > 1)
                && !empty($date_achat)) {
               //## calcul du prorata temporis en mois ##
               // si l'annee fiscale debute au dela de l'annee courante
               if ($date_m > $date_m2) {
                  $date_m2 = $date_m2+12;
               }
               $ecartmois      = ($date_m2-$date_m)+1; // calcul ecart entre mois d'acquisition
                                                       // et debut annee fiscale
               $prorata        = $ecartfinmoiscourant+$ecartmois-$ecartmoisexercice;
               // calcul tableau d'amortissement ##
               $txlineaire     = (100/$duree); // calcul du taux lineaire virtuel
               $txdegressif    = $txlineaire*$coef; // calcul du taux degressif
               $dureelineaire  = (int) (100/$txdegressif); // calcul de la duree de l'amortissement
                                                           // en mode lineaire
               $dureedegressif = $duree-$dureelineaire; // calcul de la duree de l'amortissement
                                                        // en mode degressif
               $mrt            = $va;
               // amortissement degressif pour les premieres annees
               for ($i=1; $i<=$dureedegressif; $i++) {
                  $tab['annee'][$i]    = $date_Y+$i-1;
                  $tab['vcnetdeb'][$i] = $mrt; // Pour chaque annee on calcule la valeur comptable nette
                                             // de debut d'exercice
                  $tab['annuite'][$i]  = $tab['vcnetdeb'][$i]*$txdegressif/100;
                  $tab['vcnetfin'][$i] = $mrt - $tab['annuite'][$i]; //Pour chaque annee on calcule la valeur
                                                                   //comptable nette de fin d'exercice
                  // calcul de la premiere annuite si prorata temporis
                  if ($prorata > 0) {
                     $tab['annuite'][1]  = ($va*$txdegressif/100)*($prorata/12);
                     $tab['vcnetfin'][1] = $va - $tab['annuite'][1];
                  }
                  $mrt = $tab['vcnetfin'][$i];
               }
               // amortissement en lineaire pour les derneres annees
               if ($dureelineaire != 0) {
                  $txlineaire = (100/$dureelineaire); // calcul du taux lineaire
               } else {
                  $txlineaire = 100;
               }
               $annuite = ($tab['vcnetfin'][$dureedegressif]*$txlineaire)/100; // calcul de l'annuite
               $mrt     = $tab['vcnetfin'][$dureedegressif];
               for ($i=$dureedegressif+1; $i<=$dureedegressif+$dureelineaire; $i++) {
                  $tab['annee'][$i]    = $date_Y+$i-1;
                  $tab['annuite'][$i]  = $annuite;
                  $tab['vcnetdeb'][$i] = $mrt; // Pour chaque annee on calcule la valeur comptable nette
                                               // de debut d'exercice
                  $tab['vcnetfin'][$i] = abs(($mrt - $annuite)); // Pour chaque annee on calcule la valeur
                                                               // comptable nette de fin d'exercice
                  $mrt                 = $tab['vcnetfin'][$i];
               }
               // calcul de la derniere annuite si prorata temporis
               if ($prorata > 0) {
                  $tab['annuite'][$duree] = $tab['vcnetdeb'][$duree];
                  if (isset($tab['vcnetfin'][$duree-1])) {
                     $tab['vcnetfin'][$duree] = ($tab['vcnetfin'][$duree-1] - $tab['annuite'][$duree]);
                  } else {
                     $tab['vcnetfin'][$duree] = 0;
                  }
               }
            } else {
               return "-";
            }
            break;

         default :
            return "-";
      }

      // le return
      if ($view == "all") {
         // on retourne le tableau complet
         return $tab;
      }
      // on retourne juste la valeur residuelle
      // si on ne trouve pas l'annee en cours dans le tableau d'amortissement dans le tableau,
      // le materiel est amorti
      if (!array_search(date("Y"), $tab["annee"])) {
         $vnc = 0;
      } else if (mktime(0, 0, 0, $date_m2, $date_d2, date("Y"))
                 - mktime(0, 0, 0, date("m"), date("d"), date("Y")) < 0 ) {
         // on a depasse la fin d'exercice de l'annee en cours
         //on prend la valeur residuelle de l'annee en cours
         $vnc = $tab["vcnetfin"][array_search(date("Y"), $tab["annee"])];
      } else {
         // on se situe avant la fin d'exercice
         // on prend la valeur residuelle de l'annee n-1
         $vnc = $tab["vcnetdeb"][array_search(date("Y"), $tab["annee"])];
      }
      return $vnc;
   }


   /**
    * Show Infocom form for an item (not a standard showForm)
    *
    * @param $item                  CommonDBTM object
    * @param $withtemplate integer  template or basic item (default 0)
   **/
   static function showForItem(CommonDBTM $item, $withtemplate = 0) {
      global $CFG_GLPI;

      // Show Infocom or blank form
      if (!self::canView()) {
         return false;
      }

      if (!$item) {
         echo "<div class='spaced'>".__('Requested item not found')."</div>";
      } else {
         $date_tax = $CFG_GLPI["date_tax"];
         $dev_ID   = $item->getField('id');
         $ic       = new self();
         $option   = "";
         if ($withtemplate == 2) {
            $option = " readonly ";
         }

         if (!strpos($_SERVER['PHP_SELF'], "infocoms-show")
             && in_array($item->getType(), self::getExcludedTypes())) {
            echo "<div class='firstbloc center'>".
                  __('For this type of item, the financial and administrative information are only a model for the items which you should add.').
                 "</div>";
         }
         if (!$ic->getFromDBforDevice($item->getType(), $dev_ID)) {
            $input = ['itemtype'    => $item->getType(),
                           'items_id'    => $dev_ID,
                           'entities_id' => $item->getEntityID()];

            if ($ic->can(-1, CREATE, $input)
                && ($withtemplate != 2)) {
               echo "<div class='spaced b'>";
               echo "<table class='tab_cadre_fixe'><tr class='tab_bg_1'><th>";
               echo sprintf(__('%1$s - %2$s'), $item->getTypeName(1), $item->getName())."</th></tr>";
               echo "<tr class='tab_bg_1'><td class='center'>";

               Html::showSimpleForm(Infocom::getFormURL(),
                                    'add', __('Enable the financial and administrative information'),
                                     ['itemtype' => $item->getType(),
                                           'items_id' => $dev_ID]);
               echo "</td></tr></table></div>";
            }

         } else { // getFromDBforDevice
            $canedit = ($ic->canEdit($ic->fields['id']) && ($withtemplate != 2));
            echo "<div class='spaced'>";
            if ($canedit) {
               echo "<form name='form_ic' method='post' action='".Infocom::getFormURL()."'>";
            }
            echo "<table class='tab_cadre".(!strpos($_SERVER['PHP_SELF'],
                                                    "infocoms-show")?"_fixe":"")."'>";

            // Can edit calendar ?
            $editcalendar = ($withtemplate != 2);

            echo "<tr><th colspan='4'>".__('Asset lifecycle')."</th></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Order date')."</td><td>";
            Html::showDateField("order_date", ['value'      => $ic->fields["order_date"],
                                                    'maybeempty' => true,
                                                    'canedit'    => $editcalendar]);
            echo "</td>";
            echo "<td>".__('Date of purchase')."</td><td>";
            Html::showDateField("buy_date", ['value'      => $ic->fields["buy_date"],
                                                  'maybeempty' => true,
                                                  'canedit'    => $editcalendar]);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Delivery date')."</td><td>";
            Html::showDateField("delivery_date", ['value'      => $ic->fields["delivery_date"],
                                                       'maybeempty' => true,
                                                       'canedit'    => $editcalendar]);
            echo "</td>";
            echo "<td>".__('Startup date')."</td><td>";
            Html::showDateField("use_date", ['value'      => $ic->fields["use_date"],
                                                  'maybeempty' => true,
                                                  'canedit'    => $editcalendar]);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Date of last physical inventory')."</td><td>";
            Html::showDateField("inventory_date",
                                ['value'      => $ic->fields["inventory_date"],
                                      'maybeempty' => true,
                                      'canedit'    => $editcalendar]);
            echo "</td>";
            echo "<td>".__('Decommission date')."</td><td>";
            Html::showDateField("decommission_date",
                                ['value'      => $ic->fields["decommission_date"],
                                      'maybeempty' => true,
                                      'canedit'    => $editcalendar]);
            echo "</td></tr>";

            echo "<tr><th colspan='4'>".__('Financial and administrative information')."</th></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Supplier')."</td>";
            echo "<td>";
            if ($withtemplate == 2) {
               echo Dropdown::getDropdownName("glpi_suppliers", $ic->fields["suppliers_id"]);
            } else {
               Supplier::dropdown(['value'  => $ic->fields["suppliers_id"],
                                        'entity' => $item->getEntityID(),
                                        'width'  => '70%']);
            }
            echo "</td>";
            if (Budget::canView()) {
               echo "<td>".__('Budget')."</td><td >";
               Budget::dropdown(['value'    => $ic->fields["budgets_id"],
                                      'entity'   => $item->getEntityID(),
                                      'comments' => 1]);
            } else {
               echo "<td colspan='2'>";
            }
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Order number')."</td>";
            echo "<td >";
            Html::autocompletionTextField($ic, "order_number", ['option' => $option]);
            echo "</td>";
            $tplmark = '';
            if ($item->isTemplate()
                || in_array($item->getType(),
                            self::getExcludedTypes())) {
               $tplmark = $item->getAutofillMark('immo_number', ['withtemplate' => $withtemplate], $ic->getField('immo_number'));
            }
            echo "<td>".sprintf(__('%1$s%2$s'), __('Immobilization number'), $tplmark)."</td>";
            echo "<td>";
            $objectName = autoName($ic->fields["immo_number"], "immo_number", ($withtemplate == 2),
                                   'Infocom', $item->getEntityID());
            Html::autocompletionTextField($ic, "immo_number", ['value'  => $objectName,
                                                                    'option' => $option]);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Invoice number')."</td>";
            echo "<td>";
            Html::autocompletionTextField($ic, "bill", ['option' => $option]);
            echo "</td>";
            echo "<td>".__('Delivery form')."</td><td>";
            Html::autocompletionTextField($ic, "delivery_number", ['option' => $option]);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>"._x('price', 'Value')."</td>";
            echo "<td><input type='text' name='value' $option value='".
                   Html::formatNumber($ic->fields["value"], true)."' size='14'></td>";
            echo "<td>".__('Warranty extension value')."</td>";
            echo "<td><input type='text' $option name='warranty_value' value='".
            Html::formatNumber($ic->fields["warranty_value"], true)."' size='14'></td>";
            echo "</tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Account net value')."</td><td>";
            echo Html::formatNumber(self::Amort($ic->fields["sink_type"], $ic->fields["value"],
                                                $ic->fields["sink_time"], $ic->fields["sink_coeff"],
                                                $ic->fields["buy_date"],
                                                $ic->fields["use_date"], $date_tax, "n"));
            echo "</td>";
            echo "<td rowspan='4'>".__('Comments')."</td>";
            echo "<td rowspan='4' class='middle'>";
            echo "<textarea cols='45' rows='9' name='comment' >".$ic->fields["comment"];
            echo "</textarea></td></tr>\n";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Amortization type')."</td><td >";
            if ($withtemplate == 2) {
               echo self::getAmortTypeName($ic->fields["sink_type"]);
            } else {
               self::dropdownAmortType("sink_type", $ic->fields["sink_type"]);
            }
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Amortization duration')."</td><td>";
            if ($withtemplate == 2) {
               printf(_n('%d year', '%d years', $ic->fields["sink_time"]), $ic->fields["sink_time"]);
            } else {
               Dropdown::showNumber("sink_time", ['value' => $ic->fields["sink_time"],
                                                       'max'   => 15,
                                                       'unit'  => 'year']);
            }
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Amortization coefficient')."</td>";
            echo "<td>";
            Html::autocompletionTextField($ic, "sink_coeff", ['size'   => 14,
                                                                   'option' => $option]);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            if (!in_array($item->getType(), self::getExcludedTypes() + [
                                                  'Cartridge', 'Consumable',
                                                  'SoftwareLicense'])) {
               echo "<td>".__('TCO (value + tracking cost)')."</td><td>";
               echo self::showTco($item->getField('ticket_tco'), $ic->fields["value"]);
            } else {
                echo "<td colspan='2'>";
            }
            echo "</td>";
            if (!in_array($item->getType(), self::getExcludedTypes() + [
                                                  'Cartridge', 'Consumable',
                                                  'SoftwareLicense'])) {
               echo "<td>".__('Monthly TCO')."</td><td>";
               echo self::showTco($item->getField('ticket_tco'), $ic->fields["value"],
                                  $ic->fields["buy_date"]);
            } else {
                echo "<td colspan='2'>";
            }
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Business criticity')."</td><td>";
            Dropdown::show('BusinessCriticity', ['value' => $ic->fields['businesscriticities_id']]);
            echo "</td>";
            echo "<td colspan='2'>";
            echo "</td></tr>";

            echo "<tr><th colspan='4'>".__('Warranty information')."</th></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Start date of warranty')."</td><td>";
            Html::showDateField("warranty_date", ['value'      => $ic->fields["warranty_date"],
                                                       'maybeempty' => true,
                                                       'canedit'    => $editcalendar]);
            echo "</td>";

            echo "<td>".__('Warranty duration')."</td><td>";
            if ($withtemplate == 2) {
               // -1 = life
               if ($ic->fields["warranty_duration"] == -1) {
                  echo __('Lifelong');
               } else {
                  printf(_n('%d month', '%d months', $ic->fields["warranty_duration"]),
                         $ic->fields["warranty_duration"]);
               }

            } else {
               Dropdown::showNumber("warranty_duration",
                                    ['value' => $ic->fields["warranty_duration"],
                                          'min'   => 0,
                                          'max'   => 120,
                                          'step'  => 1,
                                          'toadd' => [-1 => __('Lifelong')],
                                          'unit'  => 'month']);
            }
            $tmpdat = self::getWarrantyExpir($ic->fields["warranty_date"],
                                             $ic->fields["warranty_duration"], 0, true);
            if ($tmpdat) {
               echo "<span class='small_space'>".sprintf(__('Valid to %s'), $tmpdat)."</span>";
            }
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Warranty information')."</td>";
            echo "<td >";
            Html::autocompletionTextField($ic, "warranty_info", ['option' => $option]);
            echo "</td>";

            if ($CFG_GLPI['use_notifications']) {
               echo "<td>".__('Alarms on financial and administrative information')."</td>";
               echo "<td>";
               self::dropdownAlert(['name'    => "alert",
                                         'value'   => $ic->fields["alert"]]);
               Alert::displayLastAlert('Infocom', $ic->fields['id']);
            } else {
               echo "</td><td colspan='2'>";
            }
            echo "</td></tr>";

            //We use a static method to call the hook
            //It's then easier for plugins to detect if the hook is available or not
            //The just have to look for the addPluginInfos method
            self::addPluginInfos($item);

            if ($canedit
                && Session::haveRightsOr(self::$rightname, [UPDATE, PURGE])) {
               echo "<tr>";
               if (Session::haveRight(self::$rightname, UPDATE)) {
                  echo "<td class='tab_bg_2 center' colspan='2'>";
                  echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\"
                         class='submit'>";
                  echo "</td>";
               }
               if (Session::haveRight(self::$rightname, PURGE)) {
                  echo "<td class='tab_bg_2 center' colspan='2'>";
                  echo "<input type='submit' name='purge' value=\""._sx('button',
                                                                      'Delete permanently')."\"
                        class='submit'>";
                  echo "</td>";
               }
               echo "<td><input type='hidden' name='id' value='".$ic->fields['id']."'></td></tr>";
               echo "</table>";
               Html::closeForm();
            } else {
               echo "</table>";
            }
            echo "</div>";
         }
      }
   }

   static function addPluginInfos(CommonDBTM $item) {
      Plugin::doHookFunction("infocom", $item);
   }

   /**
    * @param $itemtype
   **/
   static function rawSearchOptionsToAdd($itemtype = null) {
      $specific_itemtype = '';
      $beforejoin        = [];

      switch ($itemtype) {
         case 'Software' :
            // Return the infocom linked to the license, not the template linked to the software
            $beforejoin        = ['table'      => 'glpi_softwarelicenses',
                                       'joinparams' => ['jointype' => 'child']];
            $specific_itemtype = 'SoftwareLicense';
            break;

         case 'CartridgeItem' :
            // Return the infocom linked to the license, not the template linked to the software
            $beforejoin        = ['table'      => 'glpi_cartridges',
                                       'joinparams' => ['jointype' => 'child']];
            $specific_itemtype = 'Cartridge';
            break;

         case 'ConsumableItem' :
            // Return the infocom linked to the license, not the template linked to the software
            $beforejoin        = ['table'      => 'glpi_consumables',
                                       'joinparams' => ['jointype' => 'child']];
            $specific_itemtype = 'Consumable';
            break;
      }

      $joinparams        = ['jointype'          => 'itemtype_item',
                                 'specific_itemtype' => $specific_itemtype];
      $complexjoinparams = [];
      if (count($beforejoin)) {
         $complexjoinparams['beforejoin'][] = $beforejoin;
         $joinparams['beforejoin']          = $beforejoin;
      }
      $complexjoinparams['beforejoin'][] = ['table'      => 'glpi_infocoms',
                                                 'joinparams' => $joinparams];

      $tab = [];

      $tab[] = [
         'id'                 => 'financial',
         'name'               => __('Financial and administrative information')
      ];

      $tab[] = [
         'id'                 => '25',
         'table'              => 'glpi_infocoms',
         'field'              => 'immo_number',
         'name'               => __('Immobilization number'),
         'forcegroupby'       => true,
         'joinparams'         => $joinparams,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '26',
         'table'              => 'glpi_infocoms',
         'field'              => 'order_number',
         'name'               => __('Order number'),
         'forcegroupby'       => true,
         'joinparams'         => $joinparams,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '27',
         'table'              => 'glpi_infocoms',
         'field'              => 'delivery_number',
         'name'               => __('Delivery form'),
         'forcegroupby'       => true,
         'joinparams'         => $joinparams,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '28',
         'table'              => 'glpi_infocoms',
         'field'              => 'bill',
         'name'               => __('Invoice number'),
         'forcegroupby'       => true,
         'joinparams'         => $joinparams,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '37',
         'table'              => 'glpi_infocoms',
         'field'              => 'buy_date',
         'name'               => __('Date of purchase'),
         'datatype'           => 'date',
         'forcegroupby'       => true,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '38',
         'table'              => 'glpi_infocoms',
         'field'              => 'use_date',
         'name'               => __('Startup date'),
         'datatype'           => 'date',
         'forcegroupby'       => true,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '142',
         'table'              => 'glpi_infocoms',
         'field'              => 'delivery_date',
         'name'               => __('Delivery date'),
         'datatype'           => 'date',
         'forcegroupby'       => true,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '124',
         'table'              => 'glpi_infocoms',
         'field'              => 'order_date',
         'name'               => __('Order date'),
         'datatype'           => 'date',
         'forcegroupby'       => true,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '123',
         'table'              => 'glpi_infocoms',
         'field'              => 'warranty_date',
         'name'               => __('Start date of warranty'),
         'datatype'           => 'date',
         'forcegroupby'       => true,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '125',
         'table'              => 'glpi_infocoms',
         'field'              => 'inventory_date',
         'name'               => __('Date of last physical inventory'),
         'datatype'           => 'date',
         'forcegroupby'       => true,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '50',
         'table'              => 'glpi_budgets',
         'field'              => 'name',
         'datatype'           => 'dropdown',
         'name'               => __('Budget'),
         'forcegroupby'       => true,
         'joinparams'         => $complexjoinparams
      ];

      $tab[] = [
         'id'                 => '51',
         'table'              => 'glpi_infocoms',
         'field'              => 'warranty_duration',
         'name'               => __('Warranty duration'),
         'forcegroupby'       => true,
         'joinparams'         => $joinparams,
         'datatype'           => 'number',
         'unit'               => 'month',
         'max'                => '120',
         'toadd'              => [
            '-1'                 => __('Lifelong')
         ]
      ];

      $tab[] = [
         'id'                 => '52',
         'table'              => 'glpi_infocoms',
         'field'              => 'warranty_info',
         'name'               => __('Warranty information'),
         'forcegroupby'       => true,
         'joinparams'         => $joinparams,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '120',
         'table'              => 'glpi_infocoms',
         'field'              => 'end_warranty',
         'name'               => __('Warranty expiration date'),
         'datatype'           => 'date_delay',
         'datafields'         => [
            '1'                  => 'warranty_date',
            '2'                  => 'warranty_duration'
         ],
         'searchunit'         => 'MONTH',
         'delayunit'          => 'MONTH',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '53',
         'table'              => 'glpi_suppliers',
         'field'              => 'name',
         'datatype'           => 'dropdown',
         'name'               => __('Supplier'),
         'forcegroupby'       => true,
         'joinparams'         => $complexjoinparams
      ];

      $tab[] = [
         'id'                 => '54',
         'table'              => 'glpi_infocoms',
         'field'              => 'value',
         'name'               => _x('price', 'Value'),
         'datatype'           => 'decimal',
         'width'              => '100',
         'forcegroupby'       => true,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '55',
         'table'              => 'glpi_infocoms',
         'field'              => 'warranty_value',
         'name'               => __('Warranty extension value'),
         'datatype'           => 'decimal',
         'width'              => '100',
         'forcegroupby'       => true,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '56',
         'table'              => 'glpi_infocoms',
         'field'              => 'sink_time',
         'name'               => __('Amortization duration'),
         'forcegroupby'       => true,
         'joinparams'         => $joinparams,
         'datatype'           => 'number',
         'max'                => '15',
         'unit'               => 'year'
      ];

      $tab[] = [
         'id'                 => '57',
         'table'              => 'glpi_infocoms',
         'field'              => 'sink_type',
         'name'               => __('Amortization type'),
         'forcegroupby'       => true,
         'joinparams'         => $joinparams,
         'datatype'           => 'specific',
         'searchequalsonfield' => 'specific',
         'searchtype'         => ['equals', 'notequals']
      ];

      $tab[] = [
         'id'                 => '58',
         'table'              => 'glpi_infocoms',
         'field'              => 'sink_coeff',
         'name'               => __('Amortization coefficient'),
         'forcegroupby'       => true,
         'joinparams'         => $joinparams,
         'datatype'           => 'decimal'
      ];

      $tab[] = [
         'id'                 => '59',
         'table'              => 'glpi_infocoms',
         'field'              => 'alert',
         'name'               => __('Email alarms'),
         'forcegroupby'       => true,
         'joinparams'         => $joinparams,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '122',
         'table'              => 'glpi_infocoms',
         'field'              => 'comment',
         'name'               => __('Comments on financial and administrative information'),
         'datatype'           => 'text',
         'forcegroupby'       => true,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '173',
         'table'              => 'glpi_businesscriticities',
         'field'              => 'completename',
         'name'               => __('Business criticity'),
         'datatype'           => 'dropdown',
         'forcegroupby'       => true,
         'joinparams'         => $complexjoinparams
      ];

      $tab[] = [
         'id'                 => '159',
         'table'              => 'glpi_infocoms',
         'field'              => 'decommission_date',
         'name'               => __('Decommission date'),
         'datatype'           => 'date',
         'forcegroupby'       => true,
         'joinparams'         => $joinparams
      ];

      return $tab;
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
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'buy_date',
         'name'               => __('Date of purchase'),
         'datatype'           => 'date'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'use_date',
         'name'               => __('Startup date'),
         'datatype'           => 'date'
      ];

      $tab[] = [
         'id'                 => '24',
         'table'              => $this->getTable(),
         'field'              => 'delivery_date',
         'name'               => __('Delivery date'),
         'datatype'           => 'date',
         'forcegroupby'       => true
      ];

      $tab[] = [
         'id'                 => '23',
         'table'              => $this->getTable(),
         'field'              => 'order_date',
         'name'               => __('Order date'),
         'datatype'           => 'date',
         'forcegroupby'       => true
      ];

      $tab[] = [
         'id'                 => '25',
         'table'              => $this->getTable(),
         'field'              => 'warranty_date',
         'name'               => __('Start date of warranty'),
         'datatype'           => 'date',
         'forcegroupby'       => true
      ];

      $tab[] = [
         'id'                 => '27',
         'table'              => $this->getTable(),
         'field'              => 'inventory_date',
         'name'               => __('Date of last physical inventory'),
         'datatype'           => 'date',
         'forcegroupby'       => true
      ];

      $tab[] = [
         'id'                 => '28',
         'table'              => $this->getTable(),
         'field'              => 'decommission_date',
         'name'               => __('Decommission date'),
         'datatype'           => 'date',
         'forcegroupby'       => true
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'warranty_duration',
         'name'               => __('Warranty duration'),
         'datatype'           => 'number',
         'unit'               => 'month',
         'max'                => '120',
         'toadd'              => [
            '-1'                 => __('Lifelong')
         ]
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'warranty_info',
         'name'               => __('Warranty information'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'warranty_value',
         'name'               => __('Warranty extension value'),
         'datatype'           => 'decimal'
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => 'glpi_suppliers',
         'field'              => 'name',
         'name'               => __('Supplier'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'order_number',
         'name'               => __('Order number'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'delivery_number',
         'name'               => __('Delivery form'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'immo_number',
         'name'               => __('Immobilization number'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => $this->getTable(),
         'field'              => 'value',
         'name'               => _x('price', 'Value'),
         'datatype'           => 'decimal'
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => $this->getTable(),
         'field'              => 'sink_time',
         'name'               => __('Amortization duration'),
         'datatype'           => 'number',
         'max'                => '15',
         'unit'               => 'year'
      ];

      $tab[] = [
         'id'                 => '15',
         'table'              => $this->getTable(),
         'field'              => 'sink_type',
         'name'               => __('Amortization type'),
         'datatype'           => 'specific',
         'searchtype'         => ['equals', 'notequals']
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '17',
         'table'              => $this->getTable(),
         'field'              => 'sink_coeff',
         'name'               => __('Amortization coefficient'),
         'datatype'           => 'decimal'
      ];

      $tab[] = [
         'id'                 => '18',
         'table'              => $this->getTable(),
         'field'              => 'bill',
         'name'               => __('Invoice number'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => 'glpi_budgets',
         'field'              => 'name',
         'name'               => __('Budget'),
         'datatype'           => 'itemlink'
      ];

      $tab[] = [
         'id'                 => '20',
         'table'              => $this->getTable(),
         'field'              => 'itemtype',
         'name'               => __('Type'),
         'datatype'           => 'itemtype',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '21',
         'table'              => $this->getTable(),
         'field'              => 'items_id',
         'name'               => __('ID'),
         'datatype'           => 'integer',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '22',
         'table'              => $this->getTable(),
         'field'              => 'alert',
         'name'               => __('Alarms on financial and administrative information'),
         'datatype'           => 'integer'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool'
      ];

      return $tab;
   }


   /**
    * Display debug information for infocom of current object
   **/
   function showDebug() {

      $item = ['item_name'          => '',
                    'warrantyexpiration' => '',
                    'itemtype'           => $this->fields['itemtype'],
                    'items_id'           => $this->fields['items_id']];

      $options['entities_id'] = $this->getEntityID();
      $options['items']       = [$item];
      NotificationEvent::debugEvent($this, $options);
   }


   /**
    * Duplicate infocoms from an item template to its clone
    *
    * @param $itemtype     itemtype of the item
    * @param $oldid        ID of the item to clone
    * @param $newid        ID of the item cloned
    * @param $newitemtype  itemtype of the new item (= $itemtype if empty) (default '')
   **/
   static function cloneItem($itemtype, $oldid, $newid, $newitemtype = '') {
      global $DB;

      $ic = new self();
      if ($ic->getFromDBforDevice($itemtype, $oldid)) {
         $input             = $ic->fields;
         $input             = Toolbox::addslashes_deep($input);
         $input['items_id'] = $newid;
         if (!empty($newitemtype)) {
            $input['itemtype'] = $newitemtype;
         }
         unset ($input["id"]);
         if (isset($input["immo_number"])) {
            $input["immo_number"] = autoName($input["immo_number"], "immo_number", 1, 'Infocom',
                                             $input['entities_id']);
         }
         $date_fields = [
            'buy_date',
            'delivery_date',
            'inventory_date',
            'order_date',
            'use_date',
            'warranty_date',
         ];
         foreach ($date_fields as $f) {
            if (empty($input[$f])) {
               unset($input[$f]);
            }
         }
         unset($input['date_creation']);
         unset($input['date_mod']);
         $ic2 = new self();
         $ic2->add($input);
      }
   }


   /**
    * Get date using a begin date and a period in month
    *
    * @param $from            date     begin date
    * @param $addwarranty     integer  period in months
    * @param $deletenotice    integer  period in months of notice (default 0)
    * @param $color           boolean  if show expire date in red color (false by default)
    *
    * @return expiration date string
   **/
   static function getWarrantyExpir($from, $addwarranty, $deletenotice = 0, $color = false) {

      // Life warranty
      if (($addwarranty == -1)
          && ($deletenotice == 0)) {
         return __('Never');
      }

      if (($from == null) || empty($from)) {
         return "";
      }

      $datetime = strtotime("$from+$addwarranty month -$deletenotice month");
      if ($color && ($datetime < time())) {
         return "<span class='red'>".Html::convDate(date("Y-m-d", $datetime))."</span>";
      }
      return Html::convDate(date("Y-m-d", $datetime));
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::getMassiveActionsForItemtype()
   **/
   static function getMassiveActionsForItemtype(array &$actions, $itemtype, $is_deleted = 0,
                                                CommonDBTM $checkitem = null) {

      $action_name = __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'activate';

      if (Infocom::canApplyOn($itemtype)
          && static::canCreate()) {
         $actions[$action_name] = __('Enable the financial and administrative information');
      }
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'activate' :
            $ic = new self();
            if ($ic->canCreate()) {
               $itemtype = $item->getType();
               foreach ($ids as  $key) {
                  if (!$ic->getFromDBforDevice($itemtype, $key)) {
                     $input = ['itemtype' => $itemtype,
                                    'items_id' => $key];
                     if ($ic->can(-1, CREATE, $input)) {
                        if ($ic->add($input)) {
                           $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                        } else {
                           $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                           $ma->addMessage($ic->getErrorMessage(ERROR_ON_ACTION));
                        }
                     } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($ic->getErrorMessage(ERROR_RIGHT));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                     $ma->addMessage($ic->getErrorMessage(ERROR_NOT_FOUND));
                  }
               }
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   /**
    * @since 9.1.7
    * @see CommonDBChild::canUpdateItem()
   **/
   function canUpdateItem() {
      return Session::haveRight(static::$rightname, UPDATE);
   }


   /**
    * @since 9.1.7
    * @see CommonDBChild::canPurgeItem()
   **/
   function canPurgeItem() {
      return Session::haveRight(static::$rightname, PURGE);
   }


   /**
    * @since 9.1.7
    * @see CommonDBChild::canCreateItem()
   **/
   function canCreateItem() {
      return Session::haveRight(static::$rightname, CREATE);
   }

   /**
    * Get item types
    *
    * @since 9.3.1
    *
    * @param array $where Where clause
    *
    * @return DBMysqlIterator
    */
   public static function getTypes($where) {
      global $DB;

      $types_iterator = $DB->request([
         'SELECT DISTINCT' => 'itemtype',
         'FROM'            => 'glpi_infocoms',
         'WHERE'           => [
            'NOT'          => ['itemtype' => self::getExcludedTypes()]
         ] + $where,
         'ORDER'           => 'itemtype'
      ]);
      return $types_iterator;
   }


   /**
    * Get excluded itemtypes
    *
    * @since 9.3.1
    *
    * @return array
    */
   public static function getExcludedTypes() {
      return ['ConsumableItem', 'CartridgeItem', 'Software'];
   }
}
