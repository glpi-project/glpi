<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
   public $itemtype = 'Entity';
   public $items_id = 'entities_id';

   // From CommonDBTM
   public $table = 'glpi_entitydatas';
   // link in message dont work (no showForm)
   public $auto_message_on_action = false;

   // Array of "right required to update" => array of fields allowed
   // Missing field here couldn't be update (no right)
   private static $field_right = array('entity'          => array(// Address
                                                                  'address', 'postcode', 'postcode',
                                                                  'town', 'state', 'country',
                                                                  'website', 'phonenumber', 'fax',
                                                                  'email', 'notepad',
                                                                  // Advanced (could be user_authtype ?)
                                                                  'authldaps_id', 'entity_ldapfilter',
                                                                  'ldap_dn', 'mail_domain', 'tag',
                                                                  // Inventory
                                                                  'autofill_buy_date',
                                                                  'autofill_delivery_date',
                                                                  'autofill_order_date',
                                                                  'autofill_use_date',
                                                                  'autofill_warranty_date'),
                                       // Notification
                                       'notification'    => array('admin_email', 'admin_reply',
                                                                  'admin_email_name',
                                                                  'admin_reply_name',
                                                                  'mailing_signature',
                                                                  'cartridges_alert_repeat',
                                                                  'consumables_alert_repeat',
                                                                  'notclosed_delay',
                                                                  'use_licenses_alert',
                                                                  'use_contracts_alert',
                                                                  'use_reservations_alert',
                                                                  'use_infocoms_alert'),
                                       // Helpdesk
                                       'entity_helpdesk' => array('calendars_id', 'tickettype',
                                                                  'auto_assign_mode',
                                                                  'autoclose_delay',
                                                                  'inquest_config', 'inquest_rate',
                                                                  'inquest_delay', 'inquest_URL'));


   function getIndexName() {
      return 'entities_id';
   }


   function canCreate() {

      foreach (self::$field_right as $right => $fields) {
         if (haveRight($right, 'w')) {
            return true;
         }
      }
      return false;
   }


   function canView() {
      return haveRight('entity', 'r');
   }


   function prepareInputForAdd($input) {

      $input['max_closedate'] = $_SESSION["glpi_currenttime"];

      return $this->checkRightDatas($input);
   }


   function prepareInputForUpdate($input) {

      // Si on change le taux de déclenchement de l'enquête (enquête activée) ou le type de l'enquete,
      // cela s'applique aux prochains tickets - Pas à l'historique
      if ((isset($input['inquest_rate'])
           && $this->fields['inquest_rate'] == 0
           && $input['inquest_rate'] != $this->fields['inquest_rate'])
          || (isset($input['inquest_config'])
              && $this->fields['inquest_config'] == 0
              && $input['inquest_config']!= $this->fields['inquest_config'])) {


         $input['max_closedate'] = $_SESSION["glpi_currenttime"];
      }
      return $this->checkRightDatas($input);

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

         if (haveRight($right, 'w')) {
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
         echo "<form method='post' name=form action='".getItemTypeFormURL(__CLASS__)."'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>".$LANG['financial'][44]."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][35]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      autocompletionTextField($entdata, "phonenumber");
      echo "</td>";
      echo "<td rowspan='7'>".$LANG['financial'][44]."&nbsp;:&nbsp;</td>";
      echo "<td rowspan='7'>";
      echo "<textarea cols='45' rows='8' name='address'>". $entdata->fields["address"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][30]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      autocompletionTextField($entdata, "fax");
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][45]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      autocompletionTextField($entdata, "website");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][14]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      autocompletionTextField($entdata, "email");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][100]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      autocompletionTextField($entdata,"postcode", array('size' => 7));
      echo "&nbsp;".$LANG['financial'][101]."&nbsp;:&nbsp;";
      autocompletionTextField($entdata, "town", array('size' => 27));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][102]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      autocompletionTextField($entdata, "state");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][103]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      autocompletionTextField($entdata, "country");
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
         echo "</table></form>";

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
         echo "<form method='post' name=form action='".getItemTypeFormURL(__CLASS__)."'>";
      }
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='2'>".$LANG['entity'][23]."</th></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2' class='center'>".$LANG['entity'][26]."</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['entity'][13]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      autocompletionTextField($entdata, "tag", array('size' => 100));
      echo "</td></tr>";

      if (canUseLdap()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['entity'][12]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         autocompletionTextField($entdata, "ldap_dn", array('size' => 100));
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['entity'][27]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      autocompletionTextField($entdata, "mail_domain", array('size' => 100));
      echo "</td></tr>";

      if (canUseLdap()) {
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
         autocompletionTextField($entdata, 'entity_ldapfilter', array('size' => 100));
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
         echo "</table></form>";

      } else {
         echo "</table>";
      }
   }


   function post_getEmpty() {

      $fields = array('autoclose_delay', 'autofill_buy_date', 'autofill_delivery_date',
                      'autofill_order_date', 'autofill_use_date', 'autofill_warranty_date',
                      'cartridges_alert_repeat', 'consumables_alert_repeat', 'notclosed_delay',
                      'use_contracts_alert', 'use_infocoms_alert', 'use_licenses_alert',
                      'use_reservations_alert');

      foreach ($fields as $field) {
         $this->fields[$field] = -1;
      }
   }


   static function showInventoryOptions(Entity $entity) {
      global $LANG;

      $ID = $entity->getField('id');
      if (!$entity->can($ID,'r')) {
         return false;
      }

      // Notification right applied
      $canedit = haveRight('infocom', 'w') && haveAccessToEntity($ID);

      // Get data
      $entitydata = new EntityData();
      if (!$entitydata->getFromDB($ID)) {
         $entitydata->getEmpty();
      }

      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' name=form action='".getItemTypeFormURL(__CLASS__)."'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>".$LANG['financial'][3]."&nbsp;: ".$LANG['financial'][111]."</th></tr>";


      $options[0] = $LANG['financial'][113];
      if ($ID > 0) {
         $options[-1] = $LANG['common'][102];
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
      $options = array(0                        => $LANG['financial'][113],
                       Infocom::COPY_BUY_DATE   => $LANG['setup'][283].': '.$LANG['financial'][14],
                       Infocom::COPY_ORDER_DATE => $LANG['setup'][283].': '.$LANG['financial'][28]);
      if ($ID > 0) {
         $options[-1] = $LANG['common'][102];
      }

      Dropdown::showFromArray('autofill_warranty_date', $options,
                              array('value' => $entitydata->getField('autofill_warranty_date')));
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
         echo "</table></form>";

      } else {
         echo "</table>";
      }

      echo "</div>";

   }


   static function showNotificationOptions(Entity $entity) {
      global $LANG;

      $ID = $entity->getField('id');
      if (!$entity->can($ID,'r') || !haveRight('notification','r')) {
         return false;
      }

      // Notification right applied
      $canedit = haveRight('notification','w') && haveAccessToEntity($ID);

      // Get data
      $entitynotification = new EntityData();
      if (!$entitynotification->getFromDB($ID)) {
         $entitynotification->getEmpty();
      }

      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' name=form action='".getItemTypeFormURL(__CLASS__)."'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>".$LANG['setup'][240]."</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][203]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      autocompletionTextField($entitynotification, "admin_email");
      echo "</td>";
      echo "<td>" . $LANG['setup'][208] . "</td><td>";
      autocompletionTextField($entitynotification, "admin_email_name");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][207]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      autocompletionTextField($entitynotification, "admin_reply");
      echo "</td>";
      echo "<td>" . $LANG['setup'][209] . "</td><td>";
      autocompletionTextField($entitynotification, "admin_reply_name");
      echo "</td></tr>";

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
                            'inherit_global' => 1));
      echo "</td>";
      echo "<td>" . $LANG['setup'][245] . " - " . $LANG['setup'][243] . "</td><td>";
      $default_value = $entitynotification->fields['consumables_alert_repeat'];
      Alert::dropdown(array('name'           => 'consumables_alert_repeat',
                            'value'          => $default_value,
                            'inherit_global' => 1));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][264] . "</td><td>";
      $default_value = $entitynotification->fields['use_licenses_alert'];
      Alert::dropdownYesNo(array('name'           => "use_licenses_alert",
                                 'value'          => $default_value,
                                 'inherit_global' => 1));
      echo "</td>";
      echo "<td>" . $LANG['setup'][246] . "</td><td>";
      $default_value = $entitynotification->fields['use_contracts_alert'];
      Alert::dropdownYesNo(array('name'           => "use_contracts_alert",
                                 'value'          => $default_value,
                                 'inherit_global' => 1));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][247] . "</td><td>";
      $default_value = $entitynotification->fields['use_infocoms_alert'];
      Alert::dropdownYesNo(array('name'           => "use_infocoms_alert",
                                 'value'          => $default_value,
                                 'inherit_global' => 1));
      echo "</td>";
      echo "<td>" . $LANG['setup'][707] . "</td><td>";
      Alert::dropdownIntegerNever('use_reservations_alert',
                                  $entitynotification->fields['use_reservations_alert'],
                                  array('max'            => 99,
                                        'inherit_global' => 1));
      echo "&nbsp;".$LANG['job'][21]."</td></tr>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td >" . $LANG['setup'][708] . "</td><td>";
      Alert::dropdownIntegerNever('notclosed_delay', $entitynotification->fields["notclosed_delay"],
                                  array('max'            => 99,
                                        'inherit_global' => 1));
      echo "&nbsp;".$LANG['stats'][31]."</td>";
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
         echo "</table></form>";

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

      $entitydatas = new EntityData;

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
      if (!$entity->can($ID,'r') || !haveRight('entity_helpdesk','r')) {
         return false;
      }
      $canedit = haveRight('entity_helpdesk','w') && haveAccessToEntity($ID);

      // Get data
      $entdata = new EntityData();
      if (!$entdata->getFromDB($ID)) {
         $entdata->getEmpty();
      }

      echo "<div class='spaced'>";
      if ($canedit) {
         echo "<form method='post' name=form action='".getItemTypeFormURL(__CLASS__)."'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><td colspan='2'>".$LANG['buttons'][15]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";
      $options = array('value'      => $entdata->fields["calendars_id"],
                       'emptylabel' => $LANG['common'][102]);

      if ($ID==0) {
         $options['emptylabel'] = DROPDOWN_EMPTY_VALUE;
      }
      Dropdown::show('Calendar', $options);

      if ($entdata->fields["calendars_id"] == 0) {
         $calendar = new Calendar();

         if ($calendar->getFromDB(self::getUsedConfig('calendars_id',$ID))) {
            echo " - ".$calendar->getLink();
         }
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2'>".$LANG['entity'][28]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";
      $toadd = array();
      if ($ID!=0) {
         $toadd = array(0 => $LANG['common'][102]);
      }
      Ticket::dropdownType('tickettype', $entdata->fields["tickettype"], $toadd);

      if ($entdata->fields["calendars_id"] == 0) {
         $calendar = new Calendar();

         if ($calendar->getFromDB(self::getUsedConfig('calendars_id', $ID))) {
            echo " - ".$calendar->getLink();
         }
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td  colspan='2'>".$LANG['setup'][52]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";
      $autoassign = array(-1                            => $LANG['setup'][731],
                          NO_AUTO_ASSIGN                => $LANG['choice'][0],
                          AUTO_ASSIGN_HARDWARE_CATEGORY => $LANG['setup'][51],
                          AUTO_ASSIGN_CATEGORY_HARDWARE => $LANG['setup'][50]);

      Dropdown::showFromArray('auto_assign_mode', $autoassign,
                              array('value' => $entdata->fields["auto_assign_mode"]));

      echo "</td></tr>";

      echo "<tr><th colspan='4'>".$LANG['entity'][17]."</th></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2'>".$LANG['entity'][18]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";
      Dropdown::showInteger('autoclose_delay', $entdata->fields['autoclose_delay'], 0, 99, 1,
                            array(-1  => $LANG['setup'][731],
                                   -10  => $LANG['setup'][307]));
/*
      Alert::dropdownIntegerNever('autoclose_delay', $entdata->fields['autoclose_delay'],
                                  array('max'            => 99,
                                        'inherit_global' => 1,
                                        'never_value'    => -10,));
*/
      echo "&nbsp;".$LANG['stats'][31]."</td></tr>";

      echo "<tr><th colspan='4'>".$LANG['entity'][19]."</th></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2'>".$LANG['entity'][19]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";

      /// no inquest case = rate 0
      $typeinquest = array(0 => $LANG['common'][102],
                           1 => $LANG['satisfaction'][9],
                           2 => $LANG['satisfaction'][10]);

      // No inherit from parent for root entity
      if ($entdata->fields['entities_id'] == 0) {
         unset($typeinquest[0]);
         $entdata->fields['inquest_config'] = 1;
      }
      $rand = Dropdown::showFromArray('inquest_config', $typeinquest,
                                      $options = array('value' => $entdata->fields['inquest_config']));
      echo "</td></tr>\n";

      // Do not display for root entity in inherit case
      if ($entdata->fields['inquest_config'] == 0 && $entdata->fields['entities_id'] !=0) {
         $inquestconfig = EntityData::getUsedConfig('inquest_config',
                                                    $entdata->fields['entities_id']);
         $inquestrate   = EntityData::getUsedConfig('inquest_config',
                                                    $entdata->fields['entities_id'], 'inquest_rate');
         echo "<tr><td colspan='4' class='green center'>".$LANG['common'][102]."&nbsp;:&nbsp;";
         if ($inquestrate == 0) {
            echo $LANG['crontask'][31];
         } else {
            echo $typeinquest[$inquestconfig];
            echo " - " .EntityData::getUsedConfig('inquest_config', $entdata->fields['entities_id'],
                                                  'inquest_delay');
            echo "&nbsp;".$LANG['stats'][31]." - ";
            echo $inquestrate."%";
            if ($inquestconfig == 2) {
               echo " - ".EntityData::getUsedConfig('inquest_config', $entdata->fields['entities_id'],
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
         echo "</table></form>";

      } else {
         echo "</table>";
      }

      echo "</div>";

      ajaxUpdateItemOnSelectEvent("dropdown_inquest_config$rand", "inquestconfig",
                                  $CFG_GLPI["root_doc"]."/ajax/ticketsatisfaction.php", $params);
   }

   /**
    * Recovery datas of current entity or parent entity
    *
    * @param $fieldref  string name of the referent field to know if we look at parent entity
    * @param $entities_id
    * @param $fieldval string name of the field that we want value
   **/
   static function getUsedConfig($fieldref, $entities_id, $fieldval='') {

      // for calendar
      if (empty($fieldval)) {
         $fieldval = $fieldref;
      }

      $entdata = new EntityData();

      // Search in entity data of the current entity
      if ($entdata->getFromDB($entities_id)) {
         // Value is defined : use it
         if (isset($entdata->fields[$fieldref])
            && ($entdata->fields[$fieldref]>0
                || !is_numeric($entdata->fields[$fieldref]))) {
            return $entdata->fields[$fieldval];
         }
      }

      // Entity data not found or not defined : search in parent one
      if ($entities_id > 0) {
         $current = new Entity();

         if ($current->getFromDB($entities_id)) {
            return EntityData::getUsedConfig($fieldref, $current->fields['entities_id'], $fieldval);
         }
      }

      switch ($fieldval) {
         case "tickettype" :
            // Default is Incident if not set
            return Ticket::INCIDENT_TYPE;
      }
      return -1;
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

      if (strstr($url,"[ITEM_TYPE]")
          && $ticket->fields['itemtype']
          && class_exists($ticket->fields['itemtype'])) {
         $objet = new $ticket->fields['itemtype'];
         $url = str_replace("[ITEM_TYPE]", urlencode($objet->getTypeName()), $url);
      }

      if (strstr($url,"[ITEM_ID]")) {
         $url = str_replace("[ITEM_ID]", $ticket->fields['items_id'], $url);
      }

      if (strstr($url,"[ITEM_NAME]")
          && $ticket->fields['itemtype']
          && class_exists($ticket->fields['itemtype'])) {
         $objet = new $ticket->fields['itemtype'];
         if ($objet->getFromDB($ticket->fields['items_id'])) {
            $url = str_replace("[ITEM_NAME]", urlencode($objet->getName()), $url);
         }
      }

      if (strstr($url,"[TICKET_PRIORITY]")) {
         $url = str_replace("[TICKET_PRIORITY]", $ticket->fields['priority'], $url);
      }

      if (strstr($url,"[TICKETCATEGORIE_ID]")) {
         $url = str_replace("[TICKETCATEGORIE_ID]", $ticket->fields['ticketcategories_id'], $url);
      }

      if (strstr($url,"[TICKETCATEGORIE_NAME]")) {
         $url = str_replace("[TICKETCATEGORIE_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_ticketcategories',
                                                                $ticket->fields['ticketcategories_id'])),
                            $url);
      }

      if (strstr($url,"[TICKET_TYPE]")) {
         $url = str_replace("[TICKET_TYPE]", $ticket->fields['type'], $url);
      }

      if (strstr($url,"[TICKET_TYPENAME]")) {
         $url = str_replace("[TICKET_TYPENAME]",
                            Ticket::getTicketTypeName($ticket->fields['type']), $url);
      }

      if (strstr($url,"[SOLUTION_TYPE]")) {
         $url = str_replace("[SOLUTION_TYPE]", $ticket->fields['ticketsolutiontypes_id'], $url);
      }

      if (strstr($url,"[SOLUTION_NAME]")) {
         $url = str_replace("[SOLUTION_NAME]",
                            urlencode(Dropdown::getDropdownName('glpi_ticketsolutiontypes',
                                                                $ticket->fields['ticketsolutiontypes_id'])),
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

      if (strstr($url,"[SLA_LEVELID]")) {
         $url = str_replace("[SLA_LEVELID]", $ticket->fields['slalevels_id'], $url);
      }

      if (strstr($url,"[SLA_LEVELNAME]")) {
         $url = str_replace("[SLA_LEVELNAME]",
                            urlencode(Dropdown::getDropdownName('glpi_slalevels',
                                                                $ticket->fields['slalevels_id'])),
                            $url);
      }

      return $url;
   }

}

?>