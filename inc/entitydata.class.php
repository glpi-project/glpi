<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Entity Data class
 */
class EntityData extends CommonDBChild {


   // From CommonDBChild
   public $itemtype               = 'Entity';
   public $items_id               = 'entities_id';

   // From CommonDBTM
   public $dohistory              = true;
   // link in message dont work (no showForm)
   public $auto_message_on_action = false;

   const CONFIG_PARENT   = - 2;
   const CONFIG_NEVER    = -10;

   const AUTO_ASSIGN_HARDWARE_CATEGORY = 1;
   const AUTO_ASSIGN_CATEGORY_HARDWARE = 2;

   // Array of "right required to update" => array of fields allowed
   // Missing field here couldn't be update (no right)
   private static $field_right = array('entity'          => array(// Address
                                                                  'address', 'country', 'email',
                                                                  'fax', 'notepad', 'phonenumber',
                                                                  'postcode', 'state', 'town',
                                                                  'website',
                                                                  // Advanced (could be user_authtype ?)
                                                                  'authldaps_id', 'entity_ldapfilter',
                                                                  'ldap_dn', 'mail_domain', 'tag'),
                                       // Inventory
                                       'infocom'         => array('autofill_buy_date',
                                                                  'autofill_delivery_date',
                                                                  'autofill_order_date',
                                                                  'autofill_use_date',
                                                                  'autofill_warranty_date',
                                                                  'entities_id_software'),
                                       // Notification
                                       'notification'    => array('admin_email', 'admin_reply',
                                                                  'admin_email_name',
                                                                  'admin_reply_name',
                                                                  'default_alarm_threshold',
                                                                  'default_contract_alert',
                                                                  'default_infocom_alert',
                                                                  'mailing_signature',
                                                                  'cartridges_alert_repeat',
                                                                  'consumables_alert_repeat',
                                                                  'notclosed_delay',
                                                                  'use_licenses_alert',
                                                                  'use_contracts_alert',
                                                                  'use_reservations_alert',
                                                                  'use_infocoms_alert',
                                                                  'notification_subject_tag'),
                                       // Helpdesk
                                       'entity_helpdesk' => array('calendars_id', 'tickettype',
                                                                  'auto_assign_mode',
                                                                  'autoclose_delay',
                                                                  'inquest_config', 'inquest_rate',
                                                                  'inquest_delay', 'inquest_URL',
                                                                  'max_closedate', 'tickettemplates_id'));


   function getIndexName() {
      return 'entities_id';
   }


   function getLogTypeID() {
      return array('Entity', $this->fields['entities_id']);
   }


   function canCreate() {

      foreach (self::$field_right as $right => $fields) {
         if (Session::haveRight($right, 'w')) {
            return true;
         }
      }
      return false;
   }


   function canView() {
      return Session::haveRight('entity', 'r');
   }


   function prepareInputForAdd($input) {

      $input['max_closedate'] = $_SESSION["glpi_currenttime"];

      return $this->checkRightDatas($input);
   }


   function prepareInputForUpdate($input) {

      // Si on change le taux de déclenchement de l'enquête (enquête activée) ou le type de l'enquete,
      // cela s'applique aux prochains tickets - Pas à l'historique
      if ((isset($input['inquest_rate'])
           && ($this->fields['inquest_rate'] == 0 || is_null($this->fields['max_closedate']))
           && $input['inquest_rate'] != $this->fields['inquest_rate'])
          || (isset($input['inquest_config'])
              && ($this->fields['inquest_config'] == 0 || is_null($this->fields['max_closedate']))
              && $input['inquest_config']!= $this->fields['inquest_config'])) {

         $input['max_closedate'] = $_SESSION["glpi_currenttime"];
      }

      if (is_numeric(Session::getLoginUserID(false))) { // Filter input for connected
         return $this->checkRightDatas($input);
      }
      // for cron
      return $input;
   }


   /**
    * Check right on each field before add / update
    *
    * @param $input array (form)
    *
    * @return array (filtered input)
   **/
   private function checkRightDatas($input) {

      $tmp = array('entities_id' => $input['entities_id']);

      foreach (self::$field_right as $right => $fields) {

         if (Session::haveRight($right, 'w')) {
            foreach ($fields as $field) {
               if (isset($input[$field])) {
                  $tmp[$field] = $input[$field];
               }
            }
         }
      }

      return $tmp;
   }


   /**
    *
   **/
   static function showStandardOptions(Entity $entity) {
      global $LANG;

      $con_spotted = false;

      $ID = $entity->getField('id');
      if (!$entity->can($ID,'r')) {
         return false;
      }

      // Entity right applied
      $canedit = $entity->can($ID, 'w');

      // Get data
      $entdata = new EntityData();
      if (!$entdata->getFromDB($ID)) {
         $entdata->getEmpty();
      }

      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>".$LANG['financial'][44]."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][35]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Html::autocompletionTextField($entdata, "phonenumber");
      echo "</td>";
      echo "<td rowspan='7'>".$LANG['financial'][44]."&nbsp;:&nbsp;</td>";
      echo "<td rowspan='7'>";
      echo "<textarea cols='45' rows='8' name='address'>". $entdata->fields["address"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][30]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Html::autocompletionTextField($entdata, "fax");
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][45]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Html::autocompletionTextField($entdata, "website");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][14]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Html::autocompletionTextField($entdata, "email");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][100]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Html::autocompletionTextField($entdata,"postcode", array('size' => 7));
      echo "&nbsp;".$LANG['financial'][101]."&nbsp;:&nbsp;";
      Html::autocompletionTextField($entdata, "town", array('size' => 27));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][102]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Html::autocompletionTextField($entdata, "state");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][103]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Html::autocompletionTextField($entdata, "country");
      echo "</td></tr>";

      if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='hidden' name='entities_id' value='$ID'>";

         if ($entdata->fields["id"]) {
            echo "<input type='hidden' name='id' value='".$entdata->fields["id"]."'>";
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\"
                   class='submit'>";
         } else {
            echo "<input type='submit' name='add' value=\"".$LANG['buttons'][7]."\" class='submit'>";
         }

         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();

      } else {
         echo "</table>";
      }

      echo "</div>";
   }


   /**
    *
   **/
   static function showAdvancedOptions(Entity $entity) {
      global $DB, $LANG;

      $con_spotted = false;

      $ID = $entity->getField('id');
      if (!$entity->can($ID,'r')) {
         return false;
      }

      // Entity right applied (could be user_authtype)
      $canedit = $entity->can($ID, 'w');

      // Get data
      $entdata = new EntityData();
      if (!$entdata->getFromDB($ID)) {
         $entdata->getEmpty();
      }


      if ($canedit) {
         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      }
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='2'>".$LANG['entity'][23]."</th></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2' class='center'>".$LANG['entity'][26]."</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['entity'][13]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Html::autocompletionTextField($entdata, "tag", array('size' => 100));
      echo "</td></tr>";

      if (Toolbox::canUseLdap()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['entity'][12]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         Html::autocompletionTextField($entdata, "ldap_dn", array('size' => 100));
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['entity'][27]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Html::autocompletionTextField($entdata, "mail_domain", array('size' => 100));
      echo "</td></tr>";

      if (Toolbox::canUseLdap()) {
         echo "<tr><th colspan='2'>".$LANG['entity'][24]."</th></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['entity'][15]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         Dropdown::show('AuthLDAP', array('value'      => $entdata->fields['authldaps_id'],
                                          'emptylabel' => $LANG['ldap'][44],
                                          'condition'  => "`is_active` = '1'"));
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['entity'][25]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         Html::autocompletionTextField($entdata, 'entity_ldapfilter', array('size' => 100));
         echo "</td></tr>";
      }

     if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='2'>";
         echo "<input type='hidden' name='entities_id' value='$ID'>";

         if ($entdata->fields["id"]) {
            echo "<input type='hidden' name='id' value='".$entdata->fields["id"]."'>";
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\"
                   class='submit'>";
         } else {
            echo "<input type='submit' name='add' value=\"".$LANG['buttons'][7]."\" class='submit'>";
         }

         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();

      } else {
         echo "</table>";
      }
   }


   function post_getEmpty() {

      $fields = array('autoclose_delay', 'autofill_buy_date', 'autofill_delivery_date',
                      'autofill_order_date', 'autofill_use_date', 'autofill_warranty_date',
                      'calendars_id', 'cartridges_alert_repeat', 'consumables_alert_repeat',
                      'entities_id_software', 'notclosed_delay', 'tickettemplates_id',
                      'use_contracts_alert', 'use_infocoms_alert', 'use_licenses_alert',
                      'use_reservations_alert');

      foreach ($fields as $field) {
         $this->fields[$field] = self::CONFIG_PARENT;
      }
   }


   static function showInventoryOptions(Entity $entity) {
      global $LANG;

      $ID = $entity->getField('id');
      if (!$entity->can($ID,'r') || !Session::haveRight('infocom','r')) {
         return false;
      }

      // Notification right applied
      $canedit = Session::haveRight('infocom', 'w') && Session::haveAccessToEntity($ID);

      // Get data
      $entitydata = new EntityData();
      if (!$entitydata->getFromDB($ID)) {
         $entitydata->getEmpty();
      }

      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>".$LANG['financial'][3]."&nbsp;: ".$LANG['financial'][111]."</th></tr>";


      $options[0] = $LANG['financial'][113];
      if ($ID > 0) {
         $options[self::CONFIG_PARENT] = $LANG['common'][102];
      }

      foreach (getAllDatasFromTable('glpi_states') as $state) {
         $options[Infocom::ON_STATUS_CHANGE.'_'.$state['id']] = $LANG['financial'][112].' : '.
                                                                $state['name'];
      }

      $options[Infocom::COPY_WARRANTY_DATE] = $LANG['setup'][283].' '.$LANG['financial'][29];
      //Buy date
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['financial'][14] . "&nbsp;: </td>";
      echo "<td>";
      Dropdown::showFromArray('autofill_buy_date', $options,
                              array('value' => $entitydata->getField('autofill_buy_date')));
      echo "</td>";

      //Order date
      echo "<td> " . $LANG['financial'][28] . "&nbsp;: </td>";
      echo "<td>";
      $options[Infocom::COPY_BUY_DATE] = $LANG['setup'][283].' '.$LANG['financial'][14];
      Dropdown::showFromArray('autofill_order_date', $options,
                              array('value' => $entitydata->getField('autofill_order_date')));
      echo "</td></tr>";

      //Delivery date
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['financial'][27] . "&nbsp;: </td>";
      echo "<td>";
      $options[Infocom::COPY_ORDER_DATE] = $LANG['setup'][283].' '.$LANG['financial'][28];
      Dropdown::showFromArray('autofill_delivery_date', $options,
                              array('value' => $entitydata->getField('autofill_delivery_date')));
      echo "</td>";

      //Use date
      echo "<td> " . $LANG['financial'][76] . "&nbsp;: </td>";
      echo "<td>";
      $options[Infocom::COPY_DELIVERY_DATE] = $LANG['setup'][283].' '.$LANG['financial'][27];
      Dropdown::showFromArray('autofill_use_date', $options,
                              array('value' => $entitydata->getField('autofill_use_date')));
      echo "</td></tr>";

      //Warranty date
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['financial'][29] . "&nbsp;: </td>";
      echo "<td>";
      $options = array(0                           => $LANG['financial'][113],
                       Infocom::COPY_BUY_DATE      => $LANG['setup'][283].': '.$LANG['financial'][14],
                       Infocom::COPY_ORDER_DATE    => $LANG['setup'][283].': '.$LANG['financial'][28],
                       Infocom::COPY_DELIVERY_DATE => $LANG['setup'][283].' '.$LANG['financial'][27]);
      if ($ID > 0) {
         $options[self::CONFIG_PARENT] = $LANG['common'][102];
      }

      Dropdown::showFromArray('autofill_warranty_date', $options,
                              array('value' => $entitydata->getField('autofill_warranty_date')));
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr><th colspan='4'>".$LANG['Menu'][4]."</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['software'][10] . "&nbsp;: </td>";
      echo "<td>";

      $toadd = array(self::CONFIG_NEVER => $LANG['common'][110]); // Keep software in PC entity
      if ($ID > 0) {
         $toadd[self::CONFIG_PARENT] = $LANG['common'][102];
      }
      $entities = array($entitydata->fields['entities_id']);
      foreach (getAncestorsOf('glpi_entities',  $entitydata->fields['entities_id']) as $ent) {
         if (Session::haveAccessToEntity($ent)) {
            $entities[] = $ent;
         }
      }

      Dropdown::show('Entity',
                     array('name'               => 'entities_id_software',
                           'value'              => $entitydata->getField('entities_id_software'),
                           'toadd'              => $toadd,
                           'entity'             => $entities,
                           'display_rootentity' => true,
                           'comments'           => false));
      if ($entitydata->fields['entities_id_software'] == self::CONFIG_PARENT) {
         $tid = self::getUsedConfig('entities_id_software', $entity->getField('entities_id'));
         echo "<font class='green'>&nbsp;&nbsp;";
         echo self::getSpecificValueToDisplay('entities_id_software', $tid);
         echo "</font>";
      }
      echo "</td><td colspan='2'></td></tr>";

      if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='hidden' name='entities_id' value='$ID'>";

         if ($entitydata->fields["id"]) {
            echo "<input type='hidden' name='id' value='".$entitydata->fields["id"]."'>";
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\"
                   class='submit'>";
         } else {
            echo "<input type='submit' name='add' value=\"".$LANG['buttons'][7]."\" class='submit'>";
         }

         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();

      } else {
         echo "</table>";
      }

      echo "</div>";

   }


   static function showNotificationOptions(Entity $entity) {
      global $LANG;

      $ID = $entity->getField('id');
      if (!$entity->can($ID,'r') || !Session::haveRight('notification','r')) {
         return false;
      }

      // Notification right applied
      $canedit = Session::haveRight('notification','w') && Session::haveAccessToEntity($ID);

      // Get data
      $entitynotification = new EntityData();
      if (!$entitynotification->getFromDB($ID)) {
         $entitynotification->getEmpty();
      }

      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>".$LANG['setup'][240]."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][203]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Html::autocompletionTextField($entitynotification, "admin_email");
      echo "</td>";
      echo "<td>" . $LANG['setup'][208] . "</td><td>";
      Html::autocompletionTextField($entitynotification, "admin_email_name");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][207]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Html::autocompletionTextField($entitynotification, "admin_reply");
      echo "</td>";
      echo "<td>" . $LANG['setup'][209] . "</td><td>";
      Html::autocompletionTextField($entitynotification, "admin_reply_name");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['mailing'][2]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Html::autocompletionTextField($entitynotification, "notification_subject_tag");
      echo "</td>";
      echo "<td colspan='2'>&nbsp;</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='middle right'>" . $LANG['setup'][204] . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea cols='60' rows='5' name='mailing_signature'>".
             $entitynotification->fields["mailing_signature"]."</textarea>";
      echo "</td></tr>";


      echo "<tr><th colspan='4'>".$LANG['setup'][242]."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['setup'][245] . " - " . $LANG['setup'][244] . "</td><td>";
      $default_value = $entitynotification->fields['cartridges_alert_repeat'];
      Alert::dropdown(array('name'           => 'cartridges_alert_repeat',
                            'value'          => $default_value,
                            'inherit_parent' => ($ID>0 ? 1 : 0)));
      echo "</td>";
      echo "<td rowspan='2'>" . $LANG['setup'][115] . "&nbsp;:</td><td rowspan='2'>";
      if ($ID > 0) {
         $toadd = array(self::CONFIG_PARENT => $LANG['common'][102],
                        self::CONFIG_NEVER => $LANG['setup'][307]);
      } else {
         $toadd = array(self::CONFIG_NEVER =>$LANG['setup'][307]);
      }
      Dropdown::showInteger('default_alarm_threshold',
                            $entitynotification->fields["default_alarm_threshold"], 0, 100, 1,
                            $toadd);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['setup'][245] . " - " . $LANG['setup'][243] . "</td><td>";
      $default_value = $entitynotification->fields['consumables_alert_repeat'];
      Alert::dropdown(array('name'           => 'consumables_alert_repeat',
                            'value'          => $default_value,
                            'inherit_parent' => ($ID>0 ? 1 : 0)));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['setup'][246] . "</td><td>";
      $default_value = $entitynotification->fields['use_contracts_alert'];
      Alert::dropdownYesNo(array('name'           => "use_contracts_alert",
                                 'value'          => $default_value,
                                 'inherit_parent' => ($ID>0 ? 1 : 0)));
      echo "</td>";
      echo "<td >".$LANG['setup'][46] . "&nbsp;:</td><td>";
      $default_value =  $entitynotification->fields["default_contract_alert"];
      Contract::dropdownAlert(array('name'           => "default_contract_alert",
                                    'value'          => $default_value,
                                    'inherit_parent' => (($ID > 0) ? 1 : 0)));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['setup'][247] . "</td><td>";
      $default_value = $entitynotification->fields['use_infocoms_alert'];
      Alert::dropdownYesNo(array('name'           => "use_infocoms_alert",
                                 'value'          => $default_value,
                                 'inherit_parent' => ($ID>0 ? 1 : 0)));
      echo "</td>";
      echo "<td >" . $LANG['setup'][46]."&nbsp;:</td><td>";
      $default_value = $entitynotification->fields["default_infocom_alert"];
      Alert::dropdownInfocomAlert(array('name'           => "default_infocom_alert",
                                        'value'          => $default_value,
                                        'inherit_parent' => (($ID > 0) ? 1 : 0)));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['setup'][264] . "</td><td>";
      $default_value = $entitynotification->fields['use_licenses_alert'];
      Alert::dropdownYesNo(array('name'           => "use_licenses_alert",
                                 'value'          => $default_value,
                                 'inherit_parent' => ($ID>0 ? 1 : 0)));
      echo "</td>";
      echo "<td colspan='2'></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['setup'][707] . "</td><td>";
      Alert::dropdownIntegerNever('use_reservations_alert',
                                  $entitynotification->fields['use_reservations_alert'],
                                  array('max'            => 99,
                                        'inherit_parent' => ($ID>0 ? 1 : 0)));
      echo "&nbsp;".Toolbox::ucfirst($LANG['gmt'][1])."</td>";
      echo "<td colspan='2'></tr>";

      echo "<tr class='tab_bg_1'><td >" . $LANG['setup'][708] . "</td><td>";
      Alert::dropdownIntegerNever('notclosed_delay', $entitynotification->fields["notclosed_delay"],
                                  array('max'            => 99,
                                        'inherit_parent' => ($ID>0 ? 1 : 0)));
      echo "&nbsp;".Toolbox::ucfirst($LANG['calendar'][12])."</td>";
      echo "<td colspan='2'></td></tr>";

      if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='hidden' name='entities_id' value='$ID'>";

         if ($entitynotification->fields["id"]) {
            echo "<input type='hidden' name='id' value='".$entitynotification->fields["id"]."'>";
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\"
                   class='submit'>";
         } else {
            echo "<input type='submit' name='add' value=\"".$LANG['buttons'][7]."\" class='submit'>";
         }

         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();

      } else {
         echo "</table>";
      }

      echo "</div>";
   }


   private static function getEntityIDByField($field,$value) {
      global $DB;

      $sql = "SELECT `entities_id`
              FROM `glpi_entitydatas`
              WHERE `".$field."` = '".$value."'";

      $result = $DB->query($sql);

      if ($DB->numrows($result)==1) {
         return $DB->result($result, 0, "entities_id");
      }
      return -1;
   }


   static function getEntityIDByDN($value) {
      return self::getEntityIDByField("ldap_dn", $value);
   }


   static function getEntityIDByTag($value) {
      return self::getEntityIDByField("tag", $value);
   }


   static function getEntityIDByDomain($value) {
      return self::getEntityIDByField("mail_domain", $value);
   }


   static function isEntityDirectoryConfigured($entities_id) {

      $entitydatas = new EntityData();

      if ($entitydatas->getFromDB($entities_id)
          && $entitydatas->getField('authldaps_id') != NOT_AVAILABLE) {
         return true;
      }

      //If there's a directory marked as default
      if (AuthLdap::getDefault()) {
         return true;
      }
      return false;
   }


   static function showHelpdeskOptions(Entity $entity) {
      global $LANG, $CFG_GLPI;

      $ID = $entity->getField('id');
      if (!$entity->can($ID,'r') || !Session::haveRight('entity_helpdesk','r')) {
         return false;
      }
      $canedit = Session::haveRight('entity_helpdesk','w') && Session::haveAccessToEntity($ID);

      // Get data
      $entdata = new EntityData();
      if (!$entdata->getFromDB($ID)) {
         $entdata->getEmpty();
      }

      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' name=form action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><td colspan='2'>".$LANG['job'][58]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";
      $toadd = array();
      if ($ID != 0) {
         $toadd = array(self::CONFIG_PARENT => $LANG['common'][102]);
      }

      $options = array('value'      => $entdata->fields["tickettemplates_id"],
                                               'toadd'  =>$toadd);

      Dropdown::show('TicketTemplate', $options);

      if ($entdata->fields["tickettemplates_id"] == self::CONFIG_PARENT) {
         echo "<font class='green'>&nbsp;&nbsp;";
         $tt  = new TicketTemplate();
         $tid = self::getUsedConfig('tickettemplates_id', $ID);
         if (!$tid) {
            echo Dropdown::EMPTY_VALUE;
         } else if ($tt->getFromDB($tid)) {
            echo "- ".$tt->getLink();
         }
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2'>".$LANG['buttons'][15]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";
      $options = array('value'      => $entdata->fields["calendars_id"],
                       'emptylabel' => $LANG['sla'][10]);

      if ($ID) {
         $options['toadd'] = array(self::CONFIG_PARENT => $LANG['common'][102]);
      }
      Dropdown::show('Calendar', $options);

      if ($entdata->fields["calendars_id"] == self::CONFIG_PARENT) {
         echo "<font class='green'>&nbsp;&nbsp;";
         $calendar = new Calendar();
         $cid = self::getUsedConfig('calendars_id', $ID, '', 0);
         if (!$cid) {
            echo "- ".$LANG['sla'][10];
         } else if ($calendar->getFromDB($cid)) {
            echo "- ".$calendar->getLink();
         }
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2'>".$LANG['entity'][28]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";
      $toadd = array();
      if ($ID != 0) {
         $toadd = array(self::CONFIG_PARENT => $LANG['common'][102]);
      }
      Ticket::dropdownType('tickettype', array('value' => $entdata->fields["tickettype"],
                                               'toadd'  =>$toadd));

      if ($entdata->fields['tickettype'] == self::CONFIG_PARENT) {
         echo "<font class='green'>&nbsp;&nbsp;";
         echo Ticket::getTicketTypeName(self::getUsedConfig('tickettype', $ID, '', Ticket::INCIDENT_TYPE));
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td  colspan='2'>".$LANG['setup'][52]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";
      $autoassign = self::getAutoAssignMode();

      if ($ID == 0) {
         unset($autoassign[self::CONFIG_PARENT]);
      }

      Dropdown::showFromArray('auto_assign_mode', $autoassign,
                              array('value' => $entdata->fields["auto_assign_mode"]));

      if ($entdata->fields['auto_assign_mode'] == self::CONFIG_PARENT && $ID != 0) {
         $auto_assign_mode = self::getUsedConfig('auto_assign_mode', $entdata->fields['entities_id']);
         echo "<font class='green'>&nbsp;&nbsp;";
         echo $autoassign[$auto_assign_mode];
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr><th colspan='4'>".$LANG['entity'][17]."</th></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2'>".$LANG['entity'][18]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";
      $autoclose = array(self::CONFIG_PARENT => $LANG['common'][102],
                         self::CONFIG_NEVER  => $LANG['setup'][307]);
      if ($ID == 0) {
         unset($autoclose[self::CONFIG_PARENT]);
      }

      Dropdown::showInteger('autoclose_delay', $entdata->fields['autoclose_delay'], 0, 99, 1,
                            $autoclose);

      echo "&nbsp;".Toolbox::ucfirst($LANG['calendar'][12]);

      if ($entdata->fields['autoclose_delay'] == self::CONFIG_PARENT && $ID != 0) {
         $autoclose_mode = self::getUsedConfig('autoclose_delay', $entdata->fields['entities_id'],
                                               '', self::CONFIG_NEVER);

         echo "<font class='green'>&nbsp;&nbsp;";
         if ($autoclose_mode >= 0) {
            echo $autoclose_mode." ".$LANG['calendar'][12];
         } else {
            echo $autoclose[$autoclose_mode];
         }
         echo "</font>";
      }
      echo "</td></tr>";

      echo "<tr><th colspan='4'>".$LANG['entity'][19]."</th></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2'>".$LANG['entity'][19]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";

      /// no inquest case = rate 0
      $typeinquest = array(self::CONFIG_PARENT  => $LANG['common'][102],
                           1                    => $LANG['satisfaction'][9],
                           2                    => $LANG['satisfaction'][10]);

      // No inherit from parent for root entity
      if ($ID == 0) {
         unset($typeinquest[self::CONFIG_PARENT]);
         if ($entdata->fields['inquest_config'] == self::CONFIG_PARENT) {
            $entdata->fields['inquest_config'] = 1;
         }
      }
      $rand = Dropdown::showFromArray('inquest_config', $typeinquest,
                                      $options = array('value' => $entdata->fields['inquest_config']));
      echo "</td></tr>\n";

      // Do not display for root entity in inherit case
      if ($entdata->fields['inquest_config'] == self::CONFIG_PARENT && $ID !=0) {
         $inquestconfig = self::getUsedConfig('inquest_config', $entdata->fields['entities_id']);
         $inquestrate   = self::getUsedConfig('inquest_config', $entdata->fields['entities_id'],
                                              'inquest_rate');
         echo "<tr><td colspan='4' class='green center'>".$LANG['common'][102]."&nbsp;:&nbsp;";

         if ($inquestrate == 0) {
            echo $LANG['crontask'][31];
         } else {
            echo $typeinquest[$inquestconfig];
            echo " - " .self::getUsedConfig('inquest_config', $entdata->fields['entities_id'],
                                            'inquest_delay');
            echo "&nbsp;".Toolbox::ucfirst($LANG['calendar'][12])." - ";
            echo $inquestrate."%";
            if ($inquestconfig == 2) {
               echo " - ".self::getUsedConfig('inquest_config', $entdata->fields['entities_id'],
                                              'inquest_URL');
            }
         }
         echo "</td></tr>\n";
      }

      echo "<tr class='tab_bg_1'><td colspan='4'>";

      $_REQUEST = array('inquest_config' => $entdata->fields['inquest_config'],
                        'entities_id'    => $ID);
      $params = array('inquest_config' => '__VALUE__',
                      'entities_id'    => $ID);
      echo "<div id='inquestconfig'>";
      include GLPI_ROOT.'/ajax/ticketsatisfaction.php';
      echo "</div>\n";

      echo "</td></tr>";

      if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='hidden' name='entities_id' value='$ID'>";

         if ($entdata->fields["id"]) {
            echo "<input type='hidden' name='id' value='".$entdata->fields["id"]."'>";
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\"
                   class='submit'>";
         } else {
            echo "<input type='submit' name='add' value=\"".$LANG['buttons'][7]."\" class='submit'>";
         }

         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();

      } else {
         echo "</table>";
      }

      echo "</div>";

      Ajax::updateItemOnSelectEvent("dropdown_inquest_config$rand", "inquestconfig",
                                    $CFG_GLPI["root_doc"]."/ajax/ticketsatisfaction.php", $params);
   }

   /**
    * Retrieve data of current entity or parent entity
    *
    * @param $fieldref  string name of the referent field to know if we look at parent entity
    * @param $entities_id
    * @param $fieldval string name of the field that we want value
    * @param $default_value value to return
   **/
   static function getUsedConfig($fieldref, $entities_id, $fieldval='', $default_value=-2) {

      // for calendar
      if (empty($fieldval)) {
         $fieldval = $fieldref;
      }

      $entdata = new EntityData();

      // Search in entity data of the current entity
      if ($entdata->getFromDB($entities_id)) {
         // Value is defined : use it
         if (isset($entdata->fields[$fieldref])) {
            // Numerical value
            if (is_numeric($default_value)
                && $entdata->fields[$fieldref] != EntityData::CONFIG_PARENT) {
               return $entdata->fields[$fieldval];
            }
            // String value
            if (!is_numeric($default_value) && $entdata->fields[$fieldref]) {
               return $entdata->fields[$fieldval];
            }
         }
      }
      // Entity data not found or not defined : search in parent one
      if ($entities_id > 0) {
         $current = new Entity();

         if ($current->getFromDB($entities_id)) {
            $ret = self::getUsedConfig($fieldref, $current->fields['entities_id'], $fieldval,
                                       $default_value);
            return $ret;

         }
      }
/*
      switch ($fieldval) {
         case "tickettype" :
            // Default is Incident if not set
            return Ticket::INCIDENT_TYPE;
      }
      */
      return $default_value;
   }


   /**
   * Generate link for ticket satisfaction
   *
   * @param $ticket ticket object
   *
   * @return url contents
   */
   static function generateLinkSatisfaction($ticket) {
      global $DB;

      $url = self::getUsedConfig('inquest_config', $ticket->fields['entities_id'], 'inquest_URL');

      if (strstr($url,"[TICKET_ID]")) {
         $url = str_replace("[TICKET_ID]", $ticket->fields['id'], $url);
      }

      if (strstr($url,"[TICKET_NAME]")) {
         $url = str_replace("[TICKET_NAME]", urlencode($ticket->fields['name']), $url);
      }

      if (strstr($url,"[TICKET_CREATEDATE]")) {
         $url = str_replace("[TICKET_CREATEDATE]", $ticket->fields['date'], $url);
      }

      if (strstr($url,"[TICKET_SOLVEDATE]")) {
         $url = str_replace("[TICKET_SOLVEDATE]", $ticket->fields['solvedate'], $url);
      }

      if (strstr($url,"[REQUESTTYPE_ID]")) {
         $url = str_replace("[REQUESTTYPE_ID]", $ticket->fields['requesttypes_id'], $url);
      }

      if (strstr($url,"[REQUESTTYPE_NAME]")) {
         $url = str_replace("[REQUESTTYPE_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_requesttypes',
                                                                $ticket->fields['requesttypes_id'])),
                            $url);
      }

      if (strstr($url,"[ITEMTYPE]")
          && $ticket->fields['itemtype']
          && ($objet = getItemForItemtype($ticket->fields['itemtype']))) {
         $url = str_replace("[ITEMTYPE]", urlencode($objet->getTypeName()), $url);
      }

      if (strstr($url,"[ITEM_ID]")) {
         $url = str_replace("[ITEM_ID]", $ticket->fields['items_id'], $url);
      }

      if (strstr($url,"[ITEM_NAME]")
          && $ticket->fields['itemtype']
          && ($objet = getItemForItemtype($ticket->fields['itemtype']))) {
         if ($objet->getFromDB($ticket->fields['items_id'])) {
            $url = str_replace("[ITEM_NAME]", urlencode($objet->getName()), $url);
         }
      }

      if (strstr($url,"[TICKET_PRIORITY]")) {
         $url = str_replace("[TICKET_PRIORITY]", $ticket->fields['priority'], $url);
      }

      if (strstr($url,"[TICKETCATEGORY_ID]")) {
         $url = str_replace("[TICKETCATEGORY_ID]", $ticket->fields['itilcategories_id'], $url);
      }

      if (strstr($url,"[TICKETCATEGORY_NAME]")) {
         $url = str_replace("[TICKETCATEGORY_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_itilcategories',
                                                                $ticket->fields['itilcategories_id'])),
                            $url);
      }

      if (strstr($url,"[TICKETTYPE_ID]")) {
         $url = str_replace("[TICKETTYPE_ID]", $ticket->fields['type'], $url);
      }

      if (strstr($url,"[TICKET_TYPENAME]")) {
         $url = str_replace("[TICKET_TYPENAME]",
                            Ticket::getTicketTypeName($ticket->fields['type']), $url);
      }

      if (strstr($url,"[SOLUTIONTYPE_ID]")) {
         $url = str_replace("[SOLUTIONTYPE_ID]", $ticket->fields['solutiontypes_id'], $url);
      }

      if (strstr($url,"[SOLUTIONTYPE_NAME]")) {
         $url = str_replace("[SOLUTIONTYPE_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_solutiontypes',
                                                                $ticket->fields['solutiontypes_id'])),
                            $url);
      }

      if (strstr($url,"[SLA_ID]")) {
         $url = str_replace("[SLA_ID]", $ticket->fields['slas_id'], $url);
      }

      if (strstr($url,"[SLA_NAME]")) {
         $url = str_replace("[SLA_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_slas',
                                                                $ticket->fields['slas_id'])),
                            $url);
      }

      if (strstr($url,"[SLALEVEL_ID]")) {
         $url = str_replace("[SLALEVEL_ID]", $ticket->fields['slalevels_id'], $url);
      }

      if (strstr($url,"[SLALEVEL_NAME]")) {
         $url = str_replace("[SLALEVEL_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_slalevels',
                                                                $ticket->fields['slalevels_id'])),
                            $url);
      }

      return $url;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'Entity' :
               $ong = array();
               $ong[1] = $LANG['financial'][44];      // Address
               $ong[2] = $LANG['entity'][14];         // Advanced
               if (Session::haveRight('notification','r')) {
                  $ong[3] = $LANG['setup'][704];      // Notification
               }
               if (Session::haveRight('entity_helpdesk','r')) {
                  $ong[4] = $LANG['title'][24];       // Helpdesk
               }
               $ong[5] = $LANG['Menu'][38];           // Inventory

               return $ong;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='Entity') {
         switch ($tabnum) {
            case 1 :
               self::showStandardOptions($item);
               break;

            case 2 :
               self::showAdvancedOptions($item);
               break;

            case 3 :
               self::showNotificationOptions($item);
               break;

            case 4 :
               self::showHelpdeskOptions($item);
               break;

            case 5 :
               self::showInventoryOptions($item);
               break;
         }
      }
      return true;
   }

   /**
    * get value for auto_assign_mode
    *
    * @since version 0.83
    *
    * @param $val if not set, ask for all values, else for 1 value
    *
    * @return array or string
    */
   static function getAutoAssignMode($val=NULL) {
      global $LANG;
      $tab = array(self::CONFIG_PARENT                  => $LANG['common'][102],
                   self::CONFIG_NEVER                   => $LANG['choice'][0],
                   self::AUTO_ASSIGN_HARDWARE_CATEGORY  => $LANG['setup'][51],
                   self::AUTO_ASSIGN_CATEGORY_HARDWARE  => $LANG['setup'][50]);

      if (is_null($val)) {
         return $tab;
      }
      if (isset($tab[$val])) {
         return $tab[$val];
      }
      return NOT_AVAILABLE;
   }

   static function getSpecificValueToDisplay($field, $values, $options=array()) {
      global $LANG;

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'use_licenses_alert' :
         case 'use_contracts_alert' :
         case 'use_infocoms_alert' :
            if ($values[$field] == self::CONFIG_PARENT) {
               return $LANG['common'][102];
            }
            return Dropdown::getYesNo($values[$field]);

         case 'use_reservations_alert' :
            switch ($values[$field]) {
               case self::CONFIG_PARENT :
                  return $LANG['common'][102];

               case 0 :
                  return $LANG['setup'][307];
            }
            return $values[$field].' '.Toolbox::ucfirst($LANG['gmt'][1]);

         case 'cartridges_alert_repeat' :
         case 'consumables_alert_repeat' :
            switch ($values[$field]) {
               case self::CONFIG_PARENT :
                  return $LANG['common'][102];

               case self::CONFIG_NEVER :
                  return $LANG['setup'][307];

               case DAY_TIMESTAMP :
                  return $LANG['setup'][305];

               case WEEK_TIMESTAMP :
                  return $LANG['setup'][308];

               case MONTH_TIMESTAMP :
                  return $LANG['setup'][309];
            }
            break;

         case 'notclosed_delay' :   // 0 means never
            if ($values[$field] == 0) {
               return $LANG['setup'][307];
            }
            // nobreak;

         case 'autoclose_delay' :   // 0 means immediatly
            switch ($values[$field]) {
               case self::CONFIG_PARENT :
                  return $LANG['common'][102];

               case self::CONFIG_NEVER :
                  return $LANG['setup'][307];
            }
            return $values[$field].' '.Toolbox::ucfirst($LANG['calendar'][12]);

         case 'auto_assign_mode' :
            return self::getAutoAssignMode($values[$field]);

         case 'calendars_id' :
            switch ($values[$field]) {
               case self::CONFIG_PARENT :
                  return $LANG['common'][102];

               case 0 :
                  return $LANG['sla'][10];
            }
            return Dropdown::getDropdownName('glpi_calendars', $values[$field]);

         case 'tickettype' :
            if ($values[$field] == self::CONFIG_PARENT) {
               return $LANG['common'][102];
            }
            return Ticket::getTicketTypeName($values[$field]);

         case 'autofill_buy_date' :
         case 'autofill_order_date' :
         case 'autofill_delivery_date' :
         case 'autofill_use_date' :
         case 'autofill_warranty_date' :
            switch ($values[$field]) {
               case self::CONFIG_PARENT :
                  return $LANG['common'][102];

               case Infocom::COPY_WARRANTY_DATE :
                  return $LANG['setup'][283].' '.$LANG['financial'][29];

               case Infocom::COPY_BUY_DATE :
                  return $LANG['setup'][283].' '.$LANG['financial'][14];

               case Infocom::COPY_ORDER_DATE :
                  return $LANG['setup'][283].' '.$LANG['financial'][28];

               case Infocom::COPY_DELIVERY_DATE :
                  return $LANG['setup'][283].' '.$LANG['financial'][27];

               default:
                  if (strstr($values[$field], '_')) {
                     list($type,$sid) = explode('_', $values[$field], 2);
                     if ($type == Infocom::ON_STATUS_CHANGE) {
                        return $LANG['financial'][112].' : '.
                               Dropdown::getDropdownName('glpi_states', $sid);
                     }
                  }
            }
            return $LANG['financial'][113];

         case 'inquest_config' :
            if ($values[$field] == self::CONFIG_PARENT) {
               return $LANG['common'][102];
            }
            return TicketSatisfaction::getTypeInquestName($values[$field]);

         case 'tickettemplates_id' :
            if ($values[$field] == self::CONFIG_PARENT) {
               return $LANG['common'][102];
            }
            return Dropdown::getDropdownName('glpi_tickettemplates', $values[$field]);

         case 'default_contract_alert' :
            return Contract::getAlertName($values[$field]);

         case 'default_infocom_alert' :
            return Alert::getAlertName($values[$field]);

         case 'entities_id_software' :
            if ($values[$field] == self::CONFIG_NEVER) {
               return $LANG['common'][110];
            }
            if ($values[$field] == self::CONFIG_PARENT) {
               return $LANG['common'][102];
            }
            return Dropdown::getDropdownName('glpi_entities', $values[$field]);

      }
      return '';
   }
}

?>
