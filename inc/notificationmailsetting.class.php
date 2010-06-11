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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 *  This class manages the mail settings
 */
class NotificationMailSetting extends CommonDBTM {

   var $table = 'glpi_configs';

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][201]. ' '.$LANG['mailing'][118];
   }


   function defineTabs($options=array()) {
      global $LANG;

      $tabs[1] = $LANG['common'][12];
      $tabs[2] = $LANG['setup'][242];

      return $tabs;
   }


   /**
    * Print the mailing config form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target filename : where to go when done.
    *     - tabs integer : ID of the tab to display
    *
    * @return Nothing (display)
    *
   **/
   function showForm($ID, $options=array()) {
      global $LANG, $CFG_GLPI;

      if (!haveRight("config", "w")) {
         return false;
      }
      $this->getFromDB($ID);
      $this->showTabs($options);
      $this->showFormHeader($options);
      echo "<tr class='tab_bg_2'><td>" . $LANG['setup'][202] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("use_mailing", $CFG_GLPI["use_mailing"]);
      echo "</td>";
      echo "<td>" . $LANG['setup'][203] . "&nbsp;:</td>";
      echo "<td><input type='text' name='admin_email' size='40' value=\"" .
                 $CFG_GLPI["admin_email"] . "\">";
      if (!NotificationMail::isUserAddressValid($CFG_GLPI["admin_email"])) {
          echo "<span class='red'>&nbsp;".$LANG['mailing'][110]."</span>";
      }
      echo " </td></tr>";

      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][227] . "&nbsp;:</td>";
      echo "<td><input type='text' name='url_base' size='40' value='".$CFG_GLPI["url_base"]."'>";
      echo "</td>";
      echo "<td >" . $LANG['setup'][207] . "&nbsp;:</td>";
      echo "<td><input type='text' name='admin_reply' size='40' value=\"" .
                 $CFG_GLPI["admin_reply"] . "\">";
      if (!NotificationMail::isUserAddressValid($CFG_GLPI["admin_reply"])) {
         echo "<span class='red'>&nbsp;".$LANG['mailing'][110]."</span>";
      }
      echo " </td></tr>";
      if (!function_exists('mail')) {
          echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
          echo "<span class='red'>" . $LANG['setup'][217] . "&nbsp;:</span>";
          echo "<span>" . $LANG['setup'][218] . "</span></td></tr>";
      }

      echo "<tr class='tab_bg_2'><td>" . $LANG['setup'][204] . "&nbsp;:</td>";
      echo "<td colspan='3'><textarea cols='60' rows='3' name='mailing_signature'>".
                 $CFG_GLPI["mailing_signature"]."</textarea></td></tr>";

      $options['candel'] = false;
      $this->showFormButtons($options);
      $this->addDivForTabs();
      return true;
   }


   function canCreate() {
      return haveRight('config', 'w');
   }


   function canView() {
      return haveRight('config', 'r');
   }


   function showFormMailServerConfig($target) {
      global $LANG,$CFG_GLPI;

      echo "<form action='$target' method='post'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<input type='hidden' name='id' value='1'>";
      echo "<tr class='tab_bg_1'><th colspan='4'>".$LANG['setup'][660]."</th></tr>";
      echo "<tr class='tab_bg_2'><td>" . $LANG['setup'][231] . "&nbsp;:</td><td>";
      $mail_methods = array(MAIL_MAIL   => $LANG['setup'][650],
                            MAIL_SMTP    => $LANG['setup'][651],
                            MAIL_SMTPSSL => $LANG['setup'][652],
                            MAIL_SMTPTLS => $LANG['setup'][653]);
      Dropdown::showFromArray("smtp_mode", $mail_methods, array('value' => $CFG_GLPI["smtp_mode"]));
      echo "</td><td colspan='2' align='center'>";
      echo "<input class='submit' type='submit' name='test_smtp_send'
                                                            value=\"".$LANG['setup'][229]."\">";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][232] . "&nbsp;:</td>";
      echo "<td><input type='text' name='smtp_host' size='40' value='".$CFG_GLPI["smtp_host"]."'>";
      echo "</td>";
      echo "<td >" . $LANG['setup'][234] . "&nbsp;:</td>";
      echo "<td><input type='text' name='smtp_username' size='40' value=\"" .
                 $CFG_GLPI["smtp_username"] . "\"></td></tr>";

      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][175] . "&nbsp;:</td>";
      echo "<td><input type='text' name='smtp_port' size='5' value='".$CFG_GLPI["smtp_port"]."'>";
      echo "</td>";
      echo "<td >" . $LANG['setup'][235] . "&nbsp;:</td>";
      echo "<td><input type='password' name='smtp_password' size='40' value='' autocomplete='off'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
      echo "<input class='submit' type='submit' name='update' value='".$LANG['buttons'][2]."'>";
      echo "</td></tr>";
      echo "</table></form>";
   }

   function showFormAlerts($target) {
      global $LANG,$CFG_GLPI;

      echo "<form action='$target' method='post'>";
      echo "<input type='hidden' name='id' value='1'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_1'><td colspan='4' class='center'>";
      echo "<strong>" . $LANG['common'][41] . "</strong></td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td >" . $LANG['setup'][246] . "&nbsp;:</td><td>";
      Alert::dropdownYesNo(array('name'=>"use_contracts_alert",
                                 'value'=>$CFG_GLPI["use_contracts_alert"]));
      echo "</td>";
      echo "<td>" . $LANG['setup'][46] . "&nbsp;:</td><td>";
      Contract::dropdownAlert("default_contract_alert", $CFG_GLPI["default_contract_alert"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][247] . "&nbsp;:</td><td>";
      Alert::dropdownYesNo(array('name'=>"use_infocoms_alert",
                                 'value'=>$CFG_GLPI["use_infocoms_alert"]));
      echo "</td>";
      echo "<td>" . $LANG['setup'][46]."&nbsp;:</td><td>";
      echo "<select name=\"default_infocom_alert\">";
      echo "<option value=\"0\" " . ($CFG_GLPI["default_infocom_alert"] == 0 ? " selected " : "") .
             " >-----</option>";
      echo "<option value=\"" . pow(2, Alert::END) . "\" " .
             ($CFG_GLPI["default_infocom_alert"] == pow(2, Alert::END) ? " selected " : "") . " >" .
             $LANG['financial'][80] . " </option>";
      echo "</select>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][264] . "&nbsp;:</td><td>";
      Alert::dropdownYesNo(array('name'=>"use_licenses_alert",
                                 'value'=>$CFG_GLPI["use_licenses_alert"]));
      echo "</td>";
      echo "<td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td >" . $LANG['setup'][707] . "&nbsp;:</td><td>";
      Alert::dropdownIntegerNever('use_reservations_alert',
                                  $CFG_GLPI["use_reservations_alert"],
                                  array('max'=>99));
      echo "&nbsp;".$LANG['job'][21]."</td>";
      echo "<td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][708] . "&nbsp;:</td><td>";
      Alert::dropdownIntegerNever('notclosed_delay',
                                  $CFG_GLPI["notclosed_delay"],
                                  array('max'=>99));
      echo "&nbsp;".$LANG['stats'][31]."</td>";
      echo "<td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][245] . " : " . $LANG['setup'][244] . "&nbsp;:</td><td>";
      Alert::dropdown(array('name'  => 'cartridges_alert_repeat',
                            'value' => $CFG_GLPI["cartridges_alert_repeat"]));
      echo "</td>";
      echo "<td>" . $LANG['setup'][115] . "&nbsp;:</td><td>";
      Dropdown::showInteger('default_alarm_threshold', $CFG_GLPI["default_alarm_threshold"], -1, 100);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td >" . $LANG['setup'][245] . " : " . $LANG['setup'][243] . "&nbsp;:</td><td>";
      Alert::dropdown(array('name'  => 'consumables_alert_repeat',
                            'value' => $CFG_GLPI["consumables_alert_repeat"]));
      echo "</td><td colspan='2'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center' colspan='4'>";
      echo "<input class='submit' type='submit' name='update' value='".$LANG['buttons'][2]."'>";
      echo "</td></tr>";
      echo "</table></form>";
   }

}
?>