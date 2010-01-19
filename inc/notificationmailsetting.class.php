<?php
/*
 * @version $Id: mailing.class.php 10038 2010-01-05 13:34:15Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/**
 *  NotificationMail class extends
 */
class NotificationMailSetting extends CommonDBTM {

   var $table = 'glpi_configs';

   function defineTabs($ID,$withtemplate){
      global $LANG;

      $tabs[1] = $LANG['common'][12];
      if ($ID > 0) {
         $tabs[2] = $LANG['setup'][660];
         $tabs[3] = $LANG['setup'][242];
         $tabs[4] = $LANG['mailing'][32];
      }

      return $tabs;
   }

   /**
    * Print the mailing config form
    *
    *@param $target filename : where to go when done.
    *@param $tabs integer : ID of the tab to display
    *
    *@return Nothing (display)
    *
   **/
   function showForm($target,$tabs) {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("config", "w")) {
         return false;
      }

      $this->showTabs(1,'');
      $this->showFormHeader($target,1,'');
      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][202] . "</td><td>";
      Dropdown::showYesNo("use_mailing", $CFG_GLPI["use_mailing"]);
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][203] . "</td>";
      echo "<td><input type=\"text\" name=\"admin_email\" size='40' value=\"" .
                 $CFG_GLPI["admin_email"] . "\">";
      if (!NotificationMail::isUserAddressValid($CFG_GLPI["admin_email"])) {
          echo "<span class='red'>&nbsp;".$LANG['mailing'][110]."</span>";
      }
      echo " </td></tr>";
      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][207] . "</td>";
      echo "<td><input type=\"text\" name=\"admin_reply\" size='40' value=\"" .
                 $CFG_GLPI["admin_reply"] . "\">";
      if (!NotificationMail::isUserAddressValid($CFG_GLPI["admin_reply"])) {
         echo "<span class='red'>&nbsp;".$LANG['mailing'][110]."</span>";
      }
      echo " </td></tr>";
      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][204] . "</td>";
      echo "<td><textarea cols='60' rows='3' name=\"mailing_signature\" >".
                 $CFG_GLPI["mailing_signature"]."</textarea></td></tr>";
      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][226] . "</td><td>";
      Dropdown::showYesNo("show_link_in_mail", $CFG_GLPI["show_link_in_mail"]);
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][227] . "</td>";
      echo "<td><input type=\"text\" name=\"url_base\" size='40' value=\"" .
                  $CFG_GLPI["url_base"] . "\"> </td></tr>";
      if (!function_exists('mail')) {
          echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
          echo "<span class='red'>" . $LANG['setup'][217] . " : </span>";
          echo "<span>" . $LANG['setup'][218] . "</span></td></tr>";
      }
/*            break;

         case 2 :
            $profiles[USER_MAILING_TYPE . "_" . ADMIN_MAILING] = $LANG['setup'][237];
            $profiles[USER_MAILING_TYPE . "_" . ADMIN_ENTITY_MAILING] = $LANG['setup'][237]." ".
                                                                        $LANG['entity'][0];
            $profiles[USER_MAILING_TYPE . "_" . TECH_MAILING] = $LANG['common'][10];
            $profiles[USER_MAILING_TYPE . "_" . AUTHOR_MAILING] = $LANG['job'][4];
            $profiles[USER_MAILING_TYPE . "_" . RECIPIENT_MAILING] = $LANG['job'][3];
            $profiles[USER_MAILING_TYPE . "_" . USER_MAILING] = $LANG['common'][34] . " " .
                                                                $LANG['common'][1];
            $profiles[USER_MAILING_TYPE . "_" . ASSIGN_MAILING] = $LANG['setup'][239];
            $profiles[USER_MAILING_TYPE . "_" . ASSIGN_ENT_MAILING] = $LANG['financial'][26];
            $profiles[USER_MAILING_TYPE . "_" . ASSIGN_GROUP_MAILING] = $LANG['setup'][248];
            $profiles[USER_MAILING_TYPE . "_" .
                      SUPERVISOR_ASSIGN_GROUP_MAILING] = $LANG['common'][64]." ".$LANG['setup'][248];
            $profiles[USER_MAILING_TYPE . "_" .
                      SUPERVISOR_AUTHOR_GROUP_MAILING] = $LANG['common'][64]." ".$LANG['setup'][249];
            asort($profiles);
            $query = "SELECT `id`, `name`
                      FROM `glpi_profiles`
                      ORDER BY `name`";
            $result = $DB->query($query);
            while ($data = $DB->fetch_assoc($result)) {
               $profiles[PROFILE_MAILING_TYPE ."_" . $data["id"]] = $LANG['profiles'][22] . " " .
                                                                    $data["name"];
            }
            $query = "SELECT `id`, `name`
                      FROM `glpi_groups`
                      ORDER BY `name`";
            $result = $DB->query($query);
            while ($data = $DB->fetch_assoc($result)) {
               $profiles[GROUP_MAILING_TYPE ."_" . $data["id"]] = $LANG['common'][35] . " " .
                                                                  $data["name"];
            }
            echo "<div class='center'>";
            echo "<input type='hidden' name='update_notifications' value='1'>";
            // ADMIN
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='3'>" . $LANG['setup'][211] . "</th></tr>";
            echo "<tr class='tab_bg_2'>";
            showFormMailingType("new", $profiles);
            echo "</tr>";
            echo "<tr><th colspan='3'>" . $LANG['setup'][212] . "</th></tr>";
            echo "<tr class='tab_bg_1'>";
            showFormMailingType("followup", $profiles);
            echo "</tr>";
            echo "<tr class='tab_bg_2'><th colspan='3'>" . $LANG['setup'][213] . "</th></tr>";
            echo "<tr class='tab_bg_2'>";
            showFormMailingType("finish", $profiles);
            echo "</tr>";
            echo "<tr class='tab_bg_2'><th colspan='3'>" . $LANG['setup'][230] . "</th></tr>";
            echo "<tr class='tab_bg_1'>";
            $profiles[USER_MAILING_TYPE . "_" . OLD_ASSIGN_MAILING] = $LANG['setup'][236];
            ksort($profiles);
            showFormMailingType("update", $profiles);
            unset ($profiles[USER_MAILING_TYPE . "_" . OLD_ASSIGN_MAILING]);
            echo "</tr>";
            echo "<tr class='tab_bg_2'><th colspan='3'>" . $LANG['setup'][225] . "</th></tr>";
            echo "<tr class='tab_bg_2'>";
            unset ($profiles[USER_MAILING_TYPE . "_" . ASSIGN_MAILING]);
            unset ($profiles[USER_MAILING_TYPE . "_" . ASSIGN_ENT_MAILING]);
            unset ($profiles[USER_MAILING_TYPE . "_" . ASSIGN_GROUP_MAILING]);
            unset ($profiles[USER_MAILING_TYPE . "_" . SUPERVISOR_ASSIGN_GROUP_MAILING]);
            unset ($profiles[USER_MAILING_TYPE . "_" . SUPERVISOR_AUTHOR_GROUP_MAILING]);
            unset ($profiles[USER_MAILING_TYPE . "_" . RECIPIENT_MAILING]);

            showFormMailingType("resa", $profiles);
            echo "</tr></table></div>";
            break;

         case 3 :
            $profiles[USER_MAILING_TYPE . "_" . ADMIN_MAILING] = $LANG['setup'][237];
            $profiles[USER_MAILING_TYPE . "_" . ADMIN_ENTITY_MAILING] = $LANG['setup'][237]." ".
                                                                        $LANG['entity'][0];
            $query = "SELECT `id`, `name`
                      FROM `glpi_profiles`
                      ORDER BY `name`";
            $result = $DB->query($query);
            while ($data = $DB->fetch_assoc($result)) {
               $profiles[PROFILE_MAILING_TYPE ."_" . $data["id"]] = $LANG['profiles'][22] . " " .
                                                                    $data["name"];
            }
            $query = "SELECT `id`, `name`
                      FROM `glpi_groups`
                      ORDER BY `name`";
            $result = $DB->query($query);
            while ($data = $DB->fetch_assoc($result)) {
               $profiles[GROUP_MAILING_TYPE ."_" . $data["id"]] = $LANG['common'][35] . " " .
                                                                  $data["name"];
            }
            ksort($profiles);
            echo "<div class='center'>";
            echo "<input type='hidden' name='update_notifications' value='1'>";
            // ADMIN
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th colspan='3'>" . $LANG['setup'][243]."&nbsp;&nbsp;";
            echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_consumables\" value=\"" .
                   $LANG['buttons'][50] . "\">";
            echo "</th></tr>";
            echo "<tr class='tab_bg_2'>";
            showFormMailingType("alertconsumable", $profiles);
            echo "</tr>";
            echo "<tr><th colspan='3'>" . $LANG['setup'][244]."&nbsp;&nbsp;";
            echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_cartridges\" value=\"" .
                   $LANG['buttons'][50] . "\">";
            echo "</th></tr>";
            echo "<tr class='tab_bg_1'>";
            showFormMailingType("alertcartridge", $profiles);
            echo "</tr>";
            echo "<tr><th colspan='3'>" . $LANG['setup'][246]."&nbsp;&nbsp;";
            echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_contracts\" value=\"" .
                   $LANG['buttons'][50] . "\">";
            echo "</th></tr>";
            echo "<tr class='tab_bg_2'>";
            showFormMailingType("alertcontract", $profiles);
            echo "</tr>";
            echo "<tr><th colspan='3'>" . $LANG['setup'][247]."&nbsp;&nbsp;";
            echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_infocoms\" value=\"" .
                   $LANG['buttons'][50] . "\">";
            echo "</th></tr>";
            echo "<tr class='tab_bg_1'>";
            showFormMailingType("alertinfocom", $profiles);
            echo "</tr>";
            echo "<tr><th colspan='3'>" . $LANG['setup'][264]."&nbsp;&nbsp;";
            echo "<input class=\"submit\" type=\"submit\" name=\"test_cron_softwares\" value=\"" .
                   $LANG['buttons'][50] . "\">";
            echo "</th></tr>";
            echo "<tr class='tab_bg_1'>";
            showFormMailingType("alertlicense", $profiles);
            echo "</tr>";
            echo "</table></div>";
            break;
      //echo "</form>";
      */
      $this->showFormButtons(1,'',2,false);
      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";
   }

   function canCreate() {
      return haveRight('config', 'w');
   }

   function canView() {
      return haveRight('config', 'w');
   }

   function showFormMailServerConfig($target) {
      global $LANG,$CFG_GLPI;

      echo "<form action=\"$target\" method=\"post\">";
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<input type='hidden' name='id' value='1'>";
      echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][231] . "</td><td>&nbsp; ";
      $mail_methods=array(MAIL_MAIL=>$LANG['setup'][650],
                          MAIL_SMTP=>$LANG['setup'][651],
                          MAIL_SMTPSSL=>$LANG['setup'][652],
                          MAIL_SMTPTLS=>$LANG['setup'][653]);
      Dropdown::showFromArray("smtp_mode",$mail_methods,
                              array('value' => $CFG_GLPI["smtp_mode"]));
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][232] . "</td>";
      echo "<td><input type=\"text\" name=\"smtp_host\" size='40' value=\"" .
                 $CFG_GLPI["smtp_host"] . "\"></td></tr>";
      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][234] . "</td>";
      echo "<td><input type=\"text\" name=\"smtp_username\" size='40' value=\"" .
                 $CFG_GLPI["smtp_username"] . "\"></td></tr>";
      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][235] . "</td>";
      echo "<td><input type=\"password\" name=\"smtp_password\" size='40' value=\"\"></td></tr>";
      echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
      echo "<input class=\"submit\" type=\"submit\" name=\"update\" value=\"" .
             $LANG['buttons'][2] . "\">";
      echo "</td></tr>";
      echo "</table></div></form>";
   }

   function showFormTest($target) {
      global $LANG;
      echo "<form action=\"$target\" method=\"post\">";
      echo "<input type='hidden' name='id' value='1'>";
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>" . $LANG['setup'][229] . "</th></tr>";
      echo "<tr class='tab_bg_2'><td class='center'>";
      echo "<input class=\"submit\" type=\"submit\" name=\"test_smtp_send\" value=\"" .
             $LANG['buttons'][2] . "\">";
      echo " </td></tr></table></div></form>";

   }

   function showFormAlerts($target) {
      global $LANG,$CFG_GLPI;
      echo "<form action=\"$target\" method=\"post\">";
      echo "<input type='hidden' name='id' value='1'>";
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][245] . " " . $LANG['setup'][244] . "</td><td>";
      echo "<select name='cartridges_alert_repeat'> ";
      echo "<option value='0' " .
             ($CFG_GLPI["cartridges_alert_repeat"] == 0 ? "selected" : "") . " >" .
              $LANG['setup'][307] . "</option>";
      echo "<option value='" . DAY_TIMESTAMP . "' " .
             ($CFG_GLPI["cartridges_alert_repeat"] == DAY_TIMESTAMP ? "selected" : "") . " >" .
             $LANG['setup'][305] . "</option>";
      echo "<option value='" . WEEK_TIMESTAMP . "' " .
             ($CFG_GLPI["cartridges_alert_repeat"] == WEEK_TIMESTAMP ? "selected" : "") . " >" .
              $LANG['setup'][308] . "</option>";
      echo "<option value='" . MONTH_TIMESTAMP . "' " .
             ($CFG_GLPI["cartridges_alert_repeat"] == MONTH_TIMESTAMP ? "selected" : "") . " >" .
             $LANG['setup'][309] . "</option>";
      echo "</select>";
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td >" . $LANG['setup'][245] . " " . $LANG['setup'][243] . "</td><td>";
      echo "<select name='consumables_alert_repeat'> ";
      echo "<option value='0' " .
             ($CFG_GLPI["consumables_alert_repeat"] == 0 ? "selected" : "") . " >" .
             $LANG['setup'][307] . "</option>";
      echo "<option value='" . DAY_TIMESTAMP . "' " .
             ($CFG_GLPI["consumables_alert_repeat"] == DAY_TIMESTAMP ? "selected" : "")." >".
             $LANG['setup'][305] . "</option>";
      echo "<option value='" . WEEK_TIMESTAMP . "' " .
             ($CFG_GLPI["consumables_alert_repeat"] == WEEK_TIMESTAMP ? "selected" : "")." >".
             $LANG['setup'][308] . "</option>";
      echo "<option value='" . MONTH_TIMESTAMP . "' " .
             ($CFG_GLPI["consumables_alert_repeat"] == MONTH_TIMESTAMP ? "selected" : "")." >".
             $LANG['setup'][309] . "</option>";
      echo "</select>";
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'><td >" . $LANG['setup'][264] . "</td><td>";
      Dropdown::showYesNo("use_licenses_alert", $CFG_GLPI["use_licenses_alert"]);
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
      echo "<input class=\"submit\" type=\"submit\" name=\"update\" value=\"" .
             $LANG['buttons'][2] . "\">";
      echo "</td></tr>";
      echo "</table></div></form>";

   }
}

?>