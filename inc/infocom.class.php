<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
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
    * Check if given object can have InfoCom
    *
    * @since version 0.85
    *
    * @param $item  an object or a string
    *
    * @return true if $object is an object that can have InfoCom
    *
   **/
   static function canApplyOn($item) {
      global $CFG_GLPI;

      // All devices are subjects to infocom !
      if (Toolbox::is_a($item, 'Item_Devices')) {
         return true;
      }

      // We also allow direct items to check
      if ($item instanceof CommonGLPI) {
         $item = $item->getType();
      }

      if (in_array($item, $CFG_GLPI['infocom_types'])){
         return true;
      }

      return false;
   }


   /**
    * Get all the types that can have an infocom
    *
    * @since version 0.85
    *
    * @return array of the itemtypes
   **/
   static function getItemtypesThatCanHave() {
      global $CFG_GLPI;

      return array_merge($CFG_GLPI['infocom_types'],
                         Item_Devices::getDeviceTypes());
   }


   static function getTypeName($nb=0) {
      //TRANS: Always plural
      return __('Financial and administrative information');
   }


    function post_getEmpty() {

      $this->fields["alert"] = Entity::getUsedConfig("use_infocoms_alert",
                                                     $this->fields["entities_id"],
                                                     "default_infocom_alert", 0);
   }


   function getLogTypeID() {
      return array($this->fields['itemtype'], $this->fields['items_id']);
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

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
                                             "`itemtype` = '".$item->getType()."'
                                               AND `items_id` = '".$item->getID()."'");
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
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

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

      $restrict = "`glpi_infocoms`.`suppliers_id` = '".$item->getField('id') ."'
                    AND `itemtype` NOT IN ('ConsumableItem', 'CartridgeItem', 'Software') ".
                    getEntitiesRestrictRequest(" AND ", "glpi_infocoms", '',
                                               $_SESSION['glpiactiveentities']);

      return countElementsInTable(array('glpi_infocoms'), $restrict);
   }


   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
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
    * @since version 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
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

      if ($this->getFromDBByQuery("WHERE `".$this->getTable()."`.`items_id` = '$ID'
                                  AND `".$this->getTable()."`.`itemtype` = '$itemtype'")) {
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
      if (!$this->getFromDBforDevice($input['itemtype'],$input['items_id'])) {
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
   static function manageDateOnStatusChange(CommonDBTM $item, $action_add=true) {
      global $CFG_GLPI;

      $itemtype = get_class($item);
      $changes  = $item->fields;

      //Autofill date on item's status change ?
      $infocom = new self();
      $infocom->getFromDB($changes['id']);
      $tmp           = array('itemtype' => $itemtype,
                             'items_id' => $changes['id']);
      $add_or_update = false;

      //For each date that can be automatically filled
      foreach (self::getAutoManagemendDatesFields() as $date => $date_field) {
         $resp   = array();
         $result = Entity::getUsedConfig($date, $changes['entities_id']);

         //Date must be filled if status corresponds to the one defined in the config
         if (preg_match('/'.self::ON_STATUS_CHANGE.'_(.*)/',$result,$values)
             && ($values[1] == $changes['states_id'])) {
            $add_or_update    = true;
            $tmp[$date_field] = $_SESSION["glpi_currenttime"];
         }
      }

      //One date or more has changed
      if ($add_or_update) {

         if (!$infocom->getFromDBforDevice($itemtype,$changes['id'])) {
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
   static function autofillDates(&$infocoms=array(), $field='', $action=0, $params=array()) {

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

      return array('autofill_buy_date'         => 'buy_date',
                   'autofill_use_date'         => 'use_date',
                   'autofill_delivery_date'    => 'delivery_date',
                   'autofill_warranty_date'    => 'warranty_date',
                   'autofill_order_date'       => 'order_date',
                   'autofill_decommission_date' => 'decommission_date');
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
    * @since version 0.84
   **/
   function cleanDBonPurge() {

      $class = new Alert();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }


   /**
    * @param $name
   **/
   static function cronInfo($name) {
      return array('description' => __('Send alarms on financial and administrative information'));
   }


   /**
    * Cron action on infocom : alert on expired warranty
    *
    * @param $task to log, if NULL use display (default NULL)
    *
    * @return 0 : nothing to do 1 : done with success
   **/
   static function cronInfocom($task=NULL) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["use_mailing"]) {
         return 0;
      }

      $message        = array();
      $cron_status    = 0;
      $items_infos    = array();
      $items_messages = array();

      foreach (Entity::getEntitiesToNotify('use_infocoms_alert') as $entity => $value) {
         $before    = Entity::getUsedConfig('send_infocoms_alert_before_delay', $entity);
         $query_end = "SELECT `glpi_infocoms`.*
                       FROM `glpi_infocoms`
                       LEFT JOIN `glpi_alerts` ON (`glpi_infocoms`.`id` = `glpi_alerts`.`items_id`
                                                   AND `glpi_alerts`.`itemtype` = 'Infocom'
                                                   AND `glpi_alerts`.`type`='".Alert::END."')
                       WHERE (`glpi_infocoms`.`alert` & ".pow(2,Alert::END).") >'0'
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
         if (NotificationEvent::raiseEvent("alert", new self(), array('entities_id' => $entity,
                                                                      'items'       => $items))) {
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
    * @since version 0.84 (before in alert.class)
    *
    * @param $val if not set, ask for all values, else for 1 value (default NULL)
    *
    * @return array or string
   **/
   static function getAlertName($val=NULL) {

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

      $tab = array();
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
   static function dropdownAmortType($name, $value=0, $display=true) {

      $values = array(2 => __('Linear'),
                      1 => __('Decreasing'));

      return Dropdown::showFromArray($name, $values,
                                     array('value'               => $value,
                                           'display'             => $display,
                                           'display_emptychoice' => true));
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
   static function showTco($ticket_tco, $value, $date_achat="") {
      if ($ticket_tco == NOT_AVAILABLE) {
         return '-';
      }

      // Affiche le TCO ou le TCO mensuel pour un matériel
      $totalcost = $ticket_tco;

      if ($date_achat) { // on veut donc le TCO mensuel
         // just to avoid IDE warning
         $date_Y = $date_m = $date_d = 0;

         sscanf($date_achat, "%4s-%2s-%2s", $date_Y, $date_m, $date_d);

         $timestamp2 = mktime(0,0,0, $date_m, $date_d, $date_Y);
         $timestamp  = mktime(0,0,0, date("m"), date("d"), date("Y"));

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
      if ($DB->result($result,0,0) > 0) {
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
                                       $CFG_GLPI["root_doc"]."/front/infocom.form.php".
                                          "?itemtype=$itemtype&items_id=$device_id",
                                       array('height' => 600));
      }
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
                         $view="n") {
      // By Jean-Mathieu Doleans qui s'est un peu pris le chou :p

      // Attention date mise en service/dateachat ->amort lineaire  et $prorata en jour !!
      // amort degressif au prorata du nombre de mois.
      // Son point de depart est le 1er jour du mois d'acquisition et non date de mise en service

      if ($type_amort == "2") {
         if (!empty($date_use)) {
            $date_achat = $date_use;
         }
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
         case "2" :
            ########################### Calcul amortissement lineaire ###########################
            if (($va > 0)
                && ($duree > 0)
                && !empty($date_achat)) {
               ## calcul du prorata temporis en jour ##
               $ecartfinmoiscourant = (30-$date_d); // calcul ecart entre jour date acquis
                                                    // ou mise en service et fin du mois courant
               // en lineaire on calcule en jour
               if ($date_d2 < 30) {
                  $ecartmoisexercice = (30-$date_d2);
               }
               if ($date_m > $date_m2) {
                  $date_m2 = $date_m2+12;
               } // si l'annee fiscale debute au dela de l'annee courante
               $ecartmois  = (($date_m2-$date_m)*30); // calcul ecart entre mois d'acquisition
                                                      // et debut annee fiscale
               $prorata    = $ecartfinmoiscourant+$ecartmois-$ecartmoisexercice;
               ## calcul tableau d'amortissement ##
               $txlineaire = (100/$duree); // calcul du taux lineaire
               $annuite    = ($va*$txlineaire)/100; // calcul de l'annuitee
               $mrt        = $va; //
               // si prorata temporis la derniere annnuite cours sur la duree n+1
               if ($prorata > 0) {
                  $duree = $duree+1;
               }
               for($i=1 ; $i<=$duree ; $i++) {
                  $tab['annee'][$i]    = $date_Y+$i-1;
                  $tab['annuite'][$i]  = $annuite;
                  $tab['vcnetdeb'][$i] = $mrt; // Pour chaque annee on calcul la valeur comptable nette
                                               // de debut d'exercice
                  $tab['vcnetfin'][$i] = abs(($mrt - $annuite)); // Pour chaque annee on calcule la valeur
                                                               // comptable nette de fin d'exercice
                  // calcul de la premiere annuite si prorata temporis
                  if ($prorata  >0) {
                     $tab['annuite'][1]  = $annuite*($prorata/360);
                     $tab['vcnetfin'][1] = abs($va - $tab['annuite'][1]);
                  }
                  $mrt = $tab['vcnetfin'][$i];
               }
               // calcul de la derniere annuite si prorata temporis
               if ($prorata > 0) {
                  $tab['annuite'][$duree]  = $tab['vcnetdeb'][$duree];
                  $tab['vcnetfin'][$duree] = $tab['vcnetfin'][$duree-1] - $tab['annuite'][$duree];
               }
            } else {
               return "-";
            }
            break;

         case "1" :
            ########################### Calcul amortissement degressif ###########################
            if (($va > 0)
                && ($duree > 0)
                && ($coef > 1)
                && !empty($date_achat)) {
               ## calcul du prorata temporis en mois ##
               // si l'annee fiscale debute au dela de l'annee courante
               if ($date_m > $date_m2) {
                  $date_m2 = $date_m2+12;
               }
               $ecartmois      = ($date_m2-$date_m)+1; // calcul ecart entre mois d'acquisition
                                                       // et debut annee fiscale
               $prorata        = $ecartfinmoiscourant+$ecartmois-$ecartmoisexercice;
               ## calcul tableau d'amortissement ##
               $txlineaire     = (100/$duree); // calcul du taux lineaire virtuel
               $txdegressif    = $txlineaire*$coef; // calcul du taux degressif
               $dureelineaire  = (int) (100/$txdegressif); // calcul de la duree de l'amortissement
                                                           // en mode lineaire
               $dureedegressif = $duree-$dureelineaire; // calcul de la duree de l'amortissement
                                                        // en mode degressif
               $mrt            = $va;
               // amortissement degressif pour les premieres annees
               for($i=1 ; $i<=$dureedegressif ; $i++) {
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
               for($i=$dureedegressif+1 ; $i<=$dureedegressif+$dureelineaire ; $i++) {
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
      if (!array_search(date("Y"),$tab["annee"])) {
         $vnc = 0;
      } else if (mktime(0 , 0 , 0, $date_m2, $date_d2, date("Y"))
                 - mktime(0 , 0 , 0 , date("m") , date("d") , date("Y")) < 0 ) {
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
    * @param $withtemplate integer  template or basic item (default '')
   **/
   static function showForItem(CommonDBTM $item, $withtemplate='') {
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

         if (!strpos($_SERVER['PHP_SELF'],"infocoms-show")
             && in_array($item->getType(), array('CartridgeItem', 'ConsumableItem', 'Software'))) {
            echo "<div class='firstbloc center'>".
                  __('For this type of item, the financial and administrative information are only a model for the items which you should add.').
                 "</div>";
         }
         if (!$ic->getFromDBforDevice($item->getType(),$dev_ID)) {
            $input = array('itemtype'    => $item->getType(),
                           'items_id'    => $dev_ID,
                           'entities_id' => $item->getEntityID());

            if ($ic->can(-1, CREATE, $input)
                && ($withtemplate != 2)) {
               echo "<div class='spaced b'>";
               echo "<table class='tab_cadre_fixe'><tr class='tab_bg_1'><th>";
               echo sprintf(__('%1$s - %2$s'), $item->getTypeName(1), $item->getName())."</th></tr>";
               echo "<tr class='tab_bg_1'><td class='center'>";

               Html::showSimpleForm($CFG_GLPI["root_doc"]."/front/infocom.form.php",
                                    'add', __('Enable the financial and administrative information'),
                                     array('itemtype' => $item->getType(),
                                           'items_id' => $dev_ID));
               echo "</td></tr></table></div>";
            }

         } else { // getFromDBforDevice
            $canedit = ($ic->canEdit($ic->fields['id']) && ($withtemplate != 2));
            echo "<div class='spaced'>";
            if ($canedit) {
               echo "<form name='form_ic' method='post' action='".$CFG_GLPI["root_doc"].
                     "/front/infocom.form.php'>";
            }
            echo "<table class='tab_cadre".(!strpos($_SERVER['PHP_SELF'],
                                                    "infocoms-show")?"_fixe":"")."'>";

            // Can edit calendar ?
           $editcalendar = ($withtemplate != 2);

            echo "<tr><th colspan='4'>".__('Asset lifecycle')."</th></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Order date')."</td><td>";
            Html::showDateField("order_date", array('value'      => $ic->fields["order_date"],
                                                    'maybeempty' => true,
                                                    'canedit'    => $editcalendar));
            echo "</td>";
            echo "<td>".__('Date of purchase')."</td><td>";
            Html::showDateField("buy_date", array('value'      => $ic->fields["buy_date"],
                                                  'maybeempty' => true,
                                                  'canedit'    => $editcalendar));
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Delivery date')."</td><td>";
            Html::showDateField("delivery_date", array('value'      => $ic->fields["delivery_date"],
                                                       'maybeempty' => true,
                                                       'canedit'    => $editcalendar));
            echo "</td>";
            echo "<td>".__('Startup date')."</td><td>";
            Html::showDateField("use_date", array('value'      => $ic->fields["use_date"],
                                                  'maybeempty' => true,
                                                  'canedit'    => $editcalendar));
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Date of last physical inventory')."</td><td>";
            Html::showDateField("inventory_date",
                                array('value'      => $ic->fields["inventory_date"],
                                      'maybeempty' => true,
                                      'canedit'    => $editcalendar));
            echo "</td>";
            echo "<td>".__('Decommission date')."</td><td>";
            Html::showDateField("decommission_date",
                                array('value'      => $ic->fields["decommission_date"],
                                      'maybeempty' => true,
                                      'canedit'    => $editcalendar));
            echo "</td></tr>";

            echo "<tr><th colspan='4'>".__('Financial and administrative information')."</th></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Supplier')."</td>";
            echo "<td>";
            if ($withtemplate == 2) {
               echo Dropdown::getDropdownName("glpi_suppliers", $ic->fields["suppliers_id"]);
            } else {
               Supplier::dropdown(array('value'  => $ic->fields["suppliers_id"],
                                        'entity' => $item->getEntityID(),
                                        'width'  => '70%'));
            }
            echo "</td>";
            if (Budget::canView()) {
               echo "<td>".__('Budget')."</td><td >";
               Budget::dropdown(array('value'    => $ic->fields["budgets_id"],
                                      'entity'   => $item->getEntityID(),
                                      'comments' => 1));
            } else {
               echo "<td colspan='2'>";
            }
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Order number')."</td>";
            echo "<td >";
            Html::autocompletionTextField($ic, "order_number", array('option' => $option));
            echo "</td>";
            $istemplate = '';
            if ($item->isTemplate()
                || in_array($item->getType(),
                            array('CartridgeItem', 'ConsumableItem', 'Software'))) {
               $istemplate = '*';
            }
            echo "<td>".sprintf(__('%1$s%2$s'), __('Immobilization number'), $istemplate)."</td>";
            echo "<td>";
            $objectName = autoName($ic->fields["immo_number"], "immo_number", ($withtemplate == 2),
                                   'Infocom', $item->getEntityID());
            Html::autocompletionTextField($ic, "immo_number", array('value'  => $objectName,
                                                                    'option' => $option));
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Invoice number')."</td>";
            echo "<td>";
            Html::autocompletionTextField($ic, "bill", array('option' => $option));
            echo "</td>";
            echo "<td>".__('Delivery form')."</td><td>";
            Html::autocompletionTextField($ic, "delivery_number", array('option' => $option));
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
                                                $ic->fields["warranty_date"],
                                                $ic->fields["use_date"], $date_tax,"n"));
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
               Dropdown::showNumber("sink_time", array('value' => $ic->fields["sink_time"],
                                                       'max'   => 15,
                                                       'unit'  => 'year'));
            }
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Amortization coefficient')."</td>";
            echo "<td>";
            Html::autocompletionTextField($ic, "sink_coeff", array('size'   => 14,
                                                                   'option' => $option));
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            if (!in_array($item->getType(), array('Cartridge', 'CartridgeItem', 'Consumable',
                                                  'ConsumableItem', 'Software',
                                                  'SoftwareLicense'))) {
               echo "<td>".__('TCO (value + tracking cost)')."</td><td>";
               echo self::showTco($item->getField('ticket_tco'), $ic->fields["value"]);
            } else {
                echo "<td colspan='2'>";
            }
            echo "</td>";
            if (!in_array($item->getType(), array('Cartridge', 'CartridgeItem', 'Consumable',
                                                  'ConsumableItem', 'Software',
                                                  'SoftwareLicense'))) {
               echo "<td>".__('Monthly TCO')."</td><td>";
               echo self::showTco($item->getField('ticket_tco'), $ic->fields["value"],
                                  $ic->fields["buy_date"]);
            } else {
                echo "<td colspan='2'>";
            }
            echo "</td></tr>";

            echo "<tr><th colspan='4'>".__('Warranty information')."</th></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".__('Start date of warranty')."</td><td>";
            Html::showDateField("warranty_date", array('value'      => $ic->fields["warranty_date"],
                                                       'maybeempty' => true,
                                                       'canedit'    => $editcalendar));
            echo "</td>";

            echo "<td>".__('Warranty duration')."</td><td>";
            if ($withtemplate == 2) {
               // -1 = life
               if ($ic->fields["warranty_duration"] == -1) {
                  _e('Lifelong');
               } else {
                  printf(_n('%d month', '%d months', $ic->fields["warranty_duration"]),
                         $ic->fields["warranty_duration"]);
               }

            } else {
               Dropdown::showNumber("warranty_duration",
                                    array('value' => $ic->fields["warranty_duration"],
                                          'min'   => 0,
                                          'max'   => 120,
                                          'step'  => 1,
                                          'toadd' => array(-1 => __('Lifelong')),
                                          'unit'  => 'month'));
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
            Html::autocompletionTextField($ic, "warranty_info", array('option' => $option));
            echo "</td>";

            if ($CFG_GLPI['use_mailing']) {
               echo "<td>".__('Alarms on financial and administrative information')."</td>";
               echo "<td>";
               self::dropdownAlert(array('name'    => "alert",
                                         'value'   => $ic->fields["alert"]));
               Alert::displayLastAlert('Infocom', $ic->fields['id']);
            } else {
               echo "</td><td colspan='2'>";
            }
            echo "</td></tr>";

            //We use a static method to call the hook
            //It's then easier for plugins to detect if the hook is available or not
            //The just have to look for the addPluginInfos method
            self::addPluginInfos($item);

            if ($canedit) {
               echo "<tr>";
               echo "<td class='tab_bg_2 center' colspan='2'>";
               echo "<input type='hidden' name='id' value='".$ic->fields['id']."'>";
               echo "<input type='submit' name='update' value=\""._sx('button','Save')."\"
                      class='submit'>";
               echo "</td>";
               echo "<td class='tab_bg_2 center' colspan='2'>";
               echo "<input type='submit' name='purge' value=\""._sx('button',
                                                                      'Delete permanently')."\"
                      class='submit'>";
               echo "</td></tr>";
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
   static function getSearchOptionsToAdd($itemtype) {

//                if ($itemtype == 'CartridgeItem') {
//                   // Return the infocom linked to the Cartridge, not the template linked to the Type
//                   $out = Search::addLeftJoin($itemtype, $rt, $already_link_tables, "glpi_cartridges",
//                                              $linkfield);
//                   $specific_leftjoin =  $out."
//                         LEFT JOIN `$new_table` $AS ON (`glpi_cartridges`.`id` = `$nt`.`items_id`
//                                                       AND `$nt`.`itemtype` = 'Cartridge') ";
//                }
//                if ($itemtype == 'ConsumableItem') {
//                   // Return the infocom linked to the Comsumable, not the template linked to the Type
//                   $out = Search::addLeftJoin($itemtype, $rt, $already_link_tables, "glpi_consumables",
//                                              $linkfield);
//                   $specific_leftjoin =  $out."
//                         LEFT JOIN `$new_table` $AS ON (glpi_consumables.`id` = `$nt`.`items_id`
//                                                       AND `$nt`.`itemtype` = 'Consumable') ";
//                }
      $specific_itemtype = '';
      $beforejoin        = array();
      switch ($itemtype) {
         case 'Software' :
            // Return the infocom linked to the license, not the template linked to the software
            $beforejoin        = array('table'      => 'glpi_softwarelicenses',
                                       'joinparams' => array('jointype' => 'child'));
            $specific_itemtype = 'SoftwareLicense';
            break;

         case 'CartridgeItem' :
            // Return the infocom linked to the license, not the template linked to the software
            $beforejoin        = array('table'      => 'glpi_cartridges',
                                       'joinparams' => array('jointype' => 'child'));
            $specific_itemtype = 'Cartridge';
            break;

         case 'ConsumableItem' :
            // Return the infocom linked to the license, not the template linked to the software
            $beforejoin        = array('table'      => 'glpi_consumables',
                                       'joinparams' => array('jointype' => 'child'));
            $specific_itemtype = 'Consumable';
            break;
      }

      $joinparams        = array('jointype'          => 'itemtype_item',
                                 'specific_itemtype' => $specific_itemtype);
      $complexjoinparams = array();
      if (count($beforejoin)) {
         $complexjoinparams['beforejoin'][] = $beforejoin;
         $joinparams['beforejoin']          = $beforejoin;
      }
      $complexjoinparams['beforejoin'][] = array('table'      => 'glpi_infocoms',
                                                 'joinparams' => $joinparams);

      $tab = array();

      $tab['financial']                = __('Financial and administrative information');

      $tab[25]['table']                = 'glpi_infocoms';
      $tab[25]['field']                = 'immo_number';
      $tab[25]['name']                 = __('Immobilization number');
      $tab[25]['forcegroupby']         = true;
      $tab[25]['joinparams']           = $joinparams;
      $tab[25]['datatype']             = 'string';

      $tab[26]['table']                = 'glpi_infocoms';
      $tab[26]['field']                = 'order_number';
      $tab[26]['name']                 = __('Order number');
      $tab[26]['forcegroupby']         = true;
      $tab[26]['joinparams']           = $joinparams;
      $tab[26]['datatype']             = 'string';

      $tab[27]['table']                = 'glpi_infocoms';
      $tab[27]['field']                = 'delivery_number';
      $tab[27]['name']                 = __('Delivery form');
      $tab[27]['forcegroupby']         = true;
      $tab[27]['joinparams']           = $joinparams;
      $tab[27]['datatype']             = 'string';

      $tab[28]['table']                = 'glpi_infocoms';
      $tab[28]['field']                = 'bill';
      $tab[28]['name']                 = __('Invoice number');
      $tab[28]['forcegroupby']         = true;
      $tab[28]['joinparams']           = $joinparams;
      $tab[28]['datatype']             = 'string';

      $tab[37]['table']                = 'glpi_infocoms';
      $tab[37]['field']                = 'buy_date';
      $tab[37]['name']                 = __('Date of purchase');
      $tab[37]['datatype']             = 'date';
      $tab[37]['forcegroupby']         = true;
      $tab[37]['joinparams']           = $joinparams;

      $tab[38]['table']                = 'glpi_infocoms';
      $tab[38]['field']                = 'use_date';
      $tab[38]['name']                 = __('Startup date');
      $tab[38]['datatype']             = 'date';
      $tab[38]['forcegroupby']         = true;
      $tab[38]['joinparams']           = $joinparams;

      $tab[142]['table']               = 'glpi_infocoms';
      $tab[142]['field']               = 'delivery_date';
      $tab[142]['name']                = __('Delivery date');
      $tab[142]['datatype']            = 'date';
      $tab[142]['forcegroupby']        = true;
      $tab[142]['joinparams']          = $joinparams;

      $tab[124]['table']               = 'glpi_infocoms';
      $tab[124]['field']               = 'order_date';
      $tab[124]['name']                = __('Order date');
      $tab[124]['datatype']            = 'date';
      $tab[124]['forcegroupby']        = true;
      $tab[124]['joinparams']          = $joinparams;

      $tab[123]['table']               = 'glpi_infocoms';
      $tab[123]['field']               = 'warranty_date';
      $tab[123]['name']                = __('Start date of warranty');
      $tab[123]['datatype']            = 'date';
      $tab[123]['forcegroupby']        = true;
      $tab[123]['joinparams']          = $joinparams;

      $tab[125]['table']               = 'glpi_infocoms';
      $tab[125]['field']               = 'inventory_date';
      $tab[125]['name']                = __('Date of last physical inventory');
      $tab[125]['datatype']            = 'date';
      $tab[125]['forcegroupby']        = true;
      $tab[125]['joinparams']          = $joinparams;

      $tab[50]['table']                = 'glpi_budgets';
      $tab[50]['field']                = 'name';
      $tab[50]['datatype']             = 'dropdown';
      $tab[50]['name']                 = __('Budget');
      $tab[50]['forcegroupby']         = true;
      $tab[50]['joinparams']           = $complexjoinparams;

      $tab[51]['table']                = 'glpi_infocoms';
      $tab[51]['field']                = 'warranty_duration';
      $tab[51]['name']                 = __('Warranty duration');
      $tab[51]['forcegroupby']         = true;
      $tab[51]['joinparams']           = $joinparams;
      $tab[51]['datatype']             = 'number';
      $tab[51]['unit']                 = 'month';
      $tab[51]['max']                  = 120;
      $tab[51]['toadd']                = array(-1 => __('Lifelong'));

      $tab[52]['table']                = 'glpi_infocoms';
      $tab[52]['field']                = 'warranty_info';
      $tab[52]['name']                 = __('Warranty information');
      $tab[52]['forcegroupby']         = true;
      $tab[52]['joinparams']           = $joinparams;
      $tab[52]['datatype']             = 'string';

      $tab[120]['table']               = 'glpi_infocoms';
      $tab[120]['field']               = 'end_warranty';
      $tab[120]['name']                = __('Warranty expiration date');
      $tab[120]['datatype']            = 'date_delay';
      $tab[120]['datafields'][1]       = 'warranty_date';
      $tab[120]['datafields'][2]       = 'warranty_duration';
      $tab[120]['searchunit']          = 'MONTH';
      $tab[120]['delayunit']           = 'MONTH';
      $tab[120]['forcegroupby']        = true;
      $tab[120]['massiveaction']       = false;
      $tab[120]['joinparams']          = $joinparams;

      $tab[53]['table']                = 'glpi_suppliers';
      $tab[53]['field']                = 'name';
      $tab[53]['datatype']             = 'dropdown';
      $tab[53]['name']                 = __('Supplier');
      $tab[53]['forcegroupby']         = true;
      $tab[53]['joinparams']           = $complexjoinparams;

      $tab[54]['table']                = 'glpi_infocoms';
      $tab[54]['field']                = 'value';
      $tab[54]['name']                 = _x('price', 'Value');
      $tab[54]['datatype']             = 'decimal';
      $tab[54]['width']                = 100;
      $tab[54]['forcegroupby']         = true;
      $tab[54]['joinparams']           = $joinparams;

      $tab[55]['table']                = 'glpi_infocoms';
      $tab[55]['field']                = 'warranty_value';
      $tab[55]['name']                 = __('Warranty extension value');
      $tab[55]['datatype']             = 'decimal';
      $tab[55]['width']                = 100;
      $tab[55]['forcegroupby']         = true;
      $tab[55]['joinparams']           = $joinparams;

      $tab[56]['table']                = 'glpi_infocoms';
      $tab[56]['field']                = 'sink_time';
      $tab[56]['name']                 = __('Amortization duration');
      $tab[56]['forcegroupby']         = true;
      $tab[56]['joinparams']           = $joinparams;
      $tab[56]['datatype']             = 'number';
      $tab[56]['max']                  = 15;
      $tab[56]['unit']                 = 'year';

      $tab[57]['table']                = 'glpi_infocoms';
      $tab[57]['field']                = 'sink_type';
      $tab[57]['name']                 = __('Amortization type');
      $tab[57]['forcegroupby']         = true;
      $tab[57]['joinparams']           = $joinparams;
      $tab[57]['datatype']             = 'specific';
      $tab[57]['searchequalsonfield']  = 'specific';
      $tab[57]['searchtype']           = array('equals', 'notequals');

      $tab[58]['table']                = 'glpi_infocoms';
      $tab[58]['field']                = 'sink_coeff';
      $tab[58]['name']                 = __('Amortization coefficient');
      $tab[58]['forcegroupby']         = true;
      $tab[58]['joinparams']           = $joinparams;
      $tab[58]['datatype']             = 'decimal';


      $tab[59]['table']                = 'glpi_infocoms';
      $tab[59]['field']                = 'alert';
      $tab[59]['name']                 = __('Email alarms');
      $tab[59]['forcegroupby']         = true;
      $tab[59]['joinparams']           = $joinparams;
      $tab[59]['datatype']             = 'specific';

      $tab[122]['table']               = 'glpi_infocoms';
      $tab[122]['field']               = 'comment';
      $tab[122]['name']                = __('Comments on financial and administrative information');
      $tab[122]['datatype']            = 'text';
      $tab[122]['forcegroupby']        = true;
      $tab[122]['joinparams']          = $joinparams;

      $tab[159]['table']               = 'glpi_infocoms';
      $tab[159]['field']               = 'decommission_date';
      $tab[159]['name']                = __('Decommission date');
      $tab[159]['datatype']            = 'date';
      $tab[159]['forcegroupby']        = true;
      $tab[159]['joinparams']          = $joinparams;

      return $tab;
   }


   function getSearchOptions() {

      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false;
      $tab[2]['datatype']        = 'number';

      $tab[4]['table']           = $this->getTable();
      $tab[4]['field']           = 'buy_date';
      $tab[4]['name']            = __('Date of purchase');
      $tab[4]['datatype']        = 'date';

      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'use_date';
      $tab[5]['name']            = __('Startup date');
      $tab[5]['datatype']        = 'date';

      $tab[24]['table']          = 'glpi_infocoms';
      $tab[24]['field']          = 'delivery_date';
      $tab[24]['name']           = __('Delivery date');
      $tab[24]['datatype']       = 'date';
      $tab[24]['forcegroupby']   = true;

      $tab[23]['table']          = 'glpi_infocoms';
      $tab[23]['field']          = 'order_date';
      $tab[23]['name']           = __('Order date');
      $tab[23]['datatype']       = 'date';
      $tab[23]['forcegroupby']   = true;

      $tab[25]['table']          = 'glpi_infocoms';
      $tab[25]['field']          = 'warranty_date';
      $tab[25]['name']           = __('Start date of warranty');
      $tab[25]['datatype']       = 'date';
      $tab[25]['forcegroupby']   = true;

      $tab[27]['table']          = 'glpi_infocoms';
      $tab[27]['field']          = 'inventory_date';
      $tab[27]['name']           = __('Date of last physical inventory');
      $tab[27]['datatype']       = 'date';
      $tab[27]['forcegroupby']   = true;

      $tab[28]['table']          = 'glpi_infocoms';
      $tab[28]['field']          = 'decommission_date';
      $tab[28]['name']           = __('Decommission date');
      $tab[28]['datatype']       = 'date';
      $tab[28]['forcegroupby']   = true;

      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'warranty_duration';
      $tab[6]['name']            = __('Warranty duration');
      $tab[6]['datatype']        = 'number';
      $tab[6]['unit']            = 'month';
      $tab[6]['max']             = 120;
      $tab[6]['toadd']           = array(-1 => __('Lifelong'));

      $tab[7]['table']           = $this->getTable();
      $tab[7]['field']           = 'warranty_info';
      $tab[7]['name']            = __('Warranty information');
      $tab[7]['datatype']        = 'string';

      $tab[8]['table']           = $this->getTable();
      $tab[8]['field']           = 'warranty_value';
      $tab[8]['name']            = __('Warranty extension value');
      $tab[8]['datatype']        = 'decimal';

      $tab[9]['table']           = 'glpi_suppliers';
      $tab[9]['field']           = 'name';
      $tab[9]['name']            = __('Supplier');
      $tab[9]['datatype']        = 'dropdown';

      $tab[10]['table']          = $this->getTable();
      $tab[10]['field']          = 'order_number';
      $tab[10]['name']           = __('Order number');
      $tab[10]['datatype']       = 'string';

      $tab[11]['table']          = $this->getTable();
      $tab[11]['field']          = 'delivery_number';
      $tab[11]['name']           = __('Delivery form');
      $tab[11]['datatype']       = 'string';

      $tab[12]['table']          = $this->getTable();
      $tab[12]['field']          = 'immo_number';
      $tab[12]['name']           = __('Immobilization number');
      $tab[12]['datatype']       = 'string';

      $tab[13]['table']          = $this->getTable();
      $tab[13]['field']          = 'value';
      $tab[13]['name']           = _x('price', 'Value');
      $tab[13]['datatype']       = 'decimal';

      $tab[14]['table']          = $this->getTable();
      $tab[14]['field']          = 'sink_time';
      $tab[14]['name']           = __('Amortization duration');
      $tab[14]['datatype']       = 'number';
      $tab[14]['max']            = 15;
      $tab[14]['unit']           = 'year';

      $tab[15]['table']          = $this->getTable();
      $tab[15]['field']          = 'sink_type';
      $tab[15]['name']           = __('Amortization type');
      $tab[15]['datatype']       = 'specific';
      $tab[15]['searchtype']     = array('equals', 'notequals');

      $tab[16]['table']          = $this->getTable();
      $tab[16]['field']          = 'comment';
      $tab[16]['name']           = __('Comments');
      $tab[16]['datatype']       = 'text';

      $tab[17]['table']          = $this->getTable();
      $tab[17]['field']          = 'sink_coeff';
      $tab[17]['name']           = __('Amortization coefficient');
      $tab[17]['datatype']       = 'decimal';

      $tab[18]['table']          = $this->getTable();
      $tab[18]['field']          = 'bill';
      $tab[18]['name']           = __('Invoice number');
      $tab[18]['datatype']       = 'string';

      $tab[19]['table']          = 'glpi_budgets';
      $tab[19]['field']          = 'name';
      $tab[19]['name']           = __('Budget');
      $tab[19]['datatype']       = 'itemlink';

      $tab[20]['table']          = $this->getTable();
      $tab[20]['field']          = 'itemtype';
      $tab[20]['name']           = __('Type');
      $tab[20]['datatype']       = 'itemtype';
      $tab[20]['massiveaction']  = false;

      $tab[21]['table']          = $this->getTable();
      $tab[21]['field']          = 'items_id';
      $tab[21]['name']           = __('ID');
      $tab[21]['datatype']       = 'integer';
      $tab[21]['massiveaction']  = false;

      $tab[22]['table']          = $this->getTable();
      $tab[22]['field']          = 'alert';
      $tab[22]['name']           = __('Alarms on financial and administrative information');
      $tab[22]['datatype']       = 'integer';

      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['massiveaction']  = false;
      $tab[80]['datatype']       = 'dropdown';


      $tab[86]['table']          = $this->getTable();
      $tab[86]['field']          = 'is_recursive';
      $tab[86]['name']           = __('Child entities');
      $tab[86]['datatype']       = 'bool';

      return $tab;
   }


   /**
    * Display debug information for infocom of current object
   **/
   function showDebug() {

      $item = array('item_name'          => '',
                    'warrantyexpiration' => '',
                    'itemtype'           => $this->fields['itemtype'],
                    'items_id'           => $this->fields['items_id']);

      $options['entities_id'] = $this->getEntityID();
      $options['items']       = array($item);
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
   static function cloneItem($itemtype, $oldid, $newid, $newitemtype='') {
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
         $date_fields = array('buy_date', 'delivery_date', 'inventory_date', 'order_date',
                              'use_date', 'warranty_date');
         foreach ($date_fields as $f) {
            if (empty($input[$f])) {
               unset($input[$f]);
            }
         }
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
   static function getWarrantyExpir($from, $addwarranty, $deletenotice=0, $color=false) {

      // Life warranty
      if (($addwarranty == -1)
          && ($deletenotice == 0)) {
         return __('Never');
      }

      if (($from == NULL) || empty($from)) {
         return "";
      }

      $datetime = strtotime("$from+$addwarranty month -$deletenotice month");
      if ($color && ($datetime < time())) {
         return "<span class='red'>".Html::convDate(date("Y-m-d", $datetime))."</span>";
      }
      return Html::convDate(date("Y-m-d", $datetime));
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::getMassiveActionsForItemtype()
   **/
   static function getMassiveActionsForItemtype(array &$actions, $itemtype, $is_deleted=0,
                                                CommonDBTM $checkitem = NULL) {

      $action_name = __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'activate';

      if (InfoCom::canApplyOn($itemtype)
          && static::canCreate()) {
         $actions[$action_name] = __('Enable the financial and administrative information');
      }
   }


   /**
    * @since version 0.85
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
                     $input = array('itemtype' => $itemtype,
                                    'items_id' => $key);
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
}
?>
