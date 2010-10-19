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
class EntityData extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_entitydatas';
   // link in message dont work (no showForm)
   public $auto_message_on_action = false;

   // Array of "right required to update" => array of fields allowed
   private static $field_right = array('entity' => array(// Address
                                                         'address', 'postcode', 'postcode', 'town',
                                                         'state', 'country', 'website',
                                                         'phonenumber', 'fax', 'email', 'notepad',
                                                         // Advanced (could be user_authtype ?)
                                                         'ldap_dn', 'tag', 'ldapservers_id',
                                                         'entity_ldapfilter',
                                                         // Helpdesk config (could be another right)
                                                         'autoclose_delay'),
                                       // Notification
                                       'notification' => array('admin_email', 'admin_reply',
                                                               'admin_email_name',
                                                               'admin_reply_name',
                                                               'mailing_signature',
                                                               'cartridges_alert_repeat',
                                                               'consumables_alert_repeat',
                                                               'use_licenses_alert',
                                                               'use_contracts_alert',
                                                               'use_reservations_alert',
                                                               'use_infocoms_alert'));


   function getIndexName() {
      return 'entities_id';
   }


   function canCreate() {
      return haveRight('entity', 'w') || haveRight('notification', 'w');
   }


   function canView() {
      return haveRight('entity', 'r');
   }


   function prepareInputForAdd($input) {

      $input['max_closedate'] = $_SESSION["glpi_currenttime"];

      foreach (self::$field_right as $right => $fields) {
         if (!haveRight($right, 'w')) {

            foreach ($fields as $field) {
               if (isset($input[$field])) {
                  unset($input[$field]);
               }
            }
         }
      }
      return $input;
   }


   function prepareInputForUpdate($input) {

      // Si on change le taux de déclanchement de l'enquête (enquête activée),
      // cela s'applique aux prochains tickets - Pas à l'historique

      if (isset($input['inquest_rate'])
          && $input['inquest_rate']!=$this->fields['inquest_rate']) {

         $input['max_closedate'] = $_SESSION["glpi_currenttime"];
      }

      return $this->prepareInputForAdd($input);
   }


   /**
    *
    */
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
            echo "<input type='submit' name='update' value='".$LANG['buttons'][7]."' class='submit' >";
         } else {
            echo "<input type='submit' name='add' value='".$LANG['buttons'][7]."' class='submit' >";
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
    */
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

      echo "<tr><th colspan='4'>".$LANG['entity'][23]."</th></tr>";
      if (canUseLdap()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['entity'][12]."&nbsp;:&nbsp;</td>";
         echo "<td colspan='3'>";
         autocompletionTextField($entdata, "ldap_dn", array('size' => 100));
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['entity'][13]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      autocompletionTextField($entdata, "tag");
      echo "</td>";
      echo "<td>".$LANG['setup'][732]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      autocompletionTextField($entdata, "mail_domain");
      echo "</td></tr>";

      if (canUseLdap()) {
         echo "<tr><th colspan='4'>".$LANG['entity'][24]."</th></tr>";
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['entity'][25]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         autocompletionTextField($entdata, 'entity_ldapfilter');
         echo "</td>";
         echo "<td>".$LANG['entity'][15]."&nbsp;:&nbsp;</td>";
         echo "<td>";
         Dropdown::show('AuthLDAP', array ('name'       => 'ldapservers_id',
                                           'value'      =>  $entdata->fields['ldapservers_id'],
                                           'emptylabel' => $LANG['ldap'][44],
                                           'condition'  => "`is_active`='1'"));
         echo "</td></tr>";
      }

     if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='hidden' name='entities_id' value='$ID'>";

         if ($entdata->fields["id"]) {
            echo "<input type='hidden' name='id' value='".$entdata->fields["id"]."'>";
            echo "<input type='submit' name='update' value='".$LANG['buttons'][7]."' class='submit'>";
         } else {
            echo "<input type='submit' name='add' value='".$LANG['buttons'][7]."' class='submit'>";
         }

         echo "</td></tr>";
         echo "</table></form>";

      } else {
         echo "</table>";
      }
   }


   function post_getEmpty() {

      $fields = array('use_licenses_alert', 'use_contracts_alert', 'use_infocoms_alert',
                      'use_reservations_alert', 'autoclose_delay', 'consumables_alert_repeat',
                      'cartridges_alert_repeat', 'notclosed_delay');

      foreach ($fields as $field) {
         $this->fields[$field] = -1;
      }
   }


   static function showNotificationOptions(Entity $entity) {
      global $LANG;

      $con_spotted = false;

      $ID = $entity->getField('id');
      if (!$entity->can($ID,'r')) {
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
            echo "<input type='submit' name='update' value='".$LANG['buttons'][7]."' class='submit'>";
         } else {
            echo "<input type='submit' name='add' value='".$LANG['buttons'][7]."' class='submit'>";
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
          && $entitydatas->getField('ldapservers_id') != NOT_AVAILABLE) {
         return true;
      }

      //If there's a directory marked as default
      if (AuthLdap::getDefault()) {
         return true;
      }
      return false;
   }


   static function showHelpdeskOptions(Entity $entity) {
      global $LANG;

      $ID = $entity->getField('id');
      if (!$entity->can($ID,'r')) {
         return false;
      }
      $canedit = $entity->canCreate();

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
                       'emptylabel' => $LANG['calendar'][9]);

      if ($ID==0) {
         $options['emptylabel'] = DROPDOWN_EMPTY_VALUE;
      }
      Dropdown::show('Calendar', $options);

      if ($entdata->fields["calendars_id"] == 0) {
         $calendar = new Calendar();

         if ($calendar->getFromDB(self::getUsedCalendar($ID))) {
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
      Alert::dropdownIntegerNever('autoclose_delay', $entdata->fields['autoclose_delay'],
                                  array('max'            => 99,
                                        'inherit_global' => 1,
                                        'never_value'    => -10));
      echo "&nbsp;".$LANG['stats'][31]."</td></tr>";

      echo "<tr><th colspan='4'>".$LANG['entity'][19]."</th></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2'>".$LANG['entity'][20]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";
      Dropdown::showInteger('inquest_delay', $entdata->fields['inquest_delay'],
                            0, 90, 1, array(-1 => $LANG['setup'][731]));
      echo "&nbsp;".$LANG['stats'][31]."</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2'>".$LANG['entity'][21]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>";
      Dropdown::showInteger('inquest_rate', $entdata->fields['inquest_rate'],
                            10, 100, 10, array(-1 => $LANG['setup'][731],
                                                0 => $LANG['crontask'][31]));
      echo "&nbsp;%</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='2'>" . $LANG['entity'][22] . "&nbsp;:&nbsp;</td>";
      echo "<td colspan='2'>" . convDateTime($entdata->fields['max_closedate'])."</td></tr>";

      if ($canedit) {
         echo "<tr>";
         echo "<td class='tab_bg_2 center' colspan='4'>";
         echo "<input type='hidden' name='entities_id' value='$ID'>";

         if ($entdata->fields["id"]) {
            echo "<input type='hidden' name='id' value='".$entdata->fields["id"]."'>";
            echo "<input type='submit' name='update' value='".$LANG['buttons'][7]."' class='submit'>";
         } else {
            echo "<input type='submit' name='add' value='".$LANG['buttons'][7]."' class='submit'>";
         }

         echo "</td></tr>";
         echo "</table></form>";

      } else {
         echo "</table>";
      }

      echo "</div>";
   }


   static function getUsedCalendar($entities_id) {

      $entdata= new EntityData();

      // Search in entity data of the current entity
      if ($entdata->getFromDB($entities_id)) {

         // Calendar defined : use it
         if (isset($entdata->fields['calendars_id']) && $entdata->fields['calendars_id'] >0 ) {
            return $entdata->fields['calendars_id'];
         }
      }

      // Entity data not found or not defined calendar : search in parent one
      if ($entities_id > 0) {
         $current = new Entity();

         if ($current->getFromDB($entities_id)) {
            return EntityData::getUsedCalendar($current->fields['entities_id']);
         }
      }
      return -1;
   }
}

?>