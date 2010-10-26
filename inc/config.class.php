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
 *  Config class
 */
class Config extends CommonDBTM {

   // From CommonDBTM
   public $auto_message_on_action = false;

   static function getTypeName() {
      global $LANG;

      return $LANG['common'][12];
   }


   function defineTabs($options=array()) {
      global $LANG;

      $tabs[1] = $LANG['setup'][70];  // Display
      $tabs[2] = $LANG['setup'][48];    // Prefs
      $tabs[3] = $LANG['Menu'][38];  // Inventory
      $tabs[4] = $LANG['title'][24];   // Helpdesk
      $tabs[5] = $LANG['setup'][720];  // SysInfo

      if (DBConnection::isDBSlaveActive()) {
         $tabs[6]  = $LANG['setup'][800];  // Slave
      }

      return $tabs;
   }


   function showForm($ID, $options=array()) {

      $this->showTabs($options);
      $this->addDivForTabs();
   }


   /**
    * Prepare input datas for updating the item
    *
    * @param $input datas used to update the item
    *
    * @return the modified $input array
   **/
   function prepareInputForUpdate($input) {

      if (isset($input["smtp_password"]) && empty($input["smtp_password"])) {
         unset($input["smtp_password"]);
      }

      if (isset($input["proxy_password"]) && empty($input["proxy_password"])) {
         unset($input["proxy_password"]);
      }

      // Manage DB Slave process
      if (isset($input['_dbslave_status'])) {
         $already_active=DBConnection::isDBSlaveActive();

         if ($input['_dbslave_status']) {
            DBConnection::changeCronTaskStatus(true);

            if (!$already_active) {
               // Activate Slave from the "system" tab
               DBConnection::createDBSlaveConfig();

            } else if (isset($input["_dbreplicate_dbhost"])) {
               // Change parameter from the "replicate" tab
               DBConnection::saveDBSlaveConf($input["_dbreplicate_dbhost"],
                                             $input["_dbreplicate_dbuser"],
                                             $input["_dbreplicate_dbpassword"],
                                             $input["_dbreplicate_dbdefault"]);
            }
         }

         if (!$input['_dbslave_status'] && $already_active) {
            DBConnection::deleteDBSlaveConfig();
            DBConnection::changeCronTaskStatus(false);
         }
      }

      // Matrix for Impact / Urgence / Priority
      if (isset($input['_matrix'])) {
         $tab = array();

         for ($urgency=1 ; $urgency<=5 ; $urgency++) {
            for ($impact=1 ; $impact<=5 ; $impact++) {
               $priority = $input["_matrix_${urgency}_${impact}"];
               $tab[$urgency][$impact] = $priority;
            }
         }

         $input['priority_matrix'] = exportArrayToDB($tab);
         $input['urgency_mask']    = 0;
         $input['impact_mask']     = 0;

         for ($i=1 ; $i<=5 ; $i++) {
            if ($input["_urgency_${i}"]) {
               $input['urgency_mask'] += (1<<$i);
            }

            if ($input["_impact_${i}"]) {
               $input['impact_mask'] += (1<<$i);
            }
         }
      }
      return $input;
   }


   /**
    * Print the config form for display
    *
    * @return Nothing (display)
   **/
   function showFormDisplay() {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"".getItemTypeFormURL(__CLASS__)."\" method='post'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . $LANG['setup'][70] . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['setup'][118] . "&nbsp;:</td>";
      echo "<td colspan='3'>";
      echo "<textarea cols='70' rows='4' name='text_login'>".$CFG_GLPI["text_login"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['setup'][117] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("use_public_faq", $CFG_GLPI["use_public_faq"]);
      echo "</td><td>" . $LANG['setup'][407] . "&nbsp;:</td>";
      echo "<td><input size='22' type='text' name='helpdesk_doc_url' value='" .
                 $CFG_GLPI["helpdesk_doc_url"] . "'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][111]."&nbsp;:</td><td>";
      Dropdown::showInteger("list_limit_max", $CFG_GLPI["list_limit_max"], 5, 200, 5);
      echo "</td><td>" . $LANG['setup'][408] . "&nbsp;:</td>";
      echo "<td><input size='22' type='text' name='central_doc_url' value='" .
                 $CFG_GLPI["central_doc_url"] . "'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][112] . "&nbsp;:</td><td>";
      Dropdown::showInteger('cut', $CFG_GLPI["cut"], 50, 500, 50);
      echo "</td><td>" . $LANG['setup'][314] . "&nbsp;:</td><td>";
      Dropdown::showInteger('url_maxlength', $CFG_GLPI["url_maxlength"], 20, 80, 5);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td>" . $LANG['setup'][149] . "&nbsp;:</td><td>";
      Dropdown::showInteger("decimal_number", $CFG_GLPI["decimal_number"], 1, 4);
      echo "</td><td>" . $LANG['setup'][47]."&nbsp;:</td><td>";
      Dropdown::showFromArray("default_graphtype", array('png' => 'PNG',
                                                         'svg' => 'SVG'),
                              array('value' => $CFG_GLPI["default_graphtype"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>".$LANG['setup'][147]."</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][120] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("use_ajax", $CFG_GLPI["use_ajax"]);
      echo "</td><td colspan='2'></td>";
      echo "</tr>";

      if ($CFG_GLPI["use_ajax"]) {
         echo "<tr class='tab_bg_2'>";
         echo "<td>" . $LANG['setup'][123] . "&nbsp;:</td><td>";
         Dropdown::showInteger('ajax_limit_count', $CFG_GLPI["ajax_limit_count"], 1, 200, 1,
                               array(0 => $LANG['setup'][307]));
         echo "</td><td>" . $LANG['setup'][122] . "&nbsp;:</td><td>";
         Dropdown::showInteger('dropdown_max', $CFG_GLPI["dropdown_max"], 0, 200);
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td>" . $LANG['setup'][127] . "&nbsp;:</td><td>";
         Dropdown::showYesNo("use_ajax_autocompletion", $CFG_GLPI["use_ajax_autocompletion"]);
         echo "</td><td>" . $LANG['setup'][121] . "&nbsp;:</td>";
         echo "<td><input type='text' size='1' name='ajax_wildcard' value='" .
                    $CFG_GLPI["ajax_wildcard"] . "'></td>";
         echo "</tr>";
      }

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value='".$LANG['buttons'][2]."'>";
      echo "</td></tr>";

      echo "</table></div>";
      echo "</form>";
   }


   /**
    * Print the config form for restrictions
    *
    * @return Nothing (display)
   **/
   function showFormInventory() {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"".getItemTypeFormURL(__CLASS__)."\" method='post'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . $LANG['Menu'][38] . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['setup'][133] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("use_ocs_mode", $CFG_GLPI["use_ocs_mode"]);
      echo "</td><td> " . $LANG['setup'][271] . "&nbsp;:</td>";
      echo "<td>";
      $this->dropdownGlobalManagement ("monitors_management_restrict",
                                       $CFG_GLPI["monitors_management_restrict"]);
      echo "</td</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][8] . "&nbsp;:</td><td>";
      Dropdown::ShowYesNo('auto_create_infocoms', $CFG_GLPI["auto_create_infocoms"]);
      echo "</td></td><td> " . $LANG['setup'][272] . "&nbsp;:</td><td>";
      $this->dropdownGlobalManagement ("peripherals_management_restrict",
                                       $CFG_GLPI["peripherals_management_restrict"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>" . $LANG['rulesengine'][86] . "&nbsp;:</td><td>";
      Dropdown::show('SoftwareCategory',
                     array('value' => $CFG_GLPI["softwarecategories_id_ondelete"],
                           'name'  => "softwarecategories_id_ondelete"));
      echo "</td><td> " . $LANG['setup'][273] . "&nbsp;:</td><td>";
      $this->dropdownGlobalManagement ("phones_management_restrict",
                                       $CFG_GLPI["phones_management_restrict"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][221] . "&nbsp;:</td><td>";
      showDateFormItem("date_tax", $CFG_GLPI["date_tax"], false);
      echo "</td><td> " . $LANG['setup'][275] . "&nbsp;:</td><td>";
      $this->dropdownGlobalManagement("printers_management_restrict",
                                      $CFG_GLPI["printers_management_restrict"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][360] . "&nbsp;:</td><td>";
      $tab = array(0 => $LANG['common'][59],
                   1 => $LANG['entity'][8]);
      Dropdown::showFromArray('use_autoname_by_entity', $tab,
                              array('value' => $CFG_GLPI["use_autoname_by_entity"]));
      echo "</td></td><td colspan='2'>&nbsp;";
      echo "</td></tr>";

      echo "</table>";

      echo "<br><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='6'>".$LANG['setup'][280]." (".$LANG['peripherals'][32].")</th></tr>";

      echo "<tr><th>&nbsp;</th>";
      echo "<th>" . $LANG['common'][92] . "</th>";
      echo "<th>" . $LANG['common'][34] . "</th>";
      echo "<th>" . $LANG['common'][35] . "</th>";
      echo "<th>" . $LANG['common'][15] . "</th>";
      echo "<th>" . $LANG['state'][0] . "</th>";
      echo "</tr>";

      $fields = array("contact", "user", "group", "location");
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['setup'][281] . "&nbsp;:</td>";
      $values[0] = $LANG['setup'][285];
      $values[1] = $LANG['setup'][283];

      foreach ($fields as $field) {
         echo "<td>";
         $fieldname = "is_".$field."_autoupdate";
         Dropdown::showFromArray($fieldname, $values, array('name' => $CFG_GLPI[$fieldname]));
         echo "</td>";
      }

      echo "<td>";
      State::dropdownBehaviour("state_autoupdate_mode", $LANG['setup'][197],
                               $CFG_GLPI["state_autoupdate_mode"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['setup'][282] . "&nbsp;:</td>";
      $values[0] = $LANG['setup'][286];
      $values[1] = $LANG['setup'][284];

      foreach ($fields as $field) {
         echo "<td>";
         $fieldname = "is_".$field."_autoclean";
         Dropdown::showFromArray($fieldname, $values, array('name' => $CFG_GLPI[$fieldname]));
         echo "</td>";
      }

      echo "<td>";
      State::dropdownBehaviour("state_autoclean_mode", $LANG['setup'][196],
                               $CFG_GLPI["state_autoclean_mode"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='6' class='center'>";
      echo "<input type='submit' name='update' class='submit' value='".$LANG['buttons'][2]."'>";
      echo "</td></tr>";

      echo "</table></div>";
      echo "</form>";
   }


   /**
    * Print the config form for restrictions
    *
    * @return Nothing (display)
   **/
   function showFormAuthentication() {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"".getItemTypeFormURL(__CLASS__)."\" method='post'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . $LANG['login'][10] . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['setup'][124] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("is_users_auto_add", $CFG_GLPI["is_users_auto_add"]);
      echo "</td><td> " . $LANG['setup'][613] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("use_noright_users_add", $CFG_GLPI["use_noright_users_add"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['ldap'][45] . "&nbsp;:</td><td>";
      AuthLDap::dropdownUserDeletedActions($CFG_GLPI["user_deleted_ldap"]);
      echo "</td><td> " . $LANG['setup'][186] . "&nbsp;:</td><td>";
      Dropdown::showGMT("time_offset", $CFG_GLPI["time_offset"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update_auth' class='submit' value='".$LANG['buttons'][2]."'>";
      echo "</td></tr>";

      echo "</table></div>";
      echo "</form>";
   }


   /**
    * Print the config form for slave DB
    *
    * @return Nothing (display)
   **/
   function showFormDBSlave() {
      global $DB, $LANG, $CFG_GLPI, $DBSlave;

      if (!haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"".getItemTypeFormURL(__CLASS__)."\" method='post'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";
      echo "<input type='hidden' name='_dbslave_status' value='1'>";
      echo "<table class='tab_cadre_fixe'>";
      $active = DBConnection::isDBSlaveActive();

      echo "<tr class='tab_bg_2'><th colspan='4'>" . $LANG['setup'][800] . "</th></tr>";
      $DBSlave = DBConnection::getDBSlaveConf();

      if (is_array($DBSlave->dbhost)) {
         $host = implode(' ', $DBSlave->dbhost);
      } else {
         $host = $DBSlave->dbhost;
      }
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['install'][30] . "&nbsp;:</td>";
      echo "<td><input type='text' name='_dbreplicate_dbhost' size='40' value='$host'></td>";
      echo "<td>" . $LANG['setup'][802] . "&nbsp;:</td>";
      echo "<td><input type='text' name='_dbreplicate_dbdefault' value='".$DBSlave->dbdefault."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['install'][31] . "&nbsp;:</td>";
      echo "<td><input type='text' name='_dbreplicate_dbuser' value='".$DBSlave->dbuser."'></td>";
      echo "<td>" . $LANG['install'][32] . "&nbsp;:</td>";
      echo "<td><input type='password' name='_dbreplicate_dbpassword' value='".
                 $DBSlave->dbpassword."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][804] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("use_slave_for_search", $CFG_GLPI["use_slave_for_search"]);
      echo "<td colspan='2'>&nbsp;</td>";
      echo "</tr>";

      if ($DBSlave->connected && !$DB->isSlave()) {
         echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
         DBConnection::showAllReplicateDelay();
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value='".$LANG['buttons'][2]."'>";
      echo "</td></tr>";

      echo "</table></div>";
      echo "</form>";
   }


   /**
    * Print the config form for connections
    *
    * @return Nothing (display)
   **/
   function showFormHelpdesk() {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"".getItemTypeFormURL(__CLASS__)."\" method='post'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . $LANG['title'][24] . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][612] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("is_ticket_category_mandatory", $CFG_GLPI["is_ticket_category_mandatory"]);
      echo "</td><td>" . $LANG['setup'][148] . "&nbsp;:</td><td>";
      Dropdown::showInteger('time_step', $CFG_GLPI["time_step"], 30, 60, 30, array(5  => 5,
                                                                                   10 => 10,
                                                                                   15 => 15,
                                                                                   20 => 20));
      echo "&nbsp;" . $LANG['job'][22];
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][610] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("is_ticket_title_mandatory", $CFG_GLPI["is_ticket_title_mandatory"]);
      echo "</td><td>" . $LANG['setup'][223] . "&nbsp;:</td><td>";
      Dropdown::showHours('planning_begin', $CFG_GLPI["planning_begin"]);
      echo "&nbsp;->&nbsp;";
      Dropdown::showHours('planning_end', $CFG_GLPI["planning_end"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['setup'][611] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("is_ticket_content_mandatory", $CFG_GLPI["is_ticket_content_mandatory"]);
      echo "</td><td>".$LANG['mailgate'][7] . " (".$LANG['setup'][46].")&nbsp;:</td><td>";
      MailCollector::showMaxFilesize('default_mailcollector_filesize_max',
                                     $CFG_GLPI["default_mailcollector_filesize_max"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['setup'][52] . "&nbsp;:</td><td>";
      $autoassign = array(NO_AUTO_ASSIGN                => $LANG['choice'][0],
                          AUTO_ASSIGN_HARDWARE_CATEGORY => $LANG['setup'][51],
                          AUTO_ASSIGN_CATEGORY_HARDWARE => $LANG['setup'][50]);
      Dropdown::showFromArray('auto_assign_mode', $autoassign,
                              array('value' => $CFG_GLPI["auto_assign_mode"]));
      echo "</td><td>" . $LANG['setup'][409] . "&nbsp;:</td><td>";
      Dropdown::show('DocumentCategory',
                     array('value' => $CFG_GLPI["documentcategories_id_forticket"],
                           'name'  => "documentcategories_id_forticket"));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['entity'][18] . "&nbsp;:</td><td>";
      Dropdown::showInteger('autoclose_delay', $CFG_GLPI['autoclose_delay'], 1, 99, 1,
                            array(0 => $LANG['setup'][307]));
      echo "&nbsp;".$LANG['stats'][31]."</td>";
      echo "<td>" . $LANG['setup'][608] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("default_software_helpdesk_visible",
                          $CFG_GLPI["default_software_helpdesk_visible"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['tracking'][37] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("keep_tickets_on_delete", $CFG_GLPI["keep_tickets_on_delete"]);
      echo "</td><td>" . $LANG['setup'][219] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("use_anonymous_helpdesk", $CFG_GLPI["use_anonymous_helpdesk"]);
      echo "</td></tr>";

      echo "<tr><th colspan='4'>".$LANG['entity'][19]."</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['entity'][20]."&nbsp;:&nbsp;</td><td>";
      Dropdown::showInteger('inquest_delay', $CFG_GLPI['inquest_delay'], 0, 90, 1);
      echo "&nbsp;".$LANG['stats'][31]."</td>";
      echo "<td>".$LANG['entity'][21]."&nbsp;:&nbsp;</td><td>";
      Dropdown::showInteger('inquest_rate', $CFG_GLPI['inquest_rate'], 10, 100, 10,
                            array(0 => $LANG['crontask'][31]));
      echo "&nbsp;%</td></tr>";

      echo "</table><br>";

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='7'>" . $LANG['help'][1];
      echo "<input type='hidden' name='_matrix' value='1'></th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='b right' colspan='2'>".$LANG['joblist'][30]."&nbsp;:</td>";

      for ($impact=5, $msg=47 ; $impact>=1 ; $impact--, $msg++) {
         echo "<td>".$LANG['help'][$msg]."&nbsp;: ";

         if ($impact==3) {
            $isimpact[3] = 1;
            echo "<input type='hidden' name='_impact_3' value='1'>";

         } else {
            $isimpact[$impact] = (($CFG_GLPI['impact_mask']&(1<<$impact)) >0);
            Dropdown::showYesNo("_impact_${impact}", $isimpact[$impact]);
         }
         echo "</td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='b' colspan='2'>".$LANG['joblist'][29]."&nbsp;:</td>";

      for ($impact=5, $msg=47 ; $impact>=1 ; $impact--, $msg++) {
         echo "<td>&nbsp;</td>";
      }
      echo "</tr>";

      for ($urgency=5, $msg=42 ; $urgency>=1 ; $urgency--, $msg++) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['help'][$msg]."&nbsp;:</td>";
         echo "<td>";

         if ($urgency==3) {
            $isurgency[3] = 1;
            echo "<input type='hidden' name='_urgency_3' value='1'>";

         } else {
            $isurgency[$urgency] = (($CFG_GLPI['urgency_mask']&(1<<$urgency)) >0);
            Dropdown::showYesNo("_urgency_${urgency}", $isurgency[$urgency]);
         }
         echo "</td>";

         for ($impact=5 ; $impact>=1 ; $impact--) {
            $pri = round(($urgency+$impact)/2);

            if (isset($CFG_GLPI['priority_matrix'][$urgency][$impact])) {
               $pri = $CFG_GLPI['priority_matrix'][$urgency][$impact];
            }


            if ($isurgency[$urgency] && $isimpact[$impact]) {
               $bgcolor=$_SESSION["glpipriority_$pri"];
               echo "<td bgcolor='$bgcolor'>";
               Ticket::dropdownPriority("_matrix_${urgency}_${impact}",$pri);
               echo "</td>";
            } else {
               echo "<td><input type='hidden' name='_matrix_${urgency}_${impact}' value='$pri'></td>";
            }
         }
         echo "</tr>\n";
      }

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='7' class='center'>";
      echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";
      echo "<input type='submit' name='update' class='submit' value='".$LANG['buttons'][2]."'>";
      echo "</td></tr>";

      echo "</table></div>";
      echo "</form>";
   }


   /**
    * Print the config form for default user prefs
    *
    * @param $data array containing datas
    * (CFG_GLPI for global config / glpi_users fields for user prefs)
    *
    * @return Nothing (display)
   **/
   function showFormUserPrefs($data=array()) {
      global $DB, $LANG, $CFG_GLPI;

      $oncentral = ($_SESSION["glpiactiveprofile"]["interface"]=="central");
      $userpref  = false;
      $url       = getItemTypeFormURL(__CLASS__);

      if (isset($data['last_login'])) {
         $userpref = true;
         $url      = $CFG_GLPI['root_doc']."/front/preference.php";
      }

      echo "<form name='form' action='$url' method='post'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<input type='hidden' name='id' value='" . $data["id"] . "'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . $LANG['setup'][6] . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][111]."&nbsp;:</td><td>";
      // Limit using global config
      Dropdown::showInteger('list_limit',
                            ($data['list_limit']<$CFG_GLPI['list_limit_max']
                             ? $data['list_limit'] : $CFG_GLPI['list_limit_max']),
                            5, $CFG_GLPI['list_limit_max'], 5);
      echo "</td><td>" . $LANG['setup'][128] ."&nbsp;:</td>";
      echo "<td>";
      $date_formats = array(0 => $LANG['calendar'][0],
                            1 => $LANG['calendar'][1],
                            2 => $LANG['calendar'][2]);
      Dropdown::showFromArray('date_format', $date_formats, array('value' => $data["date_format"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      if ($oncentral) {
         echo "<td>" . $LANG['setup'][112] . "&nbsp;:</td><td>";
         Dropdown::showInteger('dropdown_chars_limit', $data["dropdown_chars_limit"], 20, 100);
         echo "</td>";
       } else {
        echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "<td>" . $LANG['setup'][150] . "&nbsp;:</td>";
      $values = array(0 => '1 234.56',
                      1 => '1,234.56',
                      2 => '1 234,56');
      echo "<td>";
      Dropdown::showFromArray('number_format', $values, array('value' => $data["number_format"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      if ($oncentral) {
         echo "<td>" . $LANG['setup'][132] . "&nbsp;:</td><td>";
         Dropdown::showYesNo('use_flat_dropdowntree', $data["use_flat_dropdowntree"]);
         echo "</td>";
      } else {
        echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "<td>".$LANG['setup'][10]."&nbsp;:</td><td>";
      $values = array (REALNAME_BEFORE  => $LANG['common'][48]." ".$LANG['common'][43],
                       FIRSTNAME_BEFORE => $LANG['common'][43]." ".$LANG['common'][48]);
      Dropdown::showFromArray('names_format', $values, array('value' => $data["names_format"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='2'></td>";
      echo "<td>".$LANG['setup'][7]."&nbsp;:</td><td>";
      $values = array(';' =>';',
                      ',' =>',');
      Dropdown::showFromArray('csv_delimiter', $values, array('value' => $data["csv_delimiter"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      if ($oncentral) {
         echo "<td>" . $LANG['setup'][129] . "&nbsp;:</td><td>";
         Dropdown::showYesNo("is_ids_visible", $data["is_ids_visible"]);
         echo "</td>";
      } else {
         echo "<td colspan='2'></td>";
      }
      echo "<td>" . ($userpref?$LANG['setup'][41]:$LANG['setup'][113]) . "&nbsp;:</td><td>";
      if (haveRight("config","w") || ! GLPI_DEMO_MODE) {
         Dropdown::showLanguages("language", array('value' => $data["language"]));
      } else {
         echo "&nbsp;";
      }
      echo "</td></tr>";

      if ($oncentral) {
         echo "<tr class='tab_bg_1'><th colspan='4'>".$LANG['title'][24]."</th></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td>".$LANG['setup'][39]."&nbsp;:</td><td>";
         Dropdown::showYesNo("followup_private", $data["followup_private"]);
         echo "</td><td> " . $LANG['setup'][110] . "&nbsp;:</td><td>";
         Dropdown::showYesNo("show_jobs_at_login", $data["show_jobs_at_login"]);
         echo " </td></tr>";

         echo "<tr class='tab_bg_2'><td>" . $LANG['setup'][40] . "&nbsp;:</td><td>";
         Dropdown::showYesNo("task_private", $data["task_private"]);
         echo "</td><td> " . $LANG['job'][44] . "&nbsp;:</td><td>";
         Dropdown::show('RequestType', array('value' => $data["default_requesttypes_id"],
                                             'name'  => "default_requesttypes_id"));
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td>" . $LANG['setup'][114] . "&nbsp;:</td>";
         echo "<td colspan='3'>";

         echo "<table><tr>";
         echo "<td bgcolor='" . $data["priority_1"] . "'>1&nbsp;:&nbsp;";
         echo "<input type='text' name='priority_1' size='7' value='".$data["priority_1"]."'></td>";
         echo "<td bgcolor='" . $data["priority_2"] . "'>2&nbsp;:&nbsp;";
         echo "<input type='text' name='priority_2' size='7' value='".$data["priority_2"]."'></td>";
         echo "<td bgcolor='" . $data["priority_3"] . "'>3&nbsp;:&nbsp;";
         echo "<input type='text' name='priority_3' size='7' value='".$data["priority_3"]."'></td>";
         echo "<td bgcolor='" . $data["priority_4"] . "'>4&nbsp;:&nbsp;";
         echo "<input type='text' name='priority_4' size='7' value='".$data["priority_4"]."'></td>";
         echo "<td bgcolor='" . $data["priority_5"] . "'>5&nbsp;:&nbsp;";
         echo "<input type='text' name='priority_5' size='7' value='".$data["priority_5"]."'></td>";
         echo "<td bgcolor='" . $data["priority_6"] . "'>6&nbsp;:&nbsp;";
         echo "<input type='text' name='priority_6' size='7' value='".$data["priority_6"]."'></td>";
         echo "</tr></table>";

         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><th colspan='4'>". $LANG['softwarecategories'][5] ."</th></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td>" . $LANG['softwarecategories'][4]."&nbsp;:</td><td>";
         Dropdown::showYesNo("is_categorized_soft_expanded", $data["is_categorized_soft_expanded"]);
         echo "</td><td>" . $LANG['softwarecategories'][3] . "&nbsp;:</td><td>";
         Dropdown::showYesNo("is_not_categorized_soft_expanded",
                             $data["is_not_categorized_soft_expanded"]);
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value='".$LANG['buttons'][2]."'>";
      echo "</td></tr>";

      echo "</table></div>";
      echo "</form>";
   }


   /**
    * Display a HTML report about systeme information / configuration
    *
   **/
   function showSystemInformations () {
      global $DB,$LANG,$CFG_GLPI;

      echo "<div class='center' id='tabsbody'>";
      echo "<form name='form' action=\"".getItemTypeFormURL(__CLASS__)."\" method='post'>";
      echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . $LANG['setup'][70] . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][102] . " &nbsp;:</td><td>";
      $values[1] = $LANG['setup'][103];
      $values[2] = $LANG['setup'][104];
      $values[3] = $LANG['setup'][105];
      $values[4] = $LANG['setup'][106];
      $values[5] = $LANG['setup'][107];
      Dropdown::showFromArray('event_loglevel', $values,
                              array('value' => $CFG_GLPI["event_loglevel"]));
      echo "</td><td>".$LANG['setup'][101]."&nbsp;:</td><td>";
      Dropdown::showInteger('cron_limit', $CFG_GLPI["cron_limit"], 1, 30);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['setup'][185] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("use_log_in_files", $CFG_GLPI["use_log_in_files"]);
      echo "</td><td> " . $LANG['setup'][801] . "&nbsp;:</td><td>";
      $active = DBConnection::isDBSlaveActive();
      Dropdown::showYesNo("_dbslave_status", $active);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4' class='center b'>".$LANG['setup'][306].' - '.$LANG['setup'][400]."</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['common'][52] . "&nbsp;:</td>";
      echo "<td><input type='text' name='proxy_name' value='".$CFG_GLPI["proxy_name"]."'></td>";
      echo "<td>" . $LANG['setup'][175] . "&nbsp;:</td>";
      echo "<td><input type='text' name='proxy_port' value='".$CFG_GLPI["proxy_port"]."'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['login'][6] . "&nbsp;:</td>";
      echo "<td><input type='text' name='proxy_user' value='".$CFG_GLPI["proxy_user"]."'></td>";
      echo "<td>" . $LANG['login'][7] . "&nbsp;:</td>";
      echo "<td><input type='password' name='proxy_password' value='' autocomplete='off'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value='".$LANG['buttons'][2]."'></td>";
      echo "</tr>";

      echo "</table>";
      echo "</form>";

      $width = 128;

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>" . $LANG['setup'][721] . "</th></tr>";

      echo "<tr class='tab_bg_1'><td><pre>[code]\n&nbsp;\n";
      $oldlang = $_SESSION['glpilanguage'];
      loadLanguage('en_GB');
      echo "GLPI ".$CFG_GLPI['version']." (".$CFG_GLPI['root_doc']." => ".
            dirname(dirname($_SERVER["SCRIPT_FILENAME"])).")\n";
      echo "\n</pre></td></tr>";

      echo "<tr><th>" . $LANG['common'][52] . "</th></tr>\n";

      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";
      echo wordwrap($LANG['setup'][5]."&nbsp;: ".php_uname()."\n", $width, "\n\t");
      $exts = get_loaded_extensions();
      sort($exts);
      echo wordwrap("PHP ".phpversion()." (".implode(', ',$exts).")\n", $width, "\n\t");
      $msg = $LANG['common'][12].": ";

      foreach (array('memory_limit', 'max_execution_time', 'safe_mode',
                     'session.save_handler') as $key) {
         $msg .= $key.'="'.ini_get($key).'" ';
      }
      echo wordwrap($msg."\n", $width, "\n\t");

      $msg = $LANG['Menu'][4].": ";
      if (isset($_SERVER["SERVER_SOFTWARE"])) {
         $msg .= $_SERVER["SERVER_SOFTWARE"];
      }
      if (isset($_SERVER["SERVER_SIGNATURE"])) {
         $msg .= ' ('.html_clean($_SERVER["SERVER_SIGNATURE"]).')';
      }
      echo wordwrap($msg."\n", $width, "\n\t");

      if (isset($_SERVER["HTTP_USER_AGENT"])) {
         echo "\t" . $_SERVER["HTTP_USER_AGENT"] . "\n";
      }

      $version = "???";
      foreach ($DB->request('SELECT VERSION() as ver') as $data) {
         $version = $data['ver'];
      }
      echo "MySQL: $version (".$DB->dbuser."@".$DB->dbhost."/".$DB->dbdefault.")\n";

      foreach ($CFG_GLPI["systeminformations_type"] as $type) {
         $tmp = new $type;
         $tmp->showSystemInformations($width);
      }
      loadLanguage($oldlang);

      echo "\n</pre></td></tr>";

      echo "<tr class='tab_bg_1'><td>[/code]\n</td></tr>";

      echo "<tr class='tab_bg_2'><th>" . $LANG['setup'][722] . "</th></tr>\n";

      echo "</table></div>\n";
   }

   /**
    * Dropdown for global management config
    *
    * @param $name select name
    * @param $value default value
    */
   static function dropdownGlobalManagement($name,$value) {
      global $LANG;

      $choices[0] = $LANG['choice'][1]." - ". $LANG['setup'][274]. " : ".  $LANG['peripherals'][32];
      $choices[1] = $LANG['choice'][1]." - ". $LANG['setup'][274]. " : ". $LANG['peripherals'][31];
      $choices[2] = $LANG['choice'][0];
      Dropdown::showFromArray($name,$choices,array('value'=>$value));
   }


   /**
    * Get language in GLPI associated with the value coming from LDAP
    * Value can be, for example : English, en_EN or en
    *
    * @param $lang : the value coming from LDAP
    *
    * @return the locale's php page in GLPI or '' is no language associated with the value
   **/
   static function getLanguage($lang) {
      global $CFG_GLPI;

      // Search in order : ID or extjs dico or tinymce dico / native lang / english name / extjs dico / tinymce dico
      // ID  or extjs dico or tinymce dico
      foreach ($CFG_GLPI["languages"] as $ID => $language) {
         if (strcasecmp($lang,$ID) == 0
             || strcasecmp($lang,$language[2]) == 0
             || strcasecmp($lang,$language[3]) == 0) {
            return $ID;
         }
      }

      // native lang
      foreach ($CFG_GLPI["languages"] as $ID => $language) {
         if (strcasecmp($lang,$language[0]) == 0) {
            return $ID;
         }
      }

      // english lang name
      foreach ($CFG_GLPI["languages"] as $ID => $language) {
         if (strcasecmp($lang,$language[4]) == 0) {
            return $ID;
         }
      }

      return "";
   }


   static function detectRootDoc() {
      global $CFG_GLPI;

      if (!isset($CFG_GLPI["root_doc"])) {
         if ( !isset($_SERVER['REQUEST_URI']) ) {
            $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
         }

         $currentdir = getcwd();
         chdir(GLPI_ROOT);
         $glpidir = str_replace(str_replace('\\', '/',getcwd()),"",str_replace('\\', '/',$currentdir));
         chdir($currentdir);
         $globaldir = cleanParametersURL($_SERVER['REQUEST_URI']);
         $globaldir = preg_replace("/\/[0-9a-zA-Z\.\-\_]+\.php/","",$globaldir);

         $CFG_GLPI["root_doc"] = str_replace($glpidir,"",$globaldir);
         $CFG_GLPI["root_doc"] = preg_replace("/\/$/","",$CFG_GLPI["root_doc"]);
         // urldecode for space redirect to encoded URL : change entity
         $CFG_GLPI["root_doc"] = urldecode($CFG_GLPI["root_doc"]);
      }
   }


   /**
    * Display debug information for dbslave
   **/
   function showDebug() {

      $options['diff'] = 0;
      $options['name'] = '';
      NotificationEvent::debugEvent(new DBConnection(), $options);
   }

}

?>
