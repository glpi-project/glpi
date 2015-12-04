<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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

   static $rightname              = 'config';



   static function getTypeName($nb=0) {
      return __('Setup');
   }


   /**
    *  @see CommonGLPI::getMenuContent()
    *
    *   @since version 0.85
   **/
   static function getMenuContent() {
      global $CFG_GLPI;

      $menu = array();
      if (static::canView()) {
         $menu['title']   = _x('setup', 'General');
         $menu['page']    = '/front/config.form.php';
      }
      if (count($menu)) {
         return $menu;
      }
      return false;
   }


   static function canCreate() {
      return false;
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }


   /**
    * Prepare input datas for updating the item
    *
    * @see CommonDBTM::prepareInputForUpdate()
    *
    * @param $input array of datas used to update the item
    *
    * @return the modified $input array
   **/
   function prepareInputForUpdate($input) {
      global $CFG_GLPI;
      // Update only an item
      if (isset($input['context'])) {
         return $input;
      }

      // Process configuration for plugins
      if (!empty($input['config_context'])) {
         $config_context = $input['config_context'];
         unset($input['id']);
         unset($input['_glpi_csrf_token']);
         unset($input['update']);
         unset($input['config_context']);
         if ((!empty($input['config_class']))
             && (class_exists($input['config_class']))
             && (method_exists ($input['config_class'], 'configUpdate'))) {
            $config_method = $input['config_class'].'::configUpdate';
            unset($input['config_class']);
            $input = call_user_func($config_method, $input);
         }
         $this->setConfigurationValues($config_context, $input);
         return false;
      }

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
            $input["proxy_passwd"] = Toolbox::encrypt(stripslashes($input["proxy_passwd"]),
                                                      GLPIKEY);
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
      // Beware : with new management system, we must update each value
      unset($input['id']);
      unset($input['_glpi_csrf_token']);
      unset($input['update']);

      // Add skipMaintenance if maintenance mode update
      if (isset($input['maintenance_mode']) && $input['maintenance_mode']) {
         $_SESSION['glpiskipMaintenance'] = 1;
         $url = $CFG_GLPI['root_doc']."/index.php?skipMaintenance=1";
         Session::addMessageAfterRedirect(sprintf(__('Maintenance mode activated. Backdoor using: %s'),
                                                  "<a href='$url'>$url</a>"),
                                          false, WARNING);
      }

      $this->setConfigurationValues('core', $input);

      return false;
   }


   /**
    * Print the config form for display
    *
    * @return Nothing (display)
   **/
   function showFormDisplay() {
      global $CFG_GLPI;

      if (!self::canView()) {
         return false;
      }
      $canedit = Session::haveRight(self::$rightname, UPDATE);
      if ($canedit) {
         echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
      }
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . __('General setup') . "</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('URL of the application') . "</td>";
      echo "<td colspan='3'><input type='text' name='url_base' size='80' value='".$CFG_GLPI["url_base"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __('Text in the login box') . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea cols='70' rows='4' name='text_login'>".$CFG_GLPI["text_login"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td width='30%'> " . __('Allow FAQ anonymous access') . "</td><td  width='20%'>";
      Dropdown::showYesNo("use_public_faq", $CFG_GLPI["use_public_faq"]);
      echo "</td><td width='30%'>" . __('Simplified interface help link') . "</td>";
      echo "<td><input size='22' type='text' name='helpdesk_doc_url' value='" .
                 $CFG_GLPI["helpdesk_doc_url"] . "'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Default search results limit (page)')."</td><td>";
      Dropdown::showNumber("list_limit_max", array('value' => $CFG_GLPI["list_limit_max"],
                                                   'min'   => 5,
                                                   'max'   => 200,
                                                   'step'  => 5));
      echo "</td><td>" . __('Standard interface help link') . "</td>";
      echo "<td><input size='22' type='text' name='central_doc_url' value='" .
                 $CFG_GLPI["central_doc_url"] . "'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Default characters limit (summary text boxes)') . "</td><td>";
      Dropdown::showNumber('cut', array('value' => $CFG_GLPI["cut"],
                                        'min'   => 50,
                                        'max'   => 500,
                                        'step'  => 50));
      echo "</td><td>" . __('Default url length limit') . "</td><td>";
      Dropdown::showNumber('url_maxlength', array('value' => $CFG_GLPI["url_maxlength"],
                                                  'min'   => 20,
                                                  'max'   => 80,
                                                  'step'  => 5));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td>" .__('Default decimals limit') . "</td><td>";
      Dropdown::showNumber("decimal_number", array('value' => $CFG_GLPI["decimal_number"],
                                                   'min'   => 1,
                                                   'max'   => 4));
      echo "</td><td>" . __('Default chart format')."</td><td>";
      Dropdown::showFromArray("default_graphtype", array('png' => 'PNG',
                                                         'svg' => 'SVG'),
                              array('value' => $CFG_GLPI["default_graphtype"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __("Translation of dropdowns") . "</td><td>";
      Dropdown::showYesNo("translate_dropdowns", $CFG_GLPI["translate_dropdowns"]);
      echo "</td>";
      echo "<td>" . __("Knowledge base translation") . "</td><td>";
      Dropdown::showYesNo("translate_kb", $CFG_GLPI["translate_kb"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>".__('Dynamic display').
           "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".
            __('Page size for dropdown (paging using scroll)').
            "</td><td>";
      Dropdown::showNumber('dropdown_max', array('value' => $CFG_GLPI["dropdown_max"],
                                                 'min'   => 0,
                                                 'max'   => 200));
      echo "</td>";
      echo "<td>" . __('Autocompletion of text fields') . "</td><td>";
      Dropdown::showYesNo("use_ajax_autocompletion", $CFG_GLPI["use_ajax_autocompletion"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>". __("Don't show search engine in dropdowns if the number of items is less than").
           "</td><td>";
      Dropdown::showNumber('ajax_limit_count', array('value' => $CFG_GLPI["ajax_limit_count"],
                                                     'min'   => 1,
                                                     'max'   => 200,
                                                     'step'  => 1,
                                                     'toadd' => array(0 => __('Never'))));
//       echo "</td><td>".__('Buffer time for dynamic search in dropdowns')."</td><td>";
//       Dropdown::showNumber('ajax_buffertime_load',
//                            array('value' => $CFG_GLPI["ajax_buffertime_load"],
//                                  'min'   => 100,
//                                  'max'   => 5000,
//                                  'step'  => 100,
//                                  'unit'  => 'millisecond'));
      echo "<td colspan='2'></td>";
      echo "</td></tr>";

//      echo "<tr class='tab_bg_2'>";
//       echo "<td>" . __('Autocompletion of text fields') . "</td><td>";
//       Dropdown::showYesNo("use_ajax_autocompletion", $CFG_GLPI["use_ajax_autocompletion"]);
//       echo "</td><td>". __('Character to force the full display of dropdowns (wildcard)')."</td>";
//       echo "<td><input type='text' size='1' name='ajax_wildcard' value='" .
//                   $CFG_GLPI["ajax_wildcard"] . "'>";
//      echo "</td>";
//      echo "</tr>";

      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>".__('Search engine')."</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Items seen') . "</td><td>";
      $values = array(0 => __('No'),
                      1 => sprintf(__('%1$s (%2$s)'), __('Yes'), __('last criterion')),
                      2 => sprintf(__('%1$s (%2$s)'), __('Yes'), __('default criterion')));
      Dropdown::showFromArray('allow_search_view', $values,
                              array('value' => $CFG_GLPI['allow_search_view']));
      echo "</td><td>". __('Global search')."</td><td>";
      if ($CFG_GLPI['allow_search_view']) {
         Dropdown::showYesNo('allow_search_global', $CFG_GLPI['allow_search_global']);
      } else {
         echo Dropdown::getYesNo(0);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('All') . "</td><td>";
      $values = array(0 => __('No'),
                      1 => sprintf(__('%1$s (%2$s)'), __('Yes'), __('last criterion')));
      Dropdown::showFromArray('allow_search_all', $values,
                              array('value' => $CFG_GLPI['allow_search_all']));
      echo "</td><td colspan='2'></td></tr>";

      if ($canedit) {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='4' class='center'>";
         echo "<input type='submit' name='update' class='submit' value=\""._sx('button','Save')."\">";
         echo "</td></tr>";
      }

      echo "</table></div>";
      Html::closeForm();
   }


   /**
    * Print the config form for restrictions
    *
    * @return Nothing (display)
   **/
   function showFormInventory() {
      global $DB, $CFG_GLPI;

      if (!self::canView()) {
         return false;
      }

      $canedit = Config::canUpdate();
      if ($canedit) {
         echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
      }
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . __('Assets') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td width='30%'>". __('Enable the financial and administrative information by default')."</td>";
      echo "<td  width='20%'>";
      Dropdown::ShowYesNo('auto_create_infocoms', $CFG_GLPI["auto_create_infocoms"]);
      echo "</td><td width='20%'> " . __('Restrict monitor management') . "</td>";
      echo "<td width='30%'>";
      $this->dropdownGlobalManagement ("monitors_management_restrict",
                                       $CFG_GLPI["monitors_management_restrict"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>" . __('Software category deleted by the dictionary rules') .
           "</td><td>";
      SoftwareCategory::dropdown(array('value' => $CFG_GLPI["softwarecategories_id_ondelete"],
                                       'name'  => "softwarecategories_id_ondelete"));
      echo "</td><td> " . __('Restrict device management') . "</td><td>";
      $this->dropdownGlobalManagement ("peripherals_management_restrict",
                                       $CFG_GLPI["peripherals_management_restrict"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" .__('Beginning of fiscal year') . "</td><td>";
      Html::showDateField("date_tax", array('value'      => $CFG_GLPI["date_tax"],
                                            'maybeempty' => false,
                                            'canedit'    => true,
                                            'min'        => '',
                                            'max'        => '',
                                            'showyear'   => false));
      echo "</td><td> " . __('Restrict phone management') . "</td><td>";
      $this->dropdownGlobalManagement ("phones_management_restrict",
                                       $CFG_GLPI["phones_management_restrict"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Automatic fields (marked by *)') . "</td><td>";
      $tab = array(0 => __('Global'),
                   1 => __('By entity'));
      Dropdown::showFromArray('use_autoname_by_entity', $tab,
                              array('value' => $CFG_GLPI["use_autoname_by_entity"]));
      echo "</td><td> " . __('Restrict printer management') . "</td><td>";
      $this->dropdownGlobalManagement("printers_management_restrict",
                                      $CFG_GLPI["printers_management_restrict"]);
      echo "</td></tr>";

      echo "</table>";

      if (Session::haveRightsOr("transfer", array(CREATE, UPDATE))
          && Session::isMultiEntitiesMode()) {
         echo "<br><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . __('Automatic transfer of computers') . "</th></tr>";
         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __('Template for the automatic transfer of computers in another entity') .
              "</td><td>";
         Transfer::dropdown(array('value'      => $CFG_GLPI["transfers_id_auto"],
                                  'name'       => "transfers_id_auto",
                                  'emptylabel' => __('No automatic transfer')));
         echo "</td></tr>";
         echo "</table>";
      }

      echo "<br><table class='tab_cadre_fixe'>";
      echo "<tr>";
      echo "<th colspan='4'>".__('Automatically update of the elements related to the computers');
      echo "</th><th colspan='2'>".__('Unit management')."</th></tr>";

      echo "<tr><th>&nbsp;</th>";
      echo "<th>" . __('Alternate username') . "</th>";
      echo "<th>" . __('User') . "</th>";
      echo "<th>" . __('Group') . "</th>";
      echo "<th>" . __('Location') . "</th>";
      echo "<th>" . __('Status') . "</th>";
      echo "</tr>";

      $fields = array("contact", "user", "group", "location");
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __('When connecting or updating') . "</td>";
      $values[0] = __('Do not copy');
      $values[1] = __('Copy');

      foreach ($fields as $field) {
         echo "<td>";
         $fieldname = "is_".$field."_autoupdate";
         Dropdown::showFromArray($fieldname, $values, array('value' => $CFG_GLPI[$fieldname]));
         echo "</td>";
      }

      echo "<td>";
      State::dropdownBehaviour("state_autoupdate_mode", __('Copy computer status'),
                               $CFG_GLPI["state_autoupdate_mode"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __('When disconnecting') . "</td>";
      $values[0] = __('Do not delete');
      $values[1] = __('Clear');

      foreach ($fields as $field) {
         echo "<td>";
         $fieldname = "is_".$field."_autoclean";
         Dropdown::showFromArray($fieldname, $values, array('value' => $CFG_GLPI[$fieldname]));
         echo "</td>";
      }

      echo "<td>";
      State::dropdownBehaviour("state_autoclean_mode", __('Clear status'),
                               $CFG_GLPI["state_autoclean_mode"]);
      echo "</td></tr>";

      if ($canedit) {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='6' class='center'>";
         echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
         echo "</td></tr>";
      }

      echo "</table></div>";
      Html::closeForm();
   }


   /**
    * Print the config form for restrictions
    *
    * @return Nothing (display)
   **/
   function showFormAuthentication() {
      global $DB, $CFG_GLPI;

      if (!Config::canUpdate()) {
         return false;
      }

      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . __('Authentication') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td width='30%'>". __('Automatically add users from an external authentication source').
           "</td><td width='20%'>";
      Dropdown::showYesNo("is_users_auto_add", $CFG_GLPI["is_users_auto_add"]);
      echo "</td><td width='30%'>". __('Add a user without accreditation from a LDAP directory').
           "</td><td width='20%'>";
      Dropdown::showYesNo("use_noright_users_add", $CFG_GLPI["use_noright_users_add"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __('Action when a user is deleted from the LDAP directory') . "</td><td>";
      AuthLDap::dropdownUserDeletedActions($CFG_GLPI["user_deleted_ldap"]);
      echo "</td><td> " . __('GLPI server time zone') . "</td><td>";
      Dropdown::showGMT("time_offset", $CFG_GLPI["time_offset"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update_auth' class='submit' value=\""._sx('button', 'Save').
           "\">";
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
      global $DB, $CFG_GLPI, $DBslave;

      if (!Config::canUpdate()) {
         return false;
      }

      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<input type='hidden' name='_dbslave_status' value='1'>";
      echo "<table class='tab_cadre_fixe'>";
      $active = DBConnection::isDBSlaveActive();

      echo "<tr class='tab_bg_2'><th colspan='4'>" . _n('Mysql replica', 'Mysql replicas', Session::getPluralNumber()) .
           "</th></tr>";
      $DBslave = DBConnection::getDBSlaveConf();

      if (is_array($DBslave->dbhost)) {
         $host = implode(' ', $DBslave->dbhost);
      } else {
         $host = $DBslave->dbhost;
      }
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Mysql server') . "</td>";
      echo "<td><input type='text' name='_dbreplicate_dbhost' size='40' value='$host'></td>";
      echo "<td>" . __('Database') . "</td>";
      echo "<td><input type='text' name='_dbreplicate_dbdefault' value='".$DBslave->dbdefault."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Mysql user') . "</td>";
      echo "<td><input type='text' name='_dbreplicate_dbuser' value='".$DBslave->dbuser."'></td>";
      echo "<td>" . __('Mysql password') . "</td>";
      echo "<td><input type='password' name='_dbreplicate_dbpassword' value='".
                 rawurldecode($DBslave->dbpassword)."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Use the slave for the search engine') . "</td><td>";
      $values = array(0 => __('Never'),
                      1 => __('If synced (all changes)'),
                      2 => __('If synced (current user changes)'),
                      3 => __('If synced or read-only account'),
                      4 => __('Always'));
      Dropdown::showFromArray('use_slave_for_search', $values,
                              array('value' => $CFG_GLPI["use_slave_for_search"]));
      echo "<td colspan='2'>&nbsp;</td>";
      echo "</tr>";

      if ($DBslave->connected && !$DB->isSlave()) {
         echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
         DBConnection::showAllReplicateDelay();
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
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
      global $DB, $CFG_GLPI;

      if (!self::canView()) {
         return false;
      }

      $canedit = Config::canUpdate();
      if ($canedit) {
         echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
      }
      echo "<div class='center spaced' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . __('Assistance') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td width='30%'>" . __('Step for the hours (minutes)') . "</td>";
      echo "<td width='20%'>";
      Dropdown::showNumber('time_step', array('value' => $CFG_GLPI["time_step"],
                                              'min'   => 30,
                                              'max'   => 60,
                                              'step'  => 30,
                                              'toadd' => array(1  => 1,
                                                               5  => 5,
                                                               10 => 10,
                                                               15 => 15,
                                                               20 => 20)));
      echo "</td>";
      echo "<td width='30%'>" .__('Limit of the schedules for planning') . "</td>";
      echo "<td width='20%'>";
      Dropdown::showHours('planning_begin', array('value' => $CFG_GLPI["planning_begin"]));
      echo "&nbsp;->&nbsp;";
      Dropdown::showHours('planning_end', array('value' => $CFG_GLPI["planning_end"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Default file size limit imported by the mails receiver')."</td><td>";
      MailCollector::showMaxFilesize('default_mailcollector_filesize_max',
                                     $CFG_GLPI["default_mailcollector_filesize_max"]);
      echo "</td>";

      echo "<td>" . __('Use rich text for helpdesk') . "</td><td>";
      $id                 = 'alert'.mt_rand();
      $param['on_change'] = '$("#'.$id.'").html("");
            if ($(this).val() == 0) {
               $("#'.$id.'").html("<br>'.__('You will lose the formatting of your data').'");
            }';
      Dropdown::showYesNo("use_rich_text", $CFG_GLPI["use_rich_text"], -1, $param);
      echo "<span class='red' id='".$id."'></span>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Default heading when adding a document to a ticket') . "</td><td>";
      DocumentCategory::dropdown(array('value' => $CFG_GLPI["documentcategories_id_forticket"],
                                       'name'  => "documentcategories_id_forticket"));
      echo "</td>";
      echo "<td>" . __('By default, a software may be linked to a ticket') . "</td><td>";
      Dropdown::showYesNo("default_software_helpdesk_visible",
                          $CFG_GLPI["default_software_helpdesk_visible"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Keep tickets when purging hardware in the inventory') . "</td><td>";
      Dropdown::showYesNo("keep_tickets_on_delete", $CFG_GLPI["keep_tickets_on_delete"]);
      echo "</td><td>".__('Show personnal information in new ticket form (simplified interface)');
      echo "</td><td>";
      Dropdown::showYesNo('use_check_pref', $CFG_GLPI['use_check_pref']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" .__('Allow anonymous ticket creation (helpdesk.receiver)') . "</td><td>";
      Dropdown::showYesNo("use_anonymous_helpdesk", $CFG_GLPI["use_anonymous_helpdesk"]);
      echo "</td><td>" . __('Allow anonymous followups (receiver)') . "</td><td>";
      Dropdown::showYesNo("use_anonymous_followups", $CFG_GLPI["use_anonymous_followups"]);
      echo "</td></tr>";

      echo "</table>";

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='7'>" . __('Matrix of calculus for priority');
      echo "<input type='hidden' name='_matrix' value='1'></th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='b right' colspan='2'>".__('Impact')."</td>";

      for ($impact=5 ; $impact>=1 ; $impact--) {
         echo "<td class='center'>".Ticket::getImpactName($impact).'<br>';

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
      echo "<td class='b' colspan='2'>".__('Urgency')."</td>";

      for ($impact=5 ; $impact>=1 ; $impact--) {
         echo "<td>&nbsp;</td>";
      }
      echo "</tr>";

      for ($urgency=5 ; $urgency>=1 ; $urgency--) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".Ticket::getUrgencyName($urgency)."&nbsp;</td>";
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
               echo "<td class='center' bgcolor='$bgcolor'>";
               Ticket::dropdownPriority(array('value' => $pri,
                                              'name'  => "_matrix_${urgency}_${impact}"));
               echo "</td>";
            } else {
               echo "<td><input type='hidden' name='_matrix_${urgency}_${impact}' value='$pri'>
                     </td>";
            }
         }
         echo "</tr>\n";
      }
      if ($canedit) {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='7' class='center'>";
         echo "<input type='submit' name='update' class='submit' value=\""._sx('button','Save')."\">";
         echo "</td></tr>";
      }

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
      global $DB, $CFG_GLPI;

      $oncentral = ($_SESSION["glpiactiveprofile"]["interface"]=="central");
      $userpref  = false;
      $url       = Toolbox::getItemTypeFormURL(__CLASS__);

      if (array_key_exists('last_login',$data)) {
         $userpref = true;
         if ($data["id"] === Session::getLoginUserID()) {
            $url  = $CFG_GLPI['root_doc']."/front/preference.php";
         } else {
            $url  = $CFG_GLPI['root_doc']."/front/user.form.php";
         }
      }
         echo "<form name='form' action='$url' method='post'>";

      // Only set id for user prefs
      if ($userpref) {
         echo "<input type='hidden' name='id' value='".$data['id']."'>";
      }
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . __('Personalization') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td width='30%'>" . ($userpref?__('Language'):__('Default language')) . "</td>";
      echo "<td width='20%'>";
      if (Config::canUpdate()
          || !GLPI_DEMO_MODE) {
         Dropdown::showLanguages("language", array('value' => $data["language"]));
      } else {
         echo "&nbsp;";
      }

      echo "<td width='30%'>" . __('Date format') ."</td>";
      echo "<td width='20%'>";
      $date_formats = array(0 => __('YYYY-MM-DD'),
                            1 => __('DD-MM-YYYY'),
                            2 => __('MM-DD-YYYY'));
      Dropdown::showFromArray('date_format', $date_formats, array('value' => $data["date_format"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Results to display by page')."</td><td>";
      // Limit using global config
      $value = (($data['list_limit'] < $CFG_GLPI['list_limit_max'])
                ? $data['list_limit'] : $CFG_GLPI['list_limit_max']);
      Dropdown::showNumber('list_limit', array('value' => $value,
                                               'min'   => 5,
                                               'max'   => $CFG_GLPI['list_limit_max'],
                                               'step'  => 5));
      echo "</td>";
      echo "<td>" .__('Number format') . "</td>";
      $values = array(0 => '1 234.56',
                      1 => '1,234.56',
                      2 => '1 234,56',
                      3 => '1234.56',
                      4 => '1234,56');
      echo "<td>";
      Dropdown::showFromArray('number_format', $values, array('value' => $data["number_format"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".__('Display order of surnames firstnames')."</td><td>";
      $values = array(User::REALNAME_BEFORE  => __('Surname, First name'),
                      User::FIRSTNAME_BEFORE => __('First name, Surname'));
      Dropdown::showFromArray('names_format', $values, array('value' => $data["names_format"]));
      echo "</td>";
      echo "<td>" . __("Color palette") . "</td><td>";
      $themes_files = scandir(GLPI_ROOT."/css/palettes/");
      echo "<select name='palette' id='theme-selector'>";
      foreach ($themes_files as $key => $file) {
         if (strpos($file, ".css") !== false) {
            $name     = substr($file, 0, -4);
            $selected = "";
            if ($data["palette"] == $name) {
               $selected = "selected='selected'";
            }
            echo "<option value='$name' $selected>".ucfirst($name)."</option>";
         }
      }
      echo Html::scriptBlock("
         function formatThemes(theme) {
             return \"&nbsp;<img src='../css/palettes/previews/\" + theme.text.toLowerCase() + \".png'/>\"
                     + \"&nbsp;\" + theme.text;
         }
         $(\"#theme-selector\").select2({
             formatResult: formatThemes,
             formatSelection: formatThemes,
             width: '100%',
             escapeMarkup: function(m) { return m; }
         });
      ");
      echo "</select>";
      echo "</td></tr>";


      echo "<tr class='tab_bg_2'>";
      if ($oncentral) {
         echo "<td>" . __('Display the complete name in tree dropdowns') . "</td><td>";
         Dropdown::showYesNo('use_flat_dropdowntree', $data["use_flat_dropdowntree"]);
         echo "</td>";
      } else {
        echo "<td colspan='2'>&nbsp;</td>";
      }

      if (!$userpref
          || ($CFG_GLPI['show_count_on_tabs'] != -1)) {
         echo "<td>".__('Display counts in tabs')."</td><td>";

         $values = array(0 => __('No'),
                         1 => __('Yes'));

         if (!$userpref) {
            $values[-1] = __('Never');
         }
         Dropdown::showFromArray('show_count_on_tabs', $values,
                                 array('value' => $data["show_count_on_tabs"]));
         echo "</td>";
      } else {
         echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      if ($oncentral) {
         echo "<td>" . __('Show GLPI ID') . "</td><td>";
         Dropdown::showYesNo("is_ids_visible", $data["is_ids_visible"]);
         echo "</td>";
      } else {
         echo "<td colspan='2'></td>";
      }

      echo "<td>".__('CSV delimiter')."</td><td>";
      $values = array(';' => ';',
                      ',' => ',');
      Dropdown::showFromArray('csv_delimiter', $values, array('value' => $data["csv_delimiter"]));

      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Notifications for my changes') . "</td><td>";
      Dropdown::showYesNo("notification_to_myself", $data["notification_to_myself"]);
      echo "</td>";
      if ($oncentral) {
         echo "<td>".__('Results to display on home page')."</td><td>";
         Dropdown::showNumber('display_count_on_home',
                              array('value' => $data['display_count_on_home'],
                                    'min'   => 0,
                                    'max'   => 30));
         echo "</td>";
      } else {
         echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('PDF export font') . "</td><td>";
      Dropdown::showFromArray("pdffont", GLPIPDF::getFontList(),
                              array('value' => $data["pdffont"],
                                    'width' => 200));
      echo "</td>";

      echo "<td>" . __('Keep devices when purging an item') . "</td><td>";
      Dropdown::showYesNo('keep_devices_when_purging_item',
                          $data['keep_devices_when_purging_item']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>".__('Go to created item after creation')."</td>";
      echo "<td>";
      Dropdown::showYesNo("backcreated", $data["backcreated"]);
      echo "</td>";

      echo "<td>" . __('Layout')."</td><td>";
      $layout_options = array('lefttab' => __("Tabs on left"),
                              'classic' => __("Classic view"),
                              'vsplit'  => __("Vertical split"));

      echo "<select name='layout' id='layout-selector'>";
      foreach ($layout_options as $key => $name) {
         $selected = "";
         if ($data["layout"] == $key) {
            $selected = "selected='selected'";
         }
         echo "<option value='$key' $selected>".ucfirst($name)."</option>";

      }
      echo Html::scriptBlock("
         function formatLayout(layout) {
             return \"&nbsp;<img src='../pics/layout_\" + layout.id.toLowerCase() + \".png'/>\"
                     + \"&nbsp;\" + layout.text;
         }
         $(\"#layout-selector\").select2({
             formatResult: formatLayout,
             formatSelection: formatLayout,
             escapeMarkup: function(m) { return m; }
         });
      ");
      echo "</select>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td>".__('Enable ticket timeline')."</td>";
      echo "<td>";
      Dropdown::showYesNo('ticket_timeline', $data['ticket_timeline']);
      echo "</td>";
      echo "<td>" . __('Keep tabs replaced by the ticket timeline')."</td><td>";
      Dropdown::showYesNo('ticket_timeline_keep_replaced_tabs',
                          $data['ticket_timeline_keep_replaced_tabs']);
      echo "</td></tr>";


      if ($oncentral) {
         echo "<tr class='tab_bg_1'><th colspan='4'>".__('Assistance')."</th></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td>".__('Private followups by default')."</td><td>";
         Dropdown::showYesNo("followup_private", $data["followup_private"]);
         echo "</td><td>". __('Show new tickets on the home page') . "</td><td>";
         if (Session::haveRightsOr("ticket",
                                    array(Ticket::READMY, Ticket::READALL, Ticket::READASSIGN))) {
            Dropdown::showYesNo("show_jobs_at_login", $data["show_jobs_at_login"]);
         } else {
            echo Dropdown::getYesNo(0);
         }
         echo " </td></tr>";

         echo "<tr class='tab_bg_2'><td>" . __('Private tasks by default') . "</td><td>";
         Dropdown::showYesNo("task_private", $data["task_private"]);
         echo "</td><td> " . __('Request sources by default') . "</td><td>";
         RequestType::dropdown(array('value' => $data["default_requesttypes_id"],
                                     'name'  => "default_requesttypes_id"));
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td>" . __('Tasks state by default') . "</td><td>";
         Planning::dropdownState("task_state", $data["task_state"]);
         echo "</td><td colspan='2'>&nbsp;</td></tr>";

         echo "<tr class='tab_bg_2'><td>".__('Pre-select me as a technician when creating a ticket').
              "</td><td>";
         if (!$userpref || Session::haveRight('ticket', Ticket::OWN)) {
            Dropdown::showYesNo("set_default_tech", $data["set_default_tech"]);
         } else {
            echo Dropdown::getYesNo(0);
         }
         echo "</td><td>" . __('Automatically refresh the list of tickets (minutes)') . "</td><td>";
         Dropdown::showNumber('refresh_ticket_list', array('value' => $data["refresh_ticket_list"],
                                                           'min'   => 1,
                                                           'max'   => 30,
                                                           'step'  => 1,
                                                           'toadd' => array(0 => __('Never'))));
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __('Priority colors') . "</td>";
         echo "<td colspan='3'>";

         echo "<table><tr>";
         echo "<td>1&nbsp;";
         Html::showColorField('priority_1', array('value' => $data["priority_1"]));
         echo "</td>";
         echo "<td>2&nbsp;";
         Html::showColorField('priority_2', array('value' => $data["priority_2"]));
         echo "</td>";
         echo "<td>3&nbsp;";
         Html::showColorField('priority_3', array('value' => $data["priority_3"]));
         echo "</td>";
         echo "<td>4&nbsp;";
         Html::showColorField('priority_4', array('value' => $data["priority_4"]));
         echo "</td>";
         echo "<td>5&nbsp;";
         Html::showColorField('priority_5', array('value' => $data["priority_5"]));
         echo "</td>";
         echo "<td>6&nbsp;";
         Html::showColorField('priority_6', array('value' => $data["priority_6"]));
         echo "</td>";
         echo "</tr></table>";

         echo "</td></tr>";
      }

      // Only for user
      if (array_key_exists('personal_token', $data)) {
         echo "<tr class='tab_bg_1'><th colspan='4'>". __('Remote access key') ."</th></tr>";

         echo "<tr class='tab_bg_1'><td>" . __('Remote access key');
         if (!empty($data["personal_token"])) {
            //TRANS: %s is the generation date
            echo "<br>".sprintf(__('generated on %s'),
                                Html::convDateTime($data["personal_token_date"]));
         }

         echo "</td><td colspan='3'>";
         echo "<input type='checkbox' name='_reset_personal_token'>&nbsp;".__('Regenerate');
         echo "</td></tr>";
      }

      echo "<tr><th colspan='4'>".__('Due date progression')."</th></tr>";

      echo "<tr class='tab_bg_1'>".
           "<td>".__('OK state color')."</td>";
      echo "<td>";
      Html::showColorField('duedateok_color', array('value' => $data["duedateok_color"]));
      echo "</td><td colspan='2'>&nbsp;</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Warning state color')."</td>";
      echo "<td>";
      Html::showColorField('duedatewarning_color', array('value' => $data["duedatewarning_color"]));
      echo "</td>";
      echo "<td>".__('Warning state threshold')."</td>";
      echo "<td>";
      Dropdown::showNumber("duedatewarning_less", array('value' => $data['duedatewarning_less']));
      $elements = array('%'     => '%',
                        'hours' => _n('Hour', 'Hours', Session::getPluralNumber()),
                        'days'  => _n('Day', 'Days', Session::getPluralNumber()));
      echo "&nbsp;";
      Dropdown::showFromArray("duedatewarning_unit", $elements,
                              array('value' => $data['duedatewarning_unit']));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>".
           "<td>".__('Critical state color')."</td>";
      echo "<td>";
      Html::showColorField('duedatecritical_color', array('value' => $data["duedatecritical_color"]));
      echo "</td>";
      echo "<td>".__('Critical state threshold')."</td>";
      echo "<td>";
      Dropdown::showNumber("duedatecritical_less", array('value' => $data['duedatecritical_less']));
      echo "&nbsp;";
      $elements = array('%'    => '%',
                       'hours' => _n('Hour', 'Hours', Session::getPluralNumber()),
                       'days'  => _n('Day', 'Days', Session::getPluralNumber()));
      Dropdown::showFromArray("duedatecritical_unit", $elements,
                              array('value' => $data['duedatecritical_unit']));
      echo "</td></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='4' class='center'>";
         echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
         echo "</td></tr>";

      echo "</table></div>";
      Html::closeForm();
   }


   /**
    * Display security checks on password
    *
    * @param $field string id of the field containing password to check (default 'password')
    *
    * @since version 0.84
   **/
   static function displayPasswordSecurityChecks($field='password') {
      global $CFG_GLPI;

      printf(__('%1$s: %2$s'), __('Password minimum length'),
                "<span id='password_min_length' class='red'>".$CFG_GLPI['password_min_length'].
                "</span>");

      echo "<script type='text/javascript' >\n";
      echo "function passwordCheck() {\n";
      echo "var pwd = ".Html::jsGetElementbyID($field).";";
      echo "if (pwd.value.length < ".$CFG_GLPI['password_min_length'].") {
            ".Html::jsGetElementByID('password_min_length').".addClass('red');
            ".Html::jsGetElementByID('password_min_length').".removeClass('green');
      } else {
            ".Html::jsGetElementByID('password_min_length').".addClass('green');
            ".Html::jsGetElementByID('password_min_length').".removeClass('red');
      }";
      $needs = array();
      if ($CFG_GLPI["password_need_number"]) {
         $needs[] = "<span id='password_need_number' class='red'>".__('Digit')."</span>";
         echo "var numberRegex = new RegExp('[0-9]', 'g');
         if (false == numberRegex.test(pwd.value)) {
               ".Html::jsGetElementByID('password_need_number').".addClass('red');
               ".Html::jsGetElementByID('password_need_number').".removeClass('green');
         } else {
               ".Html::jsGetElementByID('password_need_number').".addClass('green');
               ".Html::jsGetElementByID('password_need_number').".removeClass('red');
         }";
      }
      if ($CFG_GLPI["password_need_letter"]) {
         $needs[] = "<span id='password_need_letter' class='red'>".__('Lowercase')."</span>";
         echo "var letterRegex = new RegExp('[a-z]', 'g');
         if (false == letterRegex.test(pwd.value)) {
               ".Html::jsGetElementByID('password_need_letter').".addClass('red');
               ".Html::jsGetElementByID('password_need_letter').".removeClass('green');
         } else {
               ".Html::jsGetElementByID('password_need_letter').".addClass('green');
               ".Html::jsGetElementByID('password_need_letter').".removeClass('red');
         }";
      }
      if ($CFG_GLPI["password_need_caps"]) {
         $needs[] = "<span id='password_need_caps' class='red'>".__('Uppercase')."</span>";
         echo "var capsRegex = new RegExp('[A-Z]', 'g');
         if (false == capsRegex.test(pwd.value)) {
               ".Html::jsGetElementByID('password_need_caps').".addClass('red');
               ".Html::jsGetElementByID('password_need_caps').".removeClass('green');
         } else {
               ".Html::jsGetElementByID('password_need_caps').".addClass('green');
               ".Html::jsGetElementByID('password_need_caps').".removeClass('red');
         }";
      }
      if ($CFG_GLPI["password_need_symbol"]) {
         $needs[] = "<span id='password_need_symbol' class='red'>".__('Symbol')."</span>";
         echo "var capsRegex = new RegExp('[^a-zA-Z0-9_]', 'g');
         if (false == capsRegex.test(pwd.value)) {
               ".Html::jsGetElementByID('password_need_symbol').".addClass('red');
               ".Html::jsGetElementByID('password_need_symbol').".removeClass('green');
         } else {
               ".Html::jsGetElementByID('password_need_symbol').".addClass('green');
               ".Html::jsGetElementByID('password_need_symbol').".removeClass('red');
         }";
      }
      echo "}";
      echo '</script>';
      if (count($needs)) {
         echo "<br>";
         printf(__('%1$s: %2$s'), __('Password must contains'), implode(', ',$needs));
      }
   }


   /**
    * Validate password based on security rules
    *
    * @since version 0.84
    *
    * @param $password  string   password to validate
    * @param $display   boolean  display errors messages? (true by default)
    *
    * @return boolean is password valid?
   **/
   static function validatePassword($password, $display=true) {
      global $CFG_GLPI;

      $ok = true;
      if ($CFG_GLPI["use_password_security"]) {
         if (Toolbox::strlen($password) < $CFG_GLPI['password_min_length']) {
            $ok = false;
            if ($display) {
               Session::addMessageAfterRedirect(__('Password too short!'), false, ERROR);
            }
         }
         if ($CFG_GLPI["password_need_number"]
             && !preg_match("/[0-9]+/", $password)) {
            $ok = false;
            if ($display) {
               Session::addMessageAfterRedirect(__('Password must include at least a digit!'),
                                                false, ERROR);
            }
         }
         if ($CFG_GLPI["password_need_letter"]
             && !preg_match("/[a-z]+/", $password)) {
            $ok = false;
            if ($display) {
               Session::addMessageAfterRedirect(__('Password must include at least a lowercase letter!'),
                                                false, ERROR);
            }
         }
         if ($CFG_GLPI["password_need_caps"]
             && !preg_match("/[A-Z]+/", $password)) {
            $ok = false;
            if ($display) {
               Session::addMessageAfterRedirect(__('Password must include at least a uppercase letter!'),
                                                false, ERROR);
            }
         }
         if ($CFG_GLPI["password_need_symbol"]
             && !preg_match("/\W+/", $password)) {
            $ok = false;
            if ($display) {
               Session::addMessageAfterRedirect(__('Password must include at least a symbol!'),
                                                false, ERROR);
            }
         }

      }
      return $ok;
   }


   /**
    * Display a HTML report about systeme information / configuration
   **/
   function showSystemInformations() {
      global $DB, $CFG_GLPI;

      if (!Config::canUpdate()) {
         return false;
      }

      echo "<div class='center' id='tabsbody'>";
      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . __('General setup') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Log Level') . "</td><td>";
      $values[1] = __('1- Critical (login error only)');
      $values[2] = __('2- Severe (not used)');
      $values[3] = __('3- Important (successful logins)');
      $values[4] = __('4- Notices (add, delete, tracking)');
      $values[5] = __('5- Complete (all)');

      Dropdown::showFromArray('event_loglevel', $values,
                              array('value' => $CFG_GLPI["event_loglevel"]));
      echo "</td><td>".__('Maximal number of automatic actions (run by CLI)')."</td><td>";
      Dropdown::showNumber('cron_limit', array('value' => $CFG_GLPI["cron_limit"],
                                               'min'   => 1,
                                               'max'   => 30));
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __('Logs in files (SQL, email, automatic action...)') . "</td><td>";
      Dropdown::showYesNo("use_log_in_files", $CFG_GLPI["use_log_in_files"]);
      echo "</td><td> " . _n('Mysql replica', 'Mysql replicas', 1) . "</td><td>";
      $active = DBConnection::isDBSlaveActive();
      Dropdown::showYesNo("_dbslave_status", $active);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4' class='center b'>".__('Password security policy');
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Password security policy validation') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("use_password_security", $CFG_GLPI["use_password_security"]);
      echo "</td>";
      echo "<td>" . __('Password minimum length') . "</td>";
      echo "<td>";
      Dropdown::showNumber('password_min_length', array('value' => $CFG_GLPI["password_min_length"],
                                                        'min'   => 4,
                                                        'max'   => 30));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Password need digit') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("password_need_number", $CFG_GLPI["password_need_number"]);
      echo "</td>";
      echo "<td>" . __('Password need lowercase character') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("password_need_letter", $CFG_GLPI["password_need_letter"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Password need uppercase character') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("password_need_caps", $CFG_GLPI["password_need_caps"]);
      echo "</td>";
      echo "<td>" . __('Password need symbol') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("password_need_symbol", $CFG_GLPI["password_need_symbol"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4' class='center b'>".__('Maintenance mode');
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Maintenance mode') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("maintenance_mode", $CFG_GLPI["maintenance_mode"]);
      echo "</td>";
      //TRANS: Proxy port
      echo "<td>" . __('Maintenance text') . "</td>";
      echo "<td>";
      echo "<textarea cols='70' rows='4' name='maintenance_text'>".$CFG_GLPI["maintenance_text"];
      echo "</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4' class='center b'>".__('Proxy configuration for upgrade check');
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Server') . "</td>";
      echo "<td><input type='text' name='proxy_name' value='".$CFG_GLPI["proxy_name"]."'></td>";
      //TRANS: Proxy port
      echo "<td>" . __('Port') . "</td>";
      echo "<td><input type='text' name='proxy_port' value='".$CFG_GLPI["proxy_port"]."'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Login') . "</td>";
      echo "<td><input type='text' name='proxy_user' value='".$CFG_GLPI["proxy_user"]."'></td>";
      echo "<td>" . __('Password') . "</td>";
      echo "<td><input type='password' name='proxy_passwd' value='' autocomplete='off'>";
      echo "<br><input type='checkbox' name='_blank_proxy_passwd'>".__('Clear');
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
      echo "</td></tr>";

      echo "</table>";
      Html::closeForm();

      $width = 128;

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>". __('Information about system installation and configuration')."</th></tr>";

       $oldlang = $_SESSION['glpilanguage'];
       // Keep this, for some function call which still use translation (ex showAllReplicateDelay)
       Session::loadLanguage('en_GB');

      // No need to translate, this part always display in english (for copy/paste to forum)

      // Try to compute a better version for .git
      if (is_dir(GLPI_ROOT."/.git")) {
         $dir = getcwd();
         chdir(GLPI_ROOT);
         $returnCode = 1;
         $result     = @exec('git describe --tags 2>&1', $output, $returnCode);
         chdir($dir);
         $ver = ($returnCode ? $CFG_GLPI['version'].'-git' : $result);
      } else {
         $ver = $CFG_GLPI['version'];
      }
      echo "<tr class='tab_bg_1'><td><pre>[code]\n&nbsp;\n";
      echo "GLPI $ver (" . $CFG_GLPI['root_doc']." => " . GLPI_ROOT . ")\n";
      echo "\n</pre></td></tr>";


      echo "<tr><th>Server</th></tr>\n";
      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";
      echo wordwrap("Operating system: ".php_uname()."\n", $width, "\n\t");
      $exts = get_loaded_extensions();
      sort($exts);
      echo wordwrap("PHP ".phpversion().' '.php_sapi_name()." (".implode(', ',$exts).")\n",
                    $width, "\n\t");
      $msg = "Setup: ";

      foreach (array('max_execution_time', 'memory_limit', 'post_max_size', 'safe_mode',
                     'session.save_handler', 'upload_max_filesize') as $key) {
         $msg .= $key.'="'.ini_get($key).'" ';
      }
      echo wordwrap($msg."\n", $width, "\n\t");

      $msg = 'Software: ';
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

      foreach ($DB->getInfo() as $key => $val) {
         echo "$key: $val\n\t";
      }
      echo "\n";

      self::checkWriteAccessToDirs(true);
      toolbox::checkSELinux(true);

      echo "\n</pre></td></tr>";

      self::showLibrariesInformation();

      foreach ($CFG_GLPI["systeminformations_types"] as $type) {
         $tmp = new $type();
         $tmp->showSystemInformations($width);
      }

      Session::loadLanguage($oldlang);

      echo "<tr class='tab_bg_1'><td>[/code]\n</td></tr>";

      echo "<tr class='tab_bg_2'><th>". __('To copy/paste in your support request')."</th></tr>\n";

      echo "</table></div>\n";
   }


   /**
    * @since version 0.90
    *
    * @return string
   **/
   static function getSQLMode() {
      global $DB;

      $query   = "SELECT @@GLOBAL.sql_mode;";
      $results = $DB->query($query);
      if ($DB->numrows($results) > 0) {
         return $DB->results($result, 0);
      }
      return '';
   }


   /**
    * show Libraries information in system information
    *
    * @since version 0.84
   **/
   static function showLibrariesInformation() {

      // No gettext

      echo "<tr class='tab_bg_2'><th>Libraries</th></tr>\n";
      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";

      include_once(GLPI_HTMLAWED);
      echo "htmLawed version " . hl_version() . " in (" . realpath(dirname(GLPI_HTMLAWED)) . ")\n";

      include (GLPI_PHPCAS);
      echo "phpCas version " . phpCAS::getVersion() . " in (" .
            (dirname(GLPI_PHPCAS) ? realpath(dirname(GLPI_PHPCAS)) : "system") . ")\n";

      require_once(GLPI_PHPMAILER_DIR . "/class.phpmailer.php");
      $pm = new PHPMailer();
      echo "PHPMailer version " . $pm->Version . " in (" . realpath(GLPI_PHPMAILER_DIR) . ")\n";

      // EZ component
      echo "ZetaComponent ezcGraph installed in (" . dirname(dirname(GLPI_EZC_BASE)) .
           "):  ".(class_exists('ezcGraph') ? 'OK' : 'KO'). "\n";

      // Zend
      $zv = new Zend\Version\Version;
      echo "Zend Framework version " . $zv::VERSION . " in (" . realpath(GLPI_ZEND_PATH) . ")\n";

      // SimplePie :
      $sp = new SimplePie();
      echo "SimplePie version " . SIMPLEPIE_VERSION . " in (" . realpath(GLPI_SIMPLEPIE_PATH) . ")\n";

      // TCPDF
      include_once(GLPI_TCPDF_DIR.'/include/tcpdf_static.php');
      echo "TCPDF version " . TCPDF_STATIC::getTCPDFVersion() . " in (" . realpath(GLPI_TCPDF_DIR) . ")\n";

      // password_compat
      require_once GLPI_PASSWORD_COMPAT;
      $check = (PasswordCompat\binary\check() ? "Ok" : "KO");
      echo "ircmaxell/password-compat in (" . realpath(dirname(GLPI_PASSWORD_COMPAT)) . "). Compatitility: $check\n";

      echo "\n</pre></td></tr>";
   }


   /**
    * Dropdown for global management config
    *
    * @param $name   select name
    * @param $value  default value
   **/
   static function dropdownGlobalManagement($name, $value) {

      $choices[0] = __('Yes - Restrict to unit management for manual add');
      $choices[1] = __('Yes - Restrict to global management for manual add');
      $choices[2] = __('No');
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

      // Search in order : ID or extjs dico or tinymce dico / native lang / english name
      //                   / extjs dico / tinymce dico
      // ID  or extjs dico or tinymce dico
      foreach ($CFG_GLPI["languages"] as $ID => $language) {
         if ((strcasecmp($lang,$ID) == 0)
             || (strcasecmp($lang,$language[2]) == 0)
             || (strcasecmp($lang,$language[3]) == 0)) {
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
         if (!isset($_SERVER['REQUEST_URI']) ) {
            $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
         }

         $currentdir = getcwd();
         chdir(GLPI_ROOT);
         $glpidir    = str_replace(str_replace('\\', '/',getcwd()), "",
                                   str_replace('\\', '/',$currentdir));
         chdir($currentdir);
         $globaldir  = Html::cleanParametersURL($_SERVER['REQUEST_URI']);
         $globaldir  = preg_replace("/\/[0-9a-zA-Z\.\-\_]+\.php/","",$globaldir);

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
      $unicity->showForm(1, -1);
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Preference' :
            return __('Personalization');

         case 'User' :
            if (User::canUpdate()
                && $item->currentUserHaveMoreRightThan($item->getID())) {
               return __('Settings');
            }
            break;

         case __CLASS__ :
            $tabs[1] = __('General setup');   // Display
            $tabs[2] = __('Default values');   // Prefs
            $tabs[3] = __('Assets');
            $tabs[4] = __('Assistance');
            if (Config::canUpdate()) {
               $tabs[5] = __('System');
            }

            if (DBConnection::isDBSlaveActive()
                && Config::canUpdate()) {
               $tabs[6]  = _n('Mysql replica', 'Mysql replicas', Session::getPluralNumber());  // Slave
            }
            return $tabs;
      }
      return '';
   }


   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1)
    * @param $withtemplate (default 0)
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      if ($item->getType() == 'Preference') {
         $config = new self();
         $user   = new User();
         if ($user->getFromDB(Session::getLoginUserID())) {
            $user->computePreferences();
            $config->showFormUserPrefs($user->fields);
         }

      } else if ($item->getType() == 'User') {
         $config = new self();
         $item->computePreferences();
         $config->showFormUserPrefs($item->fields);

      } else if ($item->getType() == __CLASS__) {
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
    * @param $fordebug boolean display for debug (no html, no gettext required) (false by default)
    *
    * @return 2 : creation error 1 : delete error 0: OK
   **/
   static function checkWriteAccessToDirs($fordebug=false) {
      global $CFG_GLPI;
      $dir_to_check = array(GLPI_CONFIG_DIR
                                    => __('Checking write permissions for setting files'),
                            GLPI_DOC_DIR
                                    => __('Checking write permissions for document files'),
                            GLPI_DUMP_DIR
                                    => __('Checking write permissions for dump files'),
                            GLPI_SESSION_DIR
                                    => __('Checking write permissions for session files'),
                            GLPI_CRON_DIR
                                    => __('Checking write permissions for automatic actions files'),
                            GLPI_GRAPH_DIR
                                    => __('Checking write permissions for graphic files'),
                            GLPI_LOCK_DIR
                                    => __('Checking write permissions for lock files'),
                            GLPI_PLUGIN_DOC_DIR
                                    => __('Checking write permissions for plugins document files'),
                            GLPI_TMP_DIR
                                    => __('Checking write permissions for temporary files'),
                            GLPI_RSS_DIR
                                    => __('Checking write permissions for rss files'),
                            GLPI_UPLOAD_DIR
                                    => __('Checking write permissions for upload files'),
                            GLPI_PICTURE_DIR
                                    => __('Checking write permissions for pictures files'));
      $error = 0;
      foreach ($dir_to_check as $dir => $message) {
         if (!$fordebug) {
            echo "<tr class='tab_bg_1'><td class='left b'>".$message."</td>";
         }
         $tmperror = Toolbox::testWriteAccessToDirectory($dir);

         $errors = array(4 => __('The directory could not be created.'),
                         3 => __('The directory was created but could not be removed.'),
                         2 => __('The file could not be created.'),
                         1 => __("The file was created but can't be deleted."));

         if ($tmperror > 0) {
            if ($fordebug) {
               echo "<img src='".$CFG_GLPI['root_doc']."/pics/ko_min.png'> ".
                     sprintf(__('Check permissions to the directory: %s'), $dir).
                     " ".$errors[$tmperror]."\n";
            } else {
               echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/ko_min.png'><p class='red'>".
                    $errors[$tmperror]."</p> ".
                    sprintf(__('Check permissions to the directory: %s'), $dir).
                    "'</td></tr>";
            }
            $error = 2;
         } else {
            if ($fordebug) {
               echo "<img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt=\"".__s('OK').
                    "\">$dir : OK\n";
            } else {
               echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt=\"".
                          __s('A file and a directory have be created and deleted - Perfect!')."\"
                          title=\"".
                          __s('A file and a directory have be created and deleted - Perfect!')."\">".
                    "</td></tr>";
            }
         }
      }

      // Only write test for GLPI_LOG as SElinux prevent removing log file.
      if (!$fordebug) {
         echo "<tr class='tab_bg_1'><td class='b left'>".
               __('Checking write permissions for log files')."</td>";
      }

      if (error_log("Test\n", 3, GLPI_LOG_DIR."/php-errors.log")) {
         if ($fordebug) {
            echo "<img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt=\"".__s('OK')."\">".
                   GLPI_LOG_DIR." : OK\n";
         } else {
            echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt=\"".
                       __s('A file was created - Perfect!')."\" title=\"".
                       __s('A file was created - Perfect!')."\"></td></tr>";
         }

      } else {
         if ($fordebug) {
            echo "<img src='".$CFG_GLPI['root_doc']."/pics/warning_min.png'>".
                  sprintf(__('Check permissions to the directory: %s'), GLPI_LOG_DIR)."\n";
         } else {
            echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/warning_min.png'>".
                 "<p class='red'>".__('The file could not be created.')."</p>".
                 sprintf(__('Check permissions to the directory: %s'), GLPI_LOG_DIR)."</td></tr>";
         }
         $error = 1;
      }

      $oldhand = set_error_handler(function($errno, $errmsg, $filename, $linenum, $vars){return true;});
      $oldlevel = error_reporting(0);
      /* TODO: could be improved, only default vhost checked */
      if ($fic = fopen('http://localhost'.$CFG_GLPI['root_doc'].'/index.php', 'r')) {
         fclose($fic);
         if (!$fordebug) {
            echo "<tr class='tab_bg_1'><td class='b left'>".
               __('Web access to files directory is protected')."</td>";
         }
         if ($fic = fopen('http://localhost'.$CFG_GLPI['root_doc'].'/files/_log/php-errors.log', 'r')) {
            fclose($fic);
            if ($fordebug) {
               echo "<img src='".$CFG_GLPI['root_doc']."/pics/warning_min.png'>".
                     __('Web access to the files directory, should not be allowed')."\n".
                     __('Check the .htaccess file and the web server configuration.')."\n";
            } else {
               echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/warning_min.png'>".
                    "<p class='red'>".__('Web access to the files directory, should not be allowed')."<br/>".
                    __('Check the .htaccess file and the web server configuration.')."</p></td></tr>";
            }
            $error = 1;
         } else {
            if ($fordebug) {
               echo "<img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt=\"".
                     __s('Web access to files directory is protected')."\">".
                     __s('Web access to files directory is protected')." : OK\n";
            } else {
               echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/ok_min.png' alt=\"".
                     __s('Web access to files directory is protected')."\" title=\"".
                     __s('Web access to files directory is protected')."\"></td></tr>";
            }
         }
      }
      error_reporting($oldlevel);
      set_error_handler($oldhand);

      return $error;
   }


   /**
    * Get current DB version (compatible with all version of GLPI)
    *
    * @since version 0.85
    *
    * @return DB version
   **/
   static function getCurrentDBVersion() {
      global $DB;

      if (!TableExists('glpi_configs')) {
         $query = "SELECT `version`
                   FROM `glpi_config`
                   WHERE `id` = '1'";
      } else if (FieldExists('glpi_configs', 'version')) {
         $query = "SELECT `version`
                   FROM `glpi_configs`
                   WHERE `id` = '1'";
      } else {
         $query = "SELECT `value` as version
                   FROM `glpi_configs`
                   WHERE 'context' = 'core'
                         AND 'name' = 'version'";
      }

      $result = $DB->query($query);
      $config = $DB->fetch_assoc($result);
      return trim($config['version']);
   }


   /**
    * Get config values
    *
    * @since version 0.85
    *
    * @param $context  string   context to get values (default for glpi is core)
    * @param $names    array    of config names to get
    *
    * @return array of config values
   **/
   static function getConfigurationValues($context, array $names=array()) {
      global $DB;

      if (count($names) == 0) {
         $query = "SELECT *
                   FROM `glpi_configs`
                   WHERE `context` = '$context'";
      } else {
         $query = "SELECT *
                   FROM `glpi_configs`
                   WHERE `context` = '$context'
                     AND `name` IN ('".implode("', '", $names)."')";
      }
      $result = array();
      foreach ($DB->request($query) as $line) {
         $result[$line['name']] = $line['value'];
      }
      return $result;
   }


   /**
    * Set config values : create or update entry
    *
    * @since version 0.85
    *
    * @param $context  string context to get values (default for glpi is core)
    * @param $values   array  of config names to set
    *
    * @return array of config values
   **/
   static function setConfigurationValues($context, array $values=array()) {

      $config = new self();
      foreach ($values as $name => $value) {
         if ($config->getFromDBByQuery("WHERE `context` = '$context'
                                              AND `name` = '$name'")) {

            $input = array('id'      => $config->getID(),
                           'context' => $context,
                           'value'   => $value);

            $config->update($input);

         } else {
            $input = array('context' => $context,
                           'name'    => $name,
                           'value'   => $value);

            $config->add($input);
         }
      }
   }

   /**
    * Delete config entries
    *
    * @since version 0.85
    *
    * @param $context string  context to get values (default for glpi is core)
    * @param $values  array   of config names to delete
    *
    * @return array of config values
   **/
   static function deleteConfigurationValues($context, array $values= array()) {

      $config = new self();
      foreach ($values as $value) {
         if ($config->getFromDBByQuery("WHERE `context` = '$context'
                                              AND `name` = '$value'")) {
            $config->delete(array('id' => $config->getID()));
         }
      }
   }


   /**
    * @since version 0.85
    *
    * @see commonDBTM::getRights()
   **/
   function getRights($interface='central') {

      $values = parent::getRights();
      unset($values[CREATE], $values[DELETE],
            $values[PURGE]);

      return $values;
   }

}
?>
