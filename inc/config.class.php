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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 *  Config class
**/
class Config extends CommonDBTM {

   // From CommonGLPI
   protected $displaylist         = false;

   // From CommonDBTM
   public $auto_message_on_action = false;
   public $showdebug              = true;


   static function getTypeName($nb=0) {
      global $LANG;

      // No plural
      return $LANG['common'][12];
   }


   function canCreate() {
      return false;
   }


   function canUpdate() {
      return Session::haveRight('config', 'w');
   }


   function canView() {
      return Session::haveRight('config', 'r');
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }


   function showForm($ID, $options=array()) {

      $this->check(1, 'r');
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

      if (isset($input['allow_search_view']) && !$input['allow_search_view']) {
         // Global search need "view"
         $input['allow_search_global'] = 0;
      }
      if (isset($input["smtp_passwd"])) {
         if (empty($input["smtp_passwd"])) {
            unset($input["smtp_passwd"]);
         } else {
            $input["smtp_passwd"] = Toolbox::encrypt(stripslashes($input["smtp_passwd"]), GLPIKEY);
         }
      }

      if (isset($input["_blank_smtp_passwd"]) && $input["_blank_smtp_passwd"]) {
         $input['smtp_passwd'] = '';
      }

      if (isset($input["proxy_passwd"])) {
         if (empty($input["proxy_passwd"])) {
            unset($input["proxy_passwd"]);
         } else {
            $input["proxy_passwd"] = Toolbox::encrypt(stripslashes($input["proxy_passwd"]), GLPIKEY);
         }
      }

      if (isset($input["_blank_proxy_passwd"]) && $input["_blank_proxy_passwd"]) {
         $input['proxy_passwd'] = '';
      }

      // Manage DB Slave process
      if (isset($input['_dbslave_status'])) {
         $already_active = DBConnection::isDBSlaveActive();

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
               $priority               = $input["_matrix_${urgency}_${impact}"];
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

      if (!Session::haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
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
      echo "<td>" . $LANG['setup'][108] . "&nbsp;:</td><td>";
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
      echo "</td>";
      if ($CFG_GLPI["use_ajax"]) {
         echo "<td>".$LANG['setup'][119]."&nbsp;:</td><td>";
         Dropdown::showInteger('ajax_min_textsearch_load', $CFG_GLPI["ajax_min_textsearch_load"],
                               0, 10, 1);
      } else {
         echo "<td colspan='2'>&nbsp;";
      }

      echo "</td></tr>";

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

      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>".$LANG['setup'][22]."</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['search'][11] . "</td><td>"; // Items seens
      $values = array(0 => $LANG['choice'][0],
                      1 => $LANG['choice'][1].' ('.$LANG['setup'][23].')',
                      2 => $LANG['choice'][1].' ('.$LANG['setup'][24].')');
      Dropdown::showFromArray('allow_search_view', $values,
                              array('value' => $CFG_GLPI['allow_search_view']));

      echo "</td><td>". $LANG['setup'][25]."</td><td>"; // Global search
      if ($CFG_GLPI['allow_search_view']) {
         Dropdown::showYesNo('allow_search_global', $CFG_GLPI['allow_search_global']);
      } else {
         echo Dropdown::getYesNo(0);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['common'][66] . "</td><td>"; // All
      $values = array(0 => $LANG['choice'][0],
                      1 => $LANG['choice'][1].' ('.$LANG['setup'][23].')');
      Dropdown::showFromArray('allow_search_all', $values,
                              array('value' => $CFG_GLPI['allow_search_all']));
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\"".$LANG['buttons'][2]."\">";
      echo "</td></tr>";

      echo "</table></div>";
      Html::closeForm();
   }


   /**
    * Print the config form for restrictions
    *
    * @return Nothing (display)
   **/
   function showFormInventory() {
      global $DB, $LANG, $CFG_GLPI;

      if (!Session::haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<input type='hidden' name='id' value='" . $CFG_GLPI["id"] . "'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . $LANG['Menu'][38] . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['ocsconfig'][23] . "&nbsp;:</td><td>";
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
      Html::showDateFormItem("date_tax", $CFG_GLPI["date_tax"], false, true, '', '', false);
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
      echo "</td></td>";
      echo "<td colspan='2'>&nbsp;";
      echo "</td></tr>";

      echo "</table>";

      if (Session::haveRight("transfer","w") && Session::isMultiEntitiesMode()) {
         echo "<br><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . $LANG['setup'][290] . "</th></tr>";
         echo "<tr class='tab_bg_2'>";
         echo "<td>" . $LANG['setup'][291] . "&nbsp;:</td><td>";
         Dropdown::show('Transfer',
                        array('value'      => $CFG_GLPI["transfers_id_auto"],
                              'name'       => "transfers_id_auto",
                              'emptylabel' => $LANG['setup'][292]));
         echo "</td></td></tr>";
         echo "</table>";
      }

      echo "<br><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='6'>".$LANG['setup'][280]." (".$LANG['peripherals'][32].")</th></tr>";

      echo "<tr><th>&nbsp;</th>";
      echo "<th>" . $LANG['common'][18] . "</th>";
      echo "<th>" . $LANG['common'][34] . "</th>";
      echo "<th>" . $LANG['common'][35] . "</th>";
      echo "<th>" . $LANG['common'][15] . "</th>";
      echo "<th>" . $LANG['state'][0] . "</th>";
      echo "</tr>";

      $fields = array("contact", "group", "location", "user");
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . $LANG['setup'][281] . "&nbsp;:</td>";
      $values[0] = $LANG['setup'][285];
      $values[1] = $LANG['setup'][283];

      foreach ($fields as $field) {
         echo "<td>";
         $fieldname = "is_".$field."_autoupdate";
         Dropdown::showFromArray($fieldname, $values, array('value' => $CFG_GLPI[$fieldname]));
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
         Dropdown::showFromArray($fieldname, $values, array('value' => $CFG_GLPI[$fieldname]));
         echo "</td>";
      }

      echo "<td>";
      State::dropdownBehaviour("state_autoclean_mode", $LANG['setup'][196],
                               $CFG_GLPI["state_autoclean_mode"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='6' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\"".$LANG['buttons'][2]."\">";
      echo "</td></tr>";

      echo "</table></div>";
      Html::closeForm();
   }


   /**
    * Print the config form for restrictions
    *
    * @return Nothing (display)
   **/
   function showFormAuthentication() {
      global $DB, $LANG, $CFG_GLPI;

      if (!Session::haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
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
      echo "</td><td> " . $LANG['setup'][187] . "&nbsp;:</td><td>";
      Dropdown::showGMT("time_offset", $CFG_GLPI["time_offset"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update_auth' class='submit' value=\"".$LANG['buttons'][2]."\">";
      echo "</td></tr>";

      echo "</table></div>";
      Html::closeForm();
   }


   /**
    * Print the config form for slave DB
    *
    * @return Nothing (display)
   **/
   function showFormDBSlave() {
      global $DB, $LANG, $CFG_GLPI, $DBSlave;

      if (!Session::haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
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
      $values = array(0 => $LANG['setup'][307],    // Never
                      1 => $LANG['setup'][830],    // If synced (all changes)
                      2 => $LANG['setup'][831],    // If synced (current user changes)
                      3 => $LANG['setup'][832],    // If synced or read-only account
                      4 => $LANG['setup'][805]);   // Always
      Dropdown::showFromArray('use_slave_for_search', $values,
                              array('value' => $CFG_GLPI["use_slave_for_search"]));
      echo "<td colspan='2'>&nbsp;</td>";
      echo "</tr>";

      if ($DBSlave->connected && !$DB->isSlave()) {
         echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
         DBConnection::showAllReplicateDelay();
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\"".$LANG['buttons'][2]."\">";
      echo "</td></tr>";

      echo "</table></div>";
      Html::closeForm();
   }


   /**
    * Print the config form for connections
    *
    * @return Nothing (display)
   **/
   function showFormHelpdesk() {
      global $DB, $LANG, $CFG_GLPI;

      if (!Session::haveRight("config", "w")) {
         return false;
      }

      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
      echo "<div class='center spaced' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . $LANG['title'][24] . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][148] . "&nbsp;:</td><td>";
      Dropdown::showInteger('time_step', $CFG_GLPI["time_step"], 30, 60, 30, array(5  => 5,
                                                                                   10 => 10,
                                                                                   15 => 15,
                                                                                   20 => 20));
      echo "&nbsp;" . $LANG['job'][22];
      echo "</td><td>" . $LANG['setup'][223] . "&nbsp;:</td><td>";
      Dropdown::showHours('planning_begin', $CFG_GLPI["planning_begin"]);
      echo "&nbsp;->&nbsp;";
      Dropdown::showHours('planning_end', $CFG_GLPI["planning_end"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['mailgate'][7] . " (".$LANG['setup'][46].")&nbsp;:</td><td>";
      MailCollector::showMaxFilesize('default_mailcollector_filesize_max',
                                     $CFG_GLPI["default_mailcollector_filesize_max"]);
      echo "</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][409] . "&nbsp;:</td><td>";
      Dropdown::show('DocumentCategory',
                     array('value' => $CFG_GLPI["documentcategories_id_forticket"],
                           'name'  => "documentcategories_id_forticket"));
      echo "</td>";
      echo "<td>" . $LANG['setup'][608] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("default_software_helpdesk_visible",
                          $CFG_GLPI["default_software_helpdesk_visible"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['tracking'][37] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("keep_tickets_on_delete", $CFG_GLPI["keep_tickets_on_delete"]);
      echo "</td><td colspan='2'>&nbsp;</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][219] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("use_anonymous_helpdesk", $CFG_GLPI["use_anonymous_helpdesk"]);
      echo "</td><td>" . $LANG['setup'][220] . "&nbsp;:</td><td>";
      Dropdown::showYesNo("use_anonymous_followups", $CFG_GLPI["use_anonymous_followups"]);
      echo "</td></tr>";

      echo "</table>";

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
      echo "<input type='submit' name='update' class='submit' value=\"".$LANG['buttons'][2]."\">";
      echo "</td></tr>";

      echo "</table></div>";
      Html::closeForm();
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
      $url       = Toolbox::getItemTypeFormURL(__CLASS__);

      if (array_key_exists('last_login',$data)) {
         $userpref = true;
         if ($data["id"] === Session::getLoginUserID()) {
            $url      = $CFG_GLPI['root_doc']."/front/preference.php";
         } else {
            $url      = $CFG_GLPI['root_doc']."/front/user.form.php";
         }
      }

      echo "<form name='form' action='$url' method='post'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<input type='hidden' name='id' value='" . $data["id"] . "'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . $LANG['setup'][6] . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . $LANG['setup'][131]."&nbsp;:</td><td>";
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
      $values = array(REALNAME_BEFORE  => $LANG['common'][48]." ".$LANG['common'][43],
                      FIRSTNAME_BEFORE => $LANG['common'][43]." ".$LANG['common'][48]);
      Dropdown::showFromArray('names_format', $values, array('value' => $data["names_format"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['setup'][7]."&nbsp;:</td><td>";
      $values = array(';' =>';',
                      ',' =>',');
      Dropdown::showFromArray('csv_delimiter', $values, array('value' => $data["csv_delimiter"]));
      echo "</td>";

      if (!$userpref || $CFG_GLPI['show_count_on_tabs'] != -1) {
         echo "<td>".$LANG['setup'][1]."&nbsp;:</td><td>";

         $values = array(0 => $LANG['choice'][0],
                         1 => $LANG['choice'][1]);

         if (!$userpref) {
            $values[-1] = $LANG['setup'][307];
         }
         Dropdown::showFromArray('show_count_on_tabs', $values, array('value' => $data["show_count_on_tabs"]));
         echo "</td>";
      } else {
         echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      if ($oncentral) {
         echo "<td>" . $LANG['setup'][129] . "&nbsp;:</td><td>";
         Dropdown::showYesNo("is_ids_visible", $data["is_ids_visible"]);
         echo "</td>";
      } else {
         echo "<td colspan='2'></td>";
      }
      echo "<td>" . ($userpref?$LANG['setup'][41]:$LANG['setup'][113]) . "&nbsp;:</td><td>";
      if (Session::haveRight("config","w") || !GLPI_DEMO_MODE) {
         Dropdown::showLanguages("language", array('value' => $data["language"]));
      } else {
         echo "&nbsp;";
      }
      echo "</td></tr>";

      if ($oncentral) {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='2'></td>";
         echo "<td>" . $LANG['setup'][133] . "&nbsp;:</td><td>";
         Dropdown::showInteger('display_count_on_home', $data['display_count_on_home'], 0, 30);
         echo "</td></tr>";
      }

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

         echo "<tr class='tab_bg_2'><td>".$LANG['setup'][12]."&nbsp;:</td><td>";
         if (!$userpref || Session::haveRight('own_ticket', 1)) {
            Dropdown::showYesNo("set_default_tech", $data["set_default_tech"]);
         } else {
            echo Dropdown::getYesNo(0);
         }
         echo "</td><td>" . $LANG['setup'][11] . "&nbsp;:</td><td>";
         Dropdown::showInteger('refresh_ticket_list', $data["refresh_ticket_list"], 1, 30, 1,
                               array(0 => $LANG['setup'][307]));
         echo "&nbsp;".$LANG['job'][22];
         echo "</td>";
         echo "</tr>";
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

      // Only for user
      if (array_key_exists('personal_token', $data)) {
         echo "<tr class='tab_bg_1'><th colspan='4'>". $LANG['common'][108] ."</th></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][108] . "&nbsp;:";
         if (!empty($data["personal_token"])) {
            echo "<br>(".$LANG['users'][18]."&nbsp;".
                       Html::convDateTime($data["personal_token_date"]).')';
         }

         echo "</td><td colspan='3'>";
         echo "<input type='checkbox' name='_reset_personal_token'>&nbsp;".$LANG['buttons'][61];
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\"".$LANG['buttons'][2]."\">";
      echo "</td></tr>";

      echo "</table></div>";
      Html::closeForm();
   }


   /**
    * Display a HTML report about systeme information / configuration
    *
   **/
   function showSystemInformations() {
      global $DB,$LANG,$CFG_GLPI;

      echo "<div class='center' id='tabsbody'>";
      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
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
      echo "<td><input type='password' name='proxy_passwd' value='' autocomplete='off'>";
      echo "<br><input type='checkbox' name='_blank_proxy_passwd'>&nbsp;".$LANG['setup'][284];

      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\"".$LANG['buttons'][2]."\"></td>";
      echo "</tr>";

      echo "</table>";
      Html::closeForm();

      $width = 128;

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>" . $LANG['setup'][721] . "</th></tr>";

      echo "<tr class='tab_bg_1'><td><pre>[code]\n&nbsp;\n";
      $oldlang = $_SESSION['glpilanguage'];
      Session::loadLanguage('en_GB');
      echo "GLPI ".$CFG_GLPI['version']." (".$CFG_GLPI['root_doc']." => ".
            dirname(dirname($_SERVER["SCRIPT_FILENAME"])).")\n";


      echo "\n</pre></td></tr>";

      echo "<tr><th>" . $LANG['common'][52] . "</th></tr>\n";

      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";
      echo wordwrap($LANG['computers'][9]."&nbsp;: ".php_uname()."\n", $width, "\n\t");
      $exts = get_loaded_extensions();
      sort($exts);
      echo wordwrap("PHP ".phpversion()." (".implode(', ',$exts).")\n", $width, "\n\t");
      $msg = $LANG['common'][12].": ";

      foreach (array('memory_limit',
                     'max_execution_time',
                     'safe_mode',
                     'session.save_handler',
                     'post_max_size',
                     'upload_max_filesize') as $key) {
         $msg .= $key.'="'.ini_get($key).'" ';
      }
      echo wordwrap($msg."\n", $width, "\n\t");

      $msg = $LANG['Menu'][4].": ";
      if (isset($_SERVER["SERVER_SOFTWARE"])) {
         $msg .= $_SERVER["SERVER_SOFTWARE"];
      }
      if (isset($_SERVER["SERVER_SIGNATURE"])) {
         $msg .= ' ('.Html::clean($_SERVER["SERVER_SIGNATURE"]).')';
      }
      echo wordwrap($msg."\n", $width, "\n\t");

      if (isset($_SERVER["HTTP_USER_AGENT"])) {
         echo "\t" . $_SERVER["HTTP_USER_AGENT"] . "\n";
      }

      $version = "???";
      foreach ($DB->request('SELECT VERSION() as ver') as $data) {
         $version = $data['ver'];
      }
      echo "MySQL: $version (".$DB->dbuser."@".$DB->dbhost."/".$DB->dbdefault.")\n\n";

      self::checkWriteAccessToDirs(true);


      foreach ($CFG_GLPI["systeminformations_types"] as $type) {
         $tmp = new $type();
         $tmp->showSystemInformations($width);
      }
      Session::loadLanguage($oldlang);



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
         $globaldir = Html::cleanParametersURL($_SERVER['REQUEST_URI']);
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


   /**
    * Display field unicity criterias form
   **/
   function showFormFieldUnicity() {
      global $CFG_GLPI;

      $unicity = new FieldUnicity();
      $unicity->showForm($CFG_GLPI["id"], -1);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      switch ($item->getType()) {
         case 'Preference' :
            return $LANG['setup'][6];

         case 'User' :
            if (Session::haveRight('user','w')
                && $item->currentUserHaveMoreRightThan($item->getID())) {
               return $LANG['Menu'][11];
            }
            break;

         case __CLASS__ :
            $tabs[1] = $LANG['setup'][70];   // Display
            $tabs[2] = $LANG['setup'][48];   // Prefs
            $tabs[3] = $LANG['Menu'][38];    // Inventory
            $tabs[4] = $LANG['title'][24];   // Helpdesk
            $tabs[5] = $LANG['setup'][720];  // SysInfo

            if (DBConnection::isDBSlaveActive()) {
               $tabs[6]  = $LANG['setup'][800];  // Slave
            }
            return $tabs;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      if ($item->getType()=='Preference') {
         $config = new self();
         $user = new User();
         if ($user->getFromDB(Session::getLoginUserID())) {
            $user->computePreferences();
            $config->showFormUserPrefs($user->fields);
         }

      } else if ($item->getType()=='User') {
         $config = new self();
         $item->computePreferences();
         $config->showFormUserPrefs($item->fields);

      } else if($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 :
               $item->showFormDisplay();
               break;

            case 2 :
               $item->showFormUserPrefs($CFG_GLPI);
               break;

            case 3 :
               $item->showFormInventory();
               break;

            case 4 :
               $item->showFormHelpdesk();
               break;

            case 5 :
               $item->showSystemInformations();
               break;

            case 6 :
               $item->showFormDBSlave();
               break;

         }
      }
      return true;
   }


   /**
    * Check Write Access to needed directories
    *
    * @param $fordebug boolean display for debug
    *
    * @return 2 : creation error 1 : delete error 0: OK
   **/
   static function checkWriteAccessToDirs($fordebug=false) {
      global $LANG;

      $dir_to_check = array(GLPI_CONFIG_DIR  => $LANG['install'][23],
                            GLPI_DOC_DIR     => $LANG['install'][21],
                            GLPI_DUMP_DIR    => $LANG['install'][16],
                            GLPI_SESSION_DIR => $LANG['install'][50],
                            GLPI_CRON_DIR    => $LANG['install'][52],
                            GLPI_CACHE_DIR   => $LANG['install'][99],
                            GLPI_GRAPH_DIR   => $LANG['install'][106]);
      $error = 0;

      foreach ($dir_to_check as $dir => $message) {

         if (!$fordebug) {
            echo "<tr class='tab_bg_1'><td class='left b'>".$message."</td>";
         }
         $tmperror = Toolbox::testWriteAccessToDirectory($dir);

         $errors = array(4 => $LANG['install'][100],
                         3 => $LANG['install'][101],
                         2 => $LANG['install'][17],
                         1 => $LANG['install'][19]);

         if ($tmperror > 0) {
            if ($fordebug) {
               echo "<img src='".GLPI_ROOT."/pics/redbutton.png'> ".$LANG['install'][97]." $dir - ".
                              $errors[$tmperror]."\n";
            } else {
               echo "<td><img src='".GLPI_ROOT."/pics/redbutton.png'><p class='red'>".
                          $errors[$tmperror]."</p> ".$LANG['install'][97]."'".$dir."'</td></tr>";
            }
            $error = 2;
         } else {
            if ($fordebug) {
               echo "<img src='".GLPI_ROOT."/pics/greenbutton.png'>$dir : OK\n";
            } else {
               echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][20].
                           "\" title=\"".$LANG['install'][20]."\"></td></tr>";
            }
         }
      }

      // Only write test for GLPI_LOG as SElinux prevent removing log file.
      if (!$fordebug) {
         echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][53]."</td>";
      }

      if (error_log("Test\n", 3, GLPI_LOG_DIR."/php-errors.log")) {
         if ($fordebug) {
            echo "<img src='".GLPI_ROOT."/pics/greenbutton.png'>".GLPI_LOG_DIR." : OK\n";
         } else {
            echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][22].
                       "\" title=\"".$LANG['install'][22]."\"></td></tr>";
         }

      } else {
         if ($fordebug) {
            echo "<img src='".GLPI_ROOT."/pics/orangebutton.png'>".$LANG['install'][97]." : ".
                           GLPI_LOG_DIR."\n";
         } else {
            echo "<td><img src='".GLPI_ROOT."/pics/orangebutton.png'>".
                      "<p class='red'>".$LANG['install'][19]."</p>".
                      $LANG['install'][97]."'".GLPI_LOG_DIR."'</td></tr>";
         }
         $error = 1;
      }
      return $error;
   }
}

?>