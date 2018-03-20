<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/** @file
* @brief
*/

use Glpi\Exception\PasswordTooWeakException;
use Zend\Cache\Storage\AvailableSpaceCapableInterface;
use Zend\Cache\Storage\TotalSpaceCapableInterface;
use Zend\Cache\Storage\FlushableInterface;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
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

   static $undisclosedFields      = ['proxy_passwd', 'smtp_passwd'];


   static function getTypeName($nb = 0) {
      return __('Setup');
   }


   /**
    *  @see CommonGLPI::getMenuContent()
    *
    *   @since version 0.85
   **/
   static function getMenuContent() {
      global $CFG_GLPI;

      $menu = [];
      if (static::canView()) {
         $menu['title']   = _x('setup', 'General');
         $menu['page']    = '/front/config.form.php';

         $menu['options']['apiclient']['title']           = APIClient::getTypeName(Session::getPluralNumber());
         $menu['options']['apiclient']['page']            = '/front/config.form.php?forcetab=Config$8';
         $menu['options']['apiclient']['links']['search'] = '/front/config.form.php?forcetab=Config$8';
         $menu['options']['apiclient']['links']['add']    = '/front/apiclient.form.php';
      }
      if (count($menu)) {
         return $menu;
      }
      return false;
   }


   static function canCreate() {
      return false;
   }


   function canViewItem() {
      if (isset($this->fields['context']) &&
         ($this->fields['context'] == 'core' ||
         in_array($this->fields['context'], $_SESSION['glpi_plugins']))) {
         return true;
      }
      return false;
   }


   function defineTabs($options = []) {

      $ong = [];
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
         $tab = [];

         for ($urgency=1; $urgency<=5; $urgency++) {
            for ($impact=1; $impact<=5; $impact++) {
               $priority               = $input["_matrix_${urgency}_${impact}"];
               $tab[$urgency][$impact] = $priority;
            }
         }

         $input['priority_matrix'] = exportArrayToDB($tab);
         $input['urgency_mask']    = 0;
         $input['impact_mask']     = 0;

         for ($i=1; $i<=5; $i++) {
            if ($input["_urgency_${i}"]) {
               $input['urgency_mask'] += (1<<$i);
            }

            if ($input["_impact_${i}"]) {
               $input['impact_mask'] += (1<<$i);
            }
         }
      }

      // lock mechanism update
      if (isset( $input['lock_use_lock_item'])) {
          $input['lock_item_list'] = exportArrayToDB((isset($input['lock_item_list'])
                                                      ? $input['lock_item_list'] : []));
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

   static public function unsetUndisclosedFields(&$fields) {
      if (isset($fields['context']) && isset($fields['name'])) {
         if ($fields['context'] == 'core'
            && in_array($fields['name'], self::$undisclosedFields)) {
            unset($fields['value']);
         } else {
            $fields = Plugin::doHookFunction('undiscloseConfigValue', $fields);
         }
      }
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

      $rand = mt_rand();
      $canedit = Session::haveRight(self::$rightname, UPDATE);

      if ($canedit) {
         echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
      }
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . __('General setup') . "</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='url_base'>" . __('URL of the application') . "</label></td>";
      echo "<td colspan='3'><input type='text' name='url_base' id='url_base' size='80' value='".$CFG_GLPI["url_base"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='text_login'>" . __('Text in the login box') . "</label></td>";
      echo "<td colspan='3'>";
      echo "<textarea cols='70' rows='4' name='text_login' id='text_login'>".$CFG_GLPI["text_login"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td width='30%'><label for='dropdown_use_public_faq$rand'>" . __('Allow FAQ anonymous access') . "</label></td><td  width='20%'>";
      Dropdown::showYesNo("use_public_faq", $CFG_GLPI["use_public_faq"], -1, ['rand' => $rand]);
      echo "</td><td width='30%'><label for='helpdesk_doc_url'>" . __('Simplified interface help link') . "</label></td>";
      echo "<td><input size='22' type='text' name='helpdesk_doc_url' id='helpdesk_doc_url' value='" .
                 $CFG_GLPI["helpdesk_doc_url"] . "'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_list_limit_max$rand'>" . __('Default search results limit (page)')."</td><td>";
      Dropdown::showNumber("list_limit_max", ['value' => $CFG_GLPI["list_limit_max"],
                                              'min'   => 5,
                                              'max'   => 200,
                                              'step'  => 5,
                                              'rand'  => $rand]);
      echo "</td><td><label for='central_doc_url'>" . __('Standard interface help link') . "</label></td>";
      echo "<td><input size='22' type='text' name='central_doc_url' id='central_doc_url' value='" .
                 $CFG_GLPI["central_doc_url"] . "'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_cut$rand'>" . __('Default characters limit (summary text boxes)') . "</label></td><td>";
      Dropdown::showNumber('cut', ['value' => $CFG_GLPI["cut"],
                                   'min'   => 50,
                                   'max'   => 500,
                                   'step'  => 50,
                                   'rand'  => $rand]);
      echo "</td><td><label for='dropdown_url_maxlength$rand'>" . __('Default url length limit') . "</td><td>";
      Dropdown::showNumber('url_maxlength', ['value' => $CFG_GLPI["url_maxlength"],
                                             'min'   => 20,
                                             'max'   => 80,
                                             'step'  => 5,
                                             'rand'  => $rand]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td><label for='dropdown_decimal_number$rand'>" .__('Default decimals limit') . "</label></td><td>";
      Dropdown::showNumber("decimal_number", ['value' => $CFG_GLPI["decimal_number"],
                                              'min'   => 1,
                                              'max'   => 4,
                                              'rand'  => $rand]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_translate_dropdowns$rand'>" . __("Translation of dropdowns") . "</label></td><td>";
      Dropdown::showYesNo("translate_dropdowns", $CFG_GLPI["translate_dropdowns"], -1, ['rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_translate_kb$rand'>" . __("Knowledge base translation") . "</label></td><td>";
      Dropdown::showYesNo("translate_kb", $CFG_GLPI["translate_kb"], -1, ['rand' => $rand]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>".__('Dynamic display').
           "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_dropdown_max$rand'>".
            __('Page size for dropdown (paging using scroll)').
            "</label></td><td>";
      Dropdown::showNumber('dropdown_max', ['value' => $CFG_GLPI["dropdown_max"],
                                            'min'   => 1,
                                            'max'   => 200,
                                            'rand'  => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_use_ajax_autocompletion$rand'>" . __('Autocompletion of text fields') . "</label></td><td>";
      Dropdown::showYesNo("use_ajax_autocompletion", $CFG_GLPI["use_ajax_autocompletion"], -1, ['rand' => $rand]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_ajax_limit_count$rand'>". __("Don't show search engine in dropdowns if the number of items is less than").
           "</label></td><td>";
      Dropdown::showNumber('ajax_limit_count', ['value' => $CFG_GLPI["ajax_limit_count"],
                                                'min'   => 1,
                                                'max'   => 200,
                                                'step'  => 1,
                                                'toadd' => [0 => __('Never')],
                                                'rand'  => $rand]);
      echo "<td colspan='2'></td>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>".__('Search engine')."</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_allow_search_view$rand'>" . __('Items seen') . "</label></td><td>";
      $values = [0 => __('No'),
                 1 => sprintf(__('%1$s (%2$s)'), __('Yes'), __('last criterion')),
                 2 => sprintf(__('%1$s (%2$s)'), __('Yes'), __('default criterion'))];
      Dropdown::showFromArray('allow_search_view', $values,
                              ['value' => $CFG_GLPI['allow_search_view'], 'rand' => $rand]);
      echo "</td><td><label for='dropdown_allow_search_global$rand'>". __('Global search')."</label></td><td>";
      if ($CFG_GLPI['allow_search_view']) {
         Dropdown::showYesNo('allow_search_global', $CFG_GLPI['allow_search_global'], -1, ['rand' => $rand]);
      } else {
         echo Dropdown::getYesNo(0);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_allow_search_all$rand'>" . __('All') . "</label></td><td>";
      $values = [0 => __('No'),
                 1 => sprintf(__('%1$s (%2$s)'), __('Yes'), __('last criterion'))];
      Dropdown::showFromArray('allow_search_all', $values,
                              ['value' => $CFG_GLPI['allow_search_all'], 'rand' => $rand]);
      echo "</td><td colspan='2'></td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>".__('Item locks')."</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_lock_use_lock_item$rand'>" . __('Use locks') . "</label></td><td>";
      Dropdown::showYesNo("lock_use_lock_item", $CFG_GLPI["lock_use_lock_item"], -1, ['rand' => $rand]);
      echo "</td><td><label for='dropdown_lock_lockprofile_id$rand'>". __('Profile to be used when locking items')."</label></td><td>";
      if ($CFG_GLPI["lock_use_lock_item"]) {
         Profile::dropdown(['name'                  => 'lock_lockprofile_id',
                            'display_emptychoice'   => true,
                            'value'                 => $CFG_GLPI['lock_lockprofile_id'],
                            'rand'                  => $rand]);
      } else {
         echo dropdown::getDropdownName(Profile::getTable(), $CFG_GLPI['lock_lockprofile_id']);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_lock_item_list$rand'>" . __('List of items to lock') . "</label></td>";
      echo "<td colspan=3>";
      Dropdown::showFromArray('lock_item_list', ObjectLock::getLockableObjects(),
                              ['values'   => $CFG_GLPI['lock_item_list'],
                               'width'    => '100%',
                               'multiple' => true,
                               'readonly' => !$CFG_GLPI["lock_use_lock_item"],
                               'rand'     => $rand]);

      echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>".__('Auto Login').
           "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_login_remember_time$rand'>". __('Time to allow "Remember Me"').
           "</label></td><td>";
      Dropdown::showTimeStamp('login_remember_time', ['value' => $CFG_GLPI["login_remember_time"],
                                                     'emptylabel'   => __('Disabled'),
                                                     'min'   => 0,
                                                     'max'   => MONTH_TIMESTAMP * 2,
                                                     'step'  => DAY_TIMESTAMP,
                                                     'toadd' => [HOUR_TIMESTAMP, HOUR_TIMESTAMP * 2, HOUR_TIMESTAMP * 6, HOUR_TIMESTAMP * 12],
                                                     'rand'  => $rand]);
      echo "<td><label for='dropdown_login_remember_default$rand'>" . __("Default state of checkbox") . "</label></td><td>";
      Dropdown::showYesNo("login_remember_default", $CFG_GLPI["login_remember_default"], -1, ['rand' => $rand]);
      echo "</td>";
      echo "</td></tr>";

      if ($canedit) {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='4' class='center'>";
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
   function showFormInventory() {
      global $DB, $CFG_GLPI;

      if (!self::canView()) {
         return false;
      }

      $rand = mt_rand();
      $canedit = Config::canUpdate();
      if ($canedit) {
         echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
      }
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . __('Assets') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td width='30%'><label for='dropdown_auto_create_infocoms$rand'>". __('Enable the financial and administrative information by default')."</label></td>";
      echo "<td  width='20%'>";
      Dropdown::ShowYesNo('auto_create_infocoms', $CFG_GLPI["auto_create_infocoms"], -1, ['rand' => $rand]);
      echo "</td><td width='20%'><label for='dropdown_monitors_management_restrict$rand'>" . __('Restrict monitor management') . "</label></td>";
      echo "<td width='30%'>";
      $this->dropdownGlobalManagement ("monitors_management_restrict",
                                       $CFG_GLPI["monitors_management_restrict"],
                                       $rand);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td><label for='dropdown_softwarecategories_id_ondelete$rand'>" . __('Software category deleted by the dictionary rules') .
           "</label></td><td>";
      SoftwareCategory::dropdown(['value' => $CFG_GLPI["softwarecategories_id_ondelete"],
                                  'name'  => "softwarecategories_id_ondelete",
                                  'rand'  => $rand]);
      echo "</td><td><label for='dropdown_peripherals_management_restrict$rand'>" . __('Restrict device management') . "</label></td><td>";
      $this->dropdownGlobalManagement ("peripherals_management_restrict",
                                       $CFG_GLPI["peripherals_management_restrict"],
                                       $rand);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='showdate$rand'>" .__('End of fiscal year') . "</label></td><td>";
      Html::showDateField("date_tax", ['value'      => $CFG_GLPI["date_tax"],
                                       'maybeempty' => false,
                                       'canedit'    => true,
                                       'min'        => '',
                                       'max'        => '',
                                       'showyear'   => false,
                                       'rand'       => $rand]);
      echo "</td><td><label for='dropdown_phones_management_restrict$rand'>" . __('Restrict phone management') . "</label></td><td>";
      $this->dropdownGlobalManagement ("phones_management_restrict",
                                       $CFG_GLPI["phones_management_restrict"],
                                       $rand);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_use_autoname_by_entity$rand'>" . __('Automatic fields (marked by *)') . "</label></td><td>";
      $tab = [0 => __('Global'),
              1 => __('By entity')];
      Dropdown::showFromArray('use_autoname_by_entity', $tab,
                              ['value' => $CFG_GLPI["use_autoname_by_entity"], 'rand' => $rand]);
      echo "</td><td><label for='dropdown_printers_management_restrict$rand'>" . __('Restrict printer management') . "</label></td><td>";
      $this->dropdownGlobalManagement("printers_management_restrict",
                                      $CFG_GLPI["printers_management_restrict"],
                                      $rand);
      echo "</td></tr>";

      echo "</table>";

      if (Session::haveRightsOr("transfer", [CREATE, UPDATE])
          && Session::isMultiEntitiesMode()) {
         echo "<br><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . __('Automatic transfer of computers') . "</th></tr>";
         echo "<tr class='tab_bg_2'>";
         echo "<td><label for='dropdown_transfers_id_auto$rand'>" . __('Template for the automatic transfer of computers in another entity') .
              "</label></td><td>";
         Transfer::dropdown(['value'      => $CFG_GLPI["transfers_id_auto"],
                             'name'       => "transfers_id_auto",
                             'emptylabel' => __('No automatic transfer'),
                             'rand'       => $rand]);
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

      $fields = ["contact", "user", "group", "location"];
      echo "<tr class='tab_bg_2'>";
      echo "<td> " . __('When connecting or updating') . "</td>";
      $values[0] = __('Do not copy');
      $values[1] = __('Copy');

      foreach ($fields as $field) {
         echo "<td>";
         $fieldname = "is_".$field."_autoupdate";
         Dropdown::showFromArray($fieldname, $values, ['value' => $CFG_GLPI[$fieldname]]);
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
         Dropdown::showFromArray($fieldname, $values, ['value' => $CFG_GLPI[$fieldname]]);
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

      echo "<tr class='tab_bg_2'><th colspan='4'>" . _n('SQL replica', 'SQL replicas', Session::getPluralNumber()) .
           "</th></tr>";
      $DBslave = DBConnection::getDBSlaveConf();

      if (is_array($DBslave->dbhost)) {
         $host = implode(' ', $DBslave->dbhost);
      } else {
         $host = $DBslave->dbhost;
      }
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('SQL server (MariaDB or MySQL)') . "</td>";
      echo "<td><input type='text' name='_dbreplicate_dbhost' size='40' value='$host'></td>";
      echo "<td>" . __('Database') . "</td>";
      echo "<td><input type='text' name='_dbreplicate_dbdefault' value='".$DBslave->dbdefault."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('SQL user') . "</td>";
      echo "<td><input type='text' name='_dbreplicate_dbuser' value='".$DBslave->dbuser."'></td>";
      echo "<td>" . __('SQL password') . "</td>";
      echo "<td><input type='password' name='_dbreplicate_dbpassword' value='".
                 rawurldecode($DBslave->dbpassword)."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Use the slave for the search engine') . "</td><td>";
      $values = [0 => __('Never'),
                      1 => __('If synced (all changes)'),
                      2 => __('If synced (current user changes)'),
                      3 => __('If synced or read-only account'),
                      4 => __('Always')];
      Dropdown::showFromArray('use_slave_for_search', $values,
                              ['value' => $CFG_GLPI["use_slave_for_search"]]);
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
    * Print the config form for External API
    *
    * @since 9.1
    * @return Nothing (display)
   **/
   function showFormAPI() {
      global $CFG_GLPI;

      if (!self::canView()) {
         return false;
      }

      echo "<div class='center spaced' id='tabsbody'>";

      $rand = mt_rand();
      $canedit = Config::canUpdate();
      if ($canedit) {
         echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
      }
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . __('API') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='url_base_api'>" . __('URL of the API') . "</label></td>";
      echo "<td colspan='3'><input type='text' name='url_base_api' id='url_base_api' size='80' value='".$CFG_GLPI["url_base_api"]."'></td>";
      echo "</tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_enable_api$rand'>" . __("Enable Rest API") . "</label></td>";
      echo "<td>";
      Dropdown::showYesNo("enable_api", $CFG_GLPI["enable_api"], -1, ['rand' => $rand]);
      echo "</td>";
      if ($CFG_GLPI["enable_api"]) {
         echo "<td colspan='2'>";
         $inline_doc_api = trim($CFG_GLPI['url_base_api'], '/')."/";
         echo "<a href='$inline_doc_api'>".__("API inline Documentation")."</a>";
         echo "</td>";
      }
      echo "</tr>";

      echo "<tr><th colspan='4'>" . __('Authentication') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_enable_api_login_credentials$rand'>";
      echo __("Enable login with credentials")."</label>&nbsp;";
      Html::showToolTip(__("Allow to login to API and get a session token with user credentials"));
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("enable_api_login_credentials", $CFG_GLPI["enable_api_login_credentials"], -1, ['rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_enable_api_login_external_token$rand'>";
      echo __("Enable login with external token")."</label>&nbsp;";
      Html::showToolTip(__("Allow to login to API and get a session token with user external token. See Remote access key in user Settings tab "));
      echo "</td>";
      echo "<td>";
      Dropdown::showYesNo("enable_api_login_external_token", $CFG_GLPI["enable_api_login_external_token"], -1, ['rand' => $rand]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
      echo "<br><br><br>";
      echo "</td></tr>";

      echo "</table>";
      Html::closeForm();

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><td>";
      echo "<hr>";
      $buttons["apiclient.form.php"] = __('Add API client');
      $title = "";
      Html::displayTitle("",
                         self::getTypeName(Session::getPluralNumber()),
                         "",
                         $buttons);
      Search::show("APIClient");
      echo "</td></tr>";
      echo "</table></div>";
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

      $rand = mt_rand();
      $canedit = Config::canUpdate();
      if ($canedit) {
         echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
      }
      echo "<div class='center spaced' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . __('Assistance') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td width='30%'><label for='dropdown_time_step$rand'>" . __('Step for the hours (minutes)') . "</label></td>";
      echo "<td width='20%'>";
      Dropdown::showNumber('time_step', ['value' => $CFG_GLPI["time_step"],
                                         'min'   => 30,
                                         'max'   => 60,
                                         'step'  => 30,
                                         'toadd' => [1  => 1,
                                                     5  => 5,
                                                     10 => 10,
                                                     15 => 15,
                                                     20 => 20],
                                         'rand'  => $rand]);
      echo "</td>";
      echo "<td width='30%'><label for='dropdown_planning_begin$rand'>" .__('Limit of the schedules for planning') . "</label></td>";
      echo "<td width='20%'>";
      Dropdown::showHours('planning_begin', ['value' => $CFG_GLPI["planning_begin"], 'rand' => $rand]);
      echo "&nbsp;<label for='dropdown_planning_end$rand'>-></label>&nbsp;";
      Dropdown::showHours('planning_end', ['value' => $CFG_GLPI["planning_end"], 'rand' => $rand]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_default_mailcollector_filesize_max$rand'>".__('Default file size limit imported by the mails receiver')."</label></td><td>";
      MailCollector::showMaxFilesize('default_mailcollector_filesize_max',
                                     $CFG_GLPI["default_mailcollector_filesize_max"],
                                     $rand);
      echo "</td>";

      echo "<td><label for='dropdown_use_rich_text$rand'>" . __('Use rich text for helpdesk') . "</label></td><td>";
      $id                 = 'alert'.mt_rand();
      $param['on_change'] = '$("#'.$id.'").html("");
            if ($(this).val() == 0) {
               $("#'.$id.'").html("<br>'.__('You will lose the formatting of your data').'");
            }';
      $param['rand'] = $rand;
      Dropdown::showYesNo("use_rich_text", $CFG_GLPI["use_rich_text"], -1, $param);
      echo "<span class='red' id='".$id."'></span>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_documentcategories_id_forticket$rand'>" . __('Default heading when adding a document to a ticket') . "</label></td><td>";
      DocumentCategory::dropdown(['value' => $CFG_GLPI["documentcategories_id_forticket"],
                                  'name'  => "documentcategories_id_forticket",
                                  'rand'  => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_default_software_helpdesk_visible$rand'>" . __('By default, a software may be linked to a ticket') . "</label></td><td>";
      Dropdown::showYesNo("default_software_helpdesk_visible",
                          $CFG_GLPI["default_software_helpdesk_visible"],
                          -1,
                          ['rand' => $rand]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_keep_tickets_on_delete$rand'>" . __('Keep tickets when purging hardware in the inventory') . "</label></td><td>";
      Dropdown::showYesNo("keep_tickets_on_delete", $CFG_GLPI["keep_tickets_on_delete"], -1, ['rand' => $rand]);
      echo "</td><td><label for='dropdown_use_check_pref$rand'>".__('Show personnal information in new ticket form (simplified interface)');
      echo "</label></td><td>";
      Dropdown::showYesNo('use_check_pref', $CFG_GLPI['use_check_pref'], -1, ['rand' => $rand]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_use_anonymous_helpdesk$rand'>" .__('Allow anonymous ticket creation (helpdesk.receiver)') . "</label></td><td>";
      Dropdown::showYesNo("use_anonymous_helpdesk", $CFG_GLPI["use_anonymous_helpdesk"], -1, ['rand' => $rand]);
      echo "</td><td><label for='dropdown_use_anonymous_followups$rand'>" . __('Allow anonymous followups (receiver)') . "</label></td><td>";
      Dropdown::showYesNo("use_anonymous_followups", $CFG_GLPI["use_anonymous_followups"], -1, ['rand' => $rand]);
      echo "</td></tr>";

      echo "</table>";

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='7'>" . __('Matrix of calculus for priority');
      echo "<input type='hidden' name='_matrix' value='1'></th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td class='b right' colspan='2'>".__('Impact')."</td>";

      for ($impact=5; $impact>=1; $impact--) {
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

      for ($impact=5; $impact>=1; $impact--) {
         echo "<td>&nbsp;</td>";
      }
      echo "</tr>";

      for ($urgency=5; $urgency>=1; $urgency--) {
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

         for ($impact=5; $impact>=1; $impact--) {
            $pri = round(($urgency+$impact)/2);

            if (isset($CFG_GLPI['priority_matrix'][$urgency][$impact])) {
               $pri = $CFG_GLPI['priority_matrix'][$urgency][$impact];
            }

            if ($isurgency[$urgency] && $isimpact[$impact]) {
               $bgcolor=$_SESSION["glpipriority_$pri"];
               echo "<td class='center' bgcolor='$bgcolor'>";
               Ticket::dropdownPriority(['value' => $pri,
                                              'name'  => "_matrix_${urgency}_${impact}"]);
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
         echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
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
   function showFormUserPrefs($data = []) {
      global $DB, $CFG_GLPI;

      $oncentral = (Session::getCurrentInterface() == "central");
      $userpref  = false;
      $url       = Toolbox::getItemTypeFormURL(__CLASS__);
      $rand      = mt_rand();

      $canedit = Config::canUpdate();
      if (array_key_exists('last_login', $data)) {
         $userpref = true;
         if ($data["id"] === Session::getLoginUserID()) {
            $url  = $CFG_GLPI['root_doc']."/front/preference.php";
         } else {
            $url  = $CFG_GLPI['root_doc']."/front/user.form.php";
         }
      }

      if ($canedit || $userpref) {
         echo "<form name='form' action='$url' method='post'>";
      }

      // Only set id for user prefs
      if ($userpref) {
         echo "<input type='hidden' name='id' value='".$data['id']."'>";
      }
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . __('Personalization') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td width='30%'><label for='dropdown_language$rand'>" . ($userpref?__('Language'):__('Default language')) . "</label></td>";
      echo "<td width='20%'>";
      if (Config::canUpdate()
          || !GLPI_DEMO_MODE) {
         Dropdown::showLanguages("language", ['value' => $data["language"], 'rand' => $rand]);
      } else {
         echo "&nbsp;";
      }

      echo "<td width='30%'><label for='dropdown_date_format$rand'>" . __('Date format') ."</label></td>";
      echo "<td width='20%'>";
      Dropdown::showFromArray('date_format', Toolbox::phpDateFormats(), ['value' => $data["date_format"], 'rand' => $rand]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_names_format$rand'>".__('Display order of surnames firstnames')."</label></td><td>";
      $values = [User::REALNAME_BEFORE  => __('Surname, First name'),
                 User::FIRSTNAME_BEFORE => __('First name, Surname')];
      Dropdown::showFromArray('names_format', $values, ['value' => $data["names_format"], 'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_number_format$rand'>" .__('Number format') . "</label></td>";
      $values = [0 => '1 234.56',
                 1 => '1,234.56',
                 2 => '1 234,56',
                 3 => '1234.56',
                 4 => '1234,56'];
      echo "<td>";
      Dropdown::showFromArray('number_format', $values, ['value' => $data["number_format"], 'rand' => $rand]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_list_limit$rand'>" . __('Results to display by page')."</label></td><td>";
      // Limit using global config
      $value = (($data['list_limit'] < $CFG_GLPI['list_limit_max'])
                ? $data['list_limit'] : $CFG_GLPI['list_limit_max']);
      Dropdown::showNumber('list_limit', ['value' => $value,
                                          'min'   => 5,
                                          'max'   => $CFG_GLPI['list_limit_max'],
                                          'step'  => 5,
                                          'rand'  => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_backcreated$rand'>".__('Go to created item after creation')."</label></td>";
      echo "<td>";
      Dropdown::showYesNo("backcreated", $data["backcreated"], -1, ['rand' => $rand]);
      echo "</td>";

      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      if ($oncentral) {
         echo "<td><label for='dropdown_use_flat_dropdowntree$rand'>" . __('Display the complete name in tree dropdowns') . "</label></td><td>";
         Dropdown::showYesNo('use_flat_dropdowntree', $data["use_flat_dropdowntree"], -1, ['rand' => $rand]);
         echo "</td>";
      } else {
         echo "<td colspan='2'>&nbsp;</td>";
      }

      if (!$userpref
          || ($CFG_GLPI['show_count_on_tabs'] != -1)) {
         echo "<td><label for='dropdown_show_count_on_tabs$rand'>".__('Display counters')."</label></td><td>";

         $values = [0 => __('No'),
                    1 => __('Yes')];

         if (!$userpref) {
            $values[-1] = __('Never');
         }
         Dropdown::showFromArray('show_count_on_tabs', $values,
                                 ['value' => $data["show_count_on_tabs"], 'rand' => $rand]);
         echo "</td>";
      } else {
         echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      if ($oncentral) {
         echo "<td><label for='dropdown_is_ids_visible$rand'>" . __('Show GLPI ID') . "</label></td><td>";
         Dropdown::showYesNo("is_ids_visible", $data["is_ids_visible"], -1, ['rand' => $rand]);
         echo "</td>";
      } else {
         echo "<td colspan='2'></td>";
      }

      echo "<td><label for='dropdown_keep_devices_when_purging_item$rand'>" . __('Keep devices when purging an item') . "</label></td><td>";
      Dropdown::showYesNo('keep_devices_when_purging_item',
                          $data['keep_devices_when_purging_item'],
                          -1,
                          ['rand' => $rand]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_notification_to_myself$rand'>" . __('Notifications for my changes') . "</label></td><td>";
      Dropdown::showYesNo("notification_to_myself", $data["notification_to_myself"], -1, ['rand' => $rand]);
      echo "</td>";
      if ($oncentral) {
         echo "<td><label for='dropdown_display_count_on_home$rand'>".__('Results to display on home page')."</label></td><td>";
         Dropdown::showNumber('display_count_on_home',
                              ['value' => $data['display_count_on_home'],
                               'min'   => 0,
                               'max'   => 30,
                               'rand'  => $rand]);
         echo "</td>";
      } else {
         echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_pdffont$rand'>" . __('PDF export font') . "</label></td><td>";
      Dropdown::showFromArray("pdffont", GLPIPDF::getFontList(),
                              ['value' => $data["pdffont"],
                               'width' => 200,
                               'rand'  => $rand]);
      echo "</td>";

      echo "<td><label for='dropdown_csv_delimiter$rand'>".__('CSV delimiter')."</label></td><td>";
      $values = [';' => ';',
                 ',' => ','];
      Dropdown::showFromArray('csv_delimiter', $values, ['value' => $data["csv_delimiter"], 'rand' => $rand]);

      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='theme-selector'>" . __("Color palette") . "</label></td><td>";
      $themes_files = scandir(GLPI_ROOT."/css/palettes/");
      echo "<select name='palette' id='theme-selector'>";
      $themes = [];
      foreach ($themes_files as $key => $file) {
         if (strpos($file, ".css") !== false) {
            $name     = substr($file, 0, -4);
            if (strpos($name, '.min') !== false) {
               $name     = substr($name, 0, -4);
               if (isset($themes[$name])) {
                  continue;
               }
            }
            $selected = "";
            if ($data["palette"] == $name) {
               $selected = "selected='selected'";
            }
            echo "<option value='$name' $selected>".ucfirst($name)."</option>";
            $themes[$name] = true;
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
         $('label[for=theme-selector]').on('click', function(){ $('#theme-selector').select2('open'); });
      ");
      echo "</select>";
      echo "</td>";
      echo "<td><label for='layout-selector'>" . __('Layout')."</label></td><td>";
      $layout_options = ['lefttab' => __("Tabs on left"),
                         'classic' => __("Classic view"),
                         'vsplit'  => __("Vertical split")];

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
         $('label[for=layout-selector]').on('click', function(){ $('#layout-selector').select2('open'); });
      ");
      echo "</select>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td><label for='dropdown_highcontrast_css$rand'>".__('Enable high contrast')."</label></td>";
      echo "<td>";
      Dropdown::showYesNo('highcontrast_css', $data['highcontrast_css'], -1, ['rand' => $rand]);
      echo "</td>";
      echo "<td colspan='2'>";
      echo "</td></tr>";

      if ($oncentral) {
         echo "<tr class='tab_bg_1'><th colspan='4'>".__('Assistance')."</th></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td><label for='dropdown_followup_private$rand'>".__('Private followups by default')."</label></td><td>";
         Dropdown::showYesNo("followup_private", $data["followup_private"], -1, ['rand' => $rand]);
         echo "</td><td><label for='dropdown_show_jobs_at_login$rand'>". __('Show new tickets on the home page') . "</label></td><td>";
         if (Session::haveRightsOr("ticket",
                                   [Ticket::READMY, Ticket::READALL, Ticket::READASSIGN])) {
            Dropdown::showYesNo("show_jobs_at_login", $data["show_jobs_at_login"], -1, ['rand' => $rand]);
         } else {
            echo Dropdown::getYesNo(0);
         }
         echo " </td></tr>";

         echo "<tr class='tab_bg_2'><td><label for='dropdown_task_private$rand'>" . __('Private tasks by default') . "</label></td><td>";
         Dropdown::showYesNo("task_private", $data["task_private"], -1, ['rand' => $rand]);
         echo "</td><td><label for='dropdown_default_requesttypes_id$rand'>" . __('Request sources by default') . "</label></td><td>";
         RequestType::dropdown(['value'      => $data["default_requesttypes_id"],
                                'name'       => "default_requesttypes_id",
                                'condition'  => 'is_active = 1 AND is_ticketheader = 1',
                                'rand'       => $rand]);
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td><label for='dropdown_task_state$rand'>" . __('Tasks state by default') . "</label></td><td>";
         Planning::dropdownState("task_state", $data["task_state"], true, ['rand' => $rand]);
         echo "</td><td><label for='dropdown_refresh_ticket_list$rand'>" . __('Automatically refresh the list of tickets (minutes)') . "</label></td><td>";
         Dropdown::showNumber('refresh_ticket_list', ['value' => $data["refresh_ticket_list"],
                                                      'min'   => 1,
                                                      'max'   => 30,
                                                      'step'  => 1,
                                                      'toadd' => [0 => __('Never')],
                                                      'rand'  => $rand]);
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td><label for='dropdown_set_default_tech$rand'>".__('Pre-select me as a technician when creating a ticket').
              "</label></td><td>";
         if (!$userpref || Session::haveRight('ticket', Ticket::OWN)) {
            Dropdown::showYesNo("set_default_tech", $data["set_default_tech"], -1, ['rand' => $rand]);
         } else {
            echo Dropdown::getYesNo(0);
         }
         echo "</td><td><label for='dropdown_set_default_requester$rand'>" . __('Pre-select me as a requester when creating a ticket') . "</label></td><td>";
         if (!$userpref || Session::haveRight('ticket', CREATE)) {
            Dropdown::showYesNo("set_default_requester", $data["set_default_requester"], -1, ['rand' => $rand]);
         } else {
            echo Dropdown::getYesNo(0);
         }
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __('Priority colors') . "</td>";
         echo "<td colspan='3'>";

         echo "<table><tr>";
         echo "<td><label for='dropdown_priority_1$rand'>1</label>&nbsp;";
         Html::showColorField('priority_1', ['value' => $data["priority_1"], 'rand' => $rand]);
         echo "</td>";
         echo "<td><label for='dropdown_priority_2$rand'>2</label>&nbsp;";
         Html::showColorField('priority_2', ['value' => $data["priority_2"], 'rand' => $rand]);
         echo "</td>";
         echo "<td><label for='dropdown_priority_3$rand'>3</label>&nbsp;";
         Html::showColorField('priority_3', ['value' => $data["priority_3"], 'rand' => $rand]);
         echo "</td>";
         echo "<td><label for='dropdown_priority_4$rand'>4</label>&nbsp;";
         Html::showColorField('priority_4', ['value' => $data["priority_4"], 'rand' => $rand]);
         echo "</td>";
         echo "<td><label for='dropdown_priority_5$rand'>5</label>&nbsp;";
         Html::showColorField('priority_5', ['value' => $data["priority_5"], 'rand' => $rand]);
         echo "</td>";
         echo "<td><label for='dropdown_priority_6$rand'>6</label>&nbsp;";
         Html::showColorField('priority_6', ['value' => $data["priority_6"], 'rand' => $rand]);
         echo "</td>";
         echo "</tr></table>";

         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td><label for='dropdown_ticket_timeline$rand'>".__('Enable ticket timeline')."</label></td>";
         echo "<td>";
         Dropdown::showYesNo('ticket_timeline', $data['ticket_timeline'], -1, ['rand' => $rand]);
         echo "</td>";
         echo "<td><label for='dropdown_ticket_timeline_keep_replaced_tabs$rand'>" . __('Keep tabs replaced by the ticket timeline')."</label></td><td>";
         Dropdown::showYesNo('ticket_timeline_keep_replaced_tabs',
                             $data['ticket_timeline_keep_replaced_tabs'], -1, ['rand' => $rand]);
         echo "</td></tr>";
      }

      // Only for user
      if (array_key_exists('personal_token', $data)) {
         echo "<tr class='tab_bg_1'><th colspan='4'>". __('Remote access keys') ."</th></tr>";

         echo "<tr class='tab_bg_1'><td>";
         echo __("Personal token");
         echo "</td><td colspan='2'>";
         if (!empty($data["personal_token"])) {
            echo "<div class='copy_to_clipboard_wrapper'>";
            echo Html::input('_personal_token', [
                                 'value'    => $data["personal_token"],
                                 'style'    => 'width:90%'
                             ]);
            echo "</div>";
            echo "(".sprintf(__('generated on %s'),
                                Html::convDateTime($data["personal_token_date"])).")";
         }
         echo "</td><td>";
         Html::showCheckbox(['name'  => '_reset_personal_token',
                             'title' => __('Regenerate')]);
         echo "&nbsp;&nbsp;".__('Regenerate');
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>";
         echo __("API token");
         echo "</td><td colspan='2'>";
         if (!empty($data["api_token"])) {
            echo "<div class='copy_to_clipboard_wrapper'>";
            echo Html::input('_api_token', [
                                 'value'    => $data["api_token"],
                                 'style'    => 'width:90%'
                             ]);
            echo "</div>";
            echo "(".sprintf(__('generated on %s'),
                                Html::convDateTime($data["api_token_date"])).")";
         }
         echo "</td><td>";
         Html::showCheckbox(['name'  => '_reset_api_token',
                             'title' => __('Regenerate')]);
         echo "&nbsp;&nbsp;".__('Regenerate');
         echo "</td></tr>";
      }

      echo "<tr><th colspan='4'>".__('Due date progression')."</th></tr>";

      echo "<tr class='tab_bg_1'>".
           "<td>".__('OK state color')."</td>";
      echo "<td>";
      Html::showColorField('duedateok_color', ['value' => $data["duedateok_color"]]);
      echo "</td><td colspan='2'>&nbsp;</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Warning state color')."</td>";
      echo "<td>";
      Html::showColorField('duedatewarning_color', ['value' => $data["duedatewarning_color"]]);
      echo "</td>";
      echo "<td>".__('Warning state threshold')."</td>";
      echo "<td>";
      Dropdown::showNumber("duedatewarning_less", ['value' => $data['duedatewarning_less']]);
      $elements = ['%'     => '%',
                        'hours' => _n('Hour', 'Hours', Session::getPluralNumber()),
                        'days'  => _n('Day', 'Days', Session::getPluralNumber())];
      echo "&nbsp;";
      Dropdown::showFromArray("duedatewarning_unit", $elements,
                              ['value' => $data['duedatewarning_unit']]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>".
           "<td>".__('Critical state color')."</td>";
      echo "<td>";
      Html::showColorField('duedatecritical_color', ['value' => $data["duedatecritical_color"]]);
      echo "</td>";
      echo "<td>".__('Critical state threshold')."</td>";
      echo "<td>";
      Dropdown::showNumber("duedatecritical_less", ['value' => $data['duedatecritical_less']]);
      echo "&nbsp;";
      $elements = ['%'    => '%',
                       'hours' => _n('Hour', 'Hours', Session::getPluralNumber()),
                       'days'  => _n('Day', 'Days', Session::getPluralNumber())];
      Dropdown::showFromArray("duedatecritical_unit", $elements,
                              ['value' => $data['duedatecritical_unit']]);
      echo "</td></tr>";

      if ($oncentral && $CFG_GLPI["lock_use_lock_item"]) {
         echo "<tr class='tab_bg_1'><th colspan='4' class='center b'>".__('Item locks')."</th></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __('Auto-lock Mode') . "</td><td>";
         Dropdown::showYesNo("lock_autolock_mode", $data["lock_autolock_mode"]);
         echo "</td><td>". __('Direct Notification (requester for unlock will be the notification sender)').
              "</td><td>";
         Dropdown::showYesNo("lock_directunlock_notification", $data["lock_directunlock_notification"]);
         echo "</td></tr>";
      }

      if ($canedit || $userpref) {
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='4' class='center'>";
         echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
         echo "</td></tr>";
      }

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
   static function displayPasswordSecurityChecks($field = 'password') {
      global $CFG_GLPI;

      $needs = [];

      if ($CFG_GLPI["use_password_security"]) {
         printf(__('%1$s: %2$s'), __('Password minimum length'),
                   "<span id='password_min_length' class='red'>".$CFG_GLPI['password_min_length'].
                   "</span>");
      }

      echo "<script type='text/javascript' >\n";
      echo "function passwordCheck() {\n";
      if ($CFG_GLPI["use_password_security"]) {
         echo "var pwd = ".Html::jsGetElementbyID($field).";";
         echo "if (pwd.val().length < ".$CFG_GLPI['password_min_length'].") {
               ".Html::jsGetElementByID('password_min_length').".addClass('red');
               ".Html::jsGetElementByID('password_min_length').".removeClass('green');
         } else {
               ".Html::jsGetElementByID('password_min_length').".addClass('green');
               ".Html::jsGetElementByID('password_min_length').".removeClass('red');
         }";
         if ($CFG_GLPI["password_need_number"]) {
            $needs[] = "<span id='password_need_number' class='red'>".__('Digit')."</span>";
            echo "var numberRegex = new RegExp('[0-9]', 'g');
            if (false == numberRegex.test(pwd.val())) {
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
            if (false == letterRegex.test(pwd.val())) {
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
            if (false == capsRegex.test(pwd.val())) {
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
            if (false == capsRegex.test(pwd.val())) {
                  ".Html::jsGetElementByID('password_need_symbol').".addClass('red');
                  ".Html::jsGetElementByID('password_need_symbol').".removeClass('green');
            } else {
                  ".Html::jsGetElementByID('password_need_symbol').".addClass('green');
                  ".Html::jsGetElementByID('password_need_symbol').".removeClass('red');
            }";
         }
      }
      echo "}";
      echo '</script>';
      if (count($needs)) {
         echo "<br>";
         printf(__('%1$s: %2$s'), __('Password must contains'), implode(', ', $needs));
      }
   }


   /**
    * Validate password based on security rules
    *
    * @since version 0.84
    *
    * @param $password  string   password to validate
    * @param $display   boolean  display errors messages? (true by default)
    * @return bool when $display is false and the password does not matches the requirements
    *
    * @throws PasswordTooWeakException
    */
   static function validatePassword($password, $display = true) {
      global $CFG_GLPI;

      $ok = true;
      $exception = new PasswordTooWeakException();
      if ($CFG_GLPI["use_password_security"]) {
         if (Toolbox::strlen($password) < $CFG_GLPI['password_min_length']) {
            $ok = false;
            if ($display) {
               Session::addMessageAfterRedirect(__('Password too short!'), false, ERROR);
            } else {
               $exception->addMessage(__('Password too short!'));
            }
         }
         if ($CFG_GLPI["password_need_number"]
             && !preg_match("/[0-9]+/", $password)) {
            $ok = false;
            if ($display) {
               Session::addMessageAfterRedirect(__('Password must include at least a digit!'),
                                                false, ERROR);
            } else {
               $exception->addMessage(__('Password must include at least a digit!'));
            }
         }
         if ($CFG_GLPI["password_need_letter"]
             && !preg_match("/[a-z]+/", $password)) {
            $ok = false;
            if ($display) {
               Session::addMessageAfterRedirect(__('Password must include at least a lowercase letter!'),
                                                false, ERROR);
            } else {
               $exception->addMessage(__('Password must include at least a lowercase letter!'));
            }
         }
         if ($CFG_GLPI["password_need_caps"]
             && !preg_match("/[A-Z]+/", $password)) {
            $ok = false;
            if ($display) {
               Session::addMessageAfterRedirect(__('Password must include at least a uppercase letter!'),
                                                false, ERROR);
            } else {
               $exception->addMessage(__('Password must include at least a uppercase letter!'));
            }
         }
         if ($CFG_GLPI["password_need_symbol"]
             && !preg_match("/\W+/", $password)) {
            $ok = false;
            if ($display) {
               Session::addMessageAfterRedirect(__('Password must include at least a symbol!'),
                                                false, ERROR);
            } else {
               $exception->addMessage(__('Password must include at least a symbol!'));
            }
         }

      }
      if (!$ok && !$display) {
         throw $exception;
      }
      return $ok;
   }


   /**
    * Display a report about system performance
    * - opcode cache (opcache)
    * - user data cache (apcu / apcu-bc)
    *
    * @since 9.1
   **/
   function showPerformanceInformations() {
      global $GLPI_CACHE;

      if (!Config::canUpdate()) {
         return false;
      }

      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4'>" . __('PHP opcode cache') . "</th></tr>";
      $ext = 'Zend OPcache';
      if (extension_loaded($ext) && ($info = opcache_get_status(false))) {
         $msg = sprintf(__s('%s extension is installed'), $ext);
         echo "<tr><td>" . sprintf(__('The "%s" extension is installed'), $ext) . "</td>
               <td>" . phpversion($ext) . "</td>
               <td></td>
               <td class='icons_block'><i class='fa fa-check-circle ok' title='$msg'><span class='sr-only'>$msg</span></td></tr>";

         // Memory
         $used = $info['memory_usage']['used_memory'];
         $free = $info['memory_usage']['free_memory'];
         $rate = round(100.0 * $used / ($used + $free));
         $max  = Toolbox::getSize($used + $free);
         $used = Toolbox::getSize($used);
         echo "<tr><td>" . __('Memory') . "</td>
               <td>" . sprintf(__('%1$s / %2$s'), $used, $max) . "</td><td>";
         Html::displayProgressBar('100', $rate, ['simple'       => true,
                                                      'forcepadding' => false]);

         $class   = 'info-circle missing';
         $msg     = sprintf(__s('%1$ss memory usage is too low or too high'), $ext);
         if ($rate > 5 && $rate < 75) {
            $class   = 'check-circle ok';
            $msg     = sprintf(__s('%1$s memory usage is correct'), $ext);
         }
         echo "</td><td class='icons_block'><i title='$msg' class='fa fa-$class'></td></tr>";

         // Hits
         $hits = $info['opcache_statistics']['hits'];
         $miss = $info['opcache_statistics']['misses'];
         $max  = $hits+$miss;
         $rate = round($info['opcache_statistics']['opcache_hit_rate']);
         echo "<tr><td>" . __('Hits rate') . "</td>
               <td>" . sprintf(__('%1$s / %2$s'), $hits, $max) . "</td><td>";
         Html::displayProgressBar('100', $rate, ['simple'       => true,
                                                      'forcepadding' => false]);

         $class   = 'info-circle missing';
         $msg     = sprintf(__s('%1$ss hits rate is low'), $ext);
         if ($rate > 90) {
            $class   = 'check-circle ok';
            $msg     = sprintf(__s('%1$s hits rate is correct'), $ext);
         }
         echo "</td><td class='icons_block'><i title='$msg' class='fa fa-$class'></td></tr>";

         // Restart (1 seems ok, can happen)
         $max = $info['opcache_statistics']['oom_restarts'];
         echo "<tr><td>" . __('Out of memory restart') . "</td>
               <td>$max</td><td>";

         $class   = 'info-circle missing';
         $msg     = sprintf(__s('%1$ss restart rate is too high'), $ext);
         if ($max < 2) {
            $class   = 'check-circle ok';
            $msg     = sprintf(__s('%1$s restart rate is correct'), $ext);
         }
         echo "</td><td class='icons_block'><i title='$msg' class='fa fa-$class'></td></tr>";

         if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            echo "<tr><td></td><td colspan='3'>";
            echo "<a class='vsubmit' href='config.form.php?reset_opcache=1'>";
            echo __('Reset');
            echo "</a></td></tr>\n";
         }
      } else {
         $msg = sprintf(__s('%s extension is not present'), $ext);
         echo "<tr><td colspan='3'>" . sprintf(__('Installing and enabling the "%s" extension may improve GLPI performance'), $ext) . "</td>
               <td class='icons_block'><i class='fa fa-info-circle missing' title='$msg'></i><span class='sr-only'>$msg</span></td></tr>";
      }

      echo "<tr><th colspan='4'>" . __('User data cache') . "</th></tr>";
      if (Toolbox::useCache()) {
         $ext = get_class($GLPI_CACHE);
         $ext = substr($ext, strrpos($ext, '\\')+1);
         $msg = sprintf(__s('The "%s" extension is installed'), $ext);
         echo "<tr><td>" . sprintf(__('The "%s" extension is installed'), $ext) . "</td>
               <td>" . phpversion($ext) . "</td>
               <td></td>
               <td class='icons_block'><i class='fa fa-check-circle ok' title='$msg'></i><span class='sr-only'>$msg</span></td></tr>";

         if ($GLPI_CACHE instanceof AvailableSpaceCapableInterface && $GLPI_CACHE instanceof TotalSpaceCapableInterface) {
            $free = $GLPI_CACHE->getAvailableSpace();
            $max  = $GLPI_CACHE->getTotalSpace();
            $used = $max - $free;
            $rate = round(100.0 * $used / $max);
            $max  = Toolbox::getSize($max);
            $used = Toolbox::getSize($used);

            echo "<tr><td>" . __('Memory') . "</td>
            <td>" . sprintf(__('%1$s / %2$s'), $used, $max) . "</td><td>";
            Html::displayProgressBar('100', $rate, ['simple'       => true,
                                                    'forcepadding' => false]);
            $class   = 'info-circle missing';
            $msg     = sprintf(__s('%1$ss memory usage is too low or too high'), $ext);
            if ($rate > 5 && $rate < 50) {
               $class   = 'check-circle ok';
               $msg     = sprintf(__s('%1$s memory usage is correct'), $ext);
            }
            echo "</td><td class='icons_block'><i title='$msg' class='fa fa-$class'></td></tr>";
         } else {
            echo "<tr><td>" . __('Memory') . "</td>
               <td>" . NOT_AVAILABLE. "</td><td>" . NOT_AVAILABLE . "</td><td></td></tr>";
         }

         if ($GLPI_CACHE instanceof FlushableInterface) {
            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
               echo "<tr><td></td><td colspan='3'>";
               echo "<a class='vsubmit' href='config.form.php?reset_cache=1'>";
               echo __('Reset');
               echo "</a></td></tr>\n";
            }
         }
      } else {
         $ext = 'APCu'; // Default cache, can be improved later
         $msg = sprintf(__s('%s extension is not present'), $ext);
         echo "<tr><td colspan='3'>" . sprintf(__('Installing the "%s" extension may improve GLPI performance'), $ext) . "</td>
               <td><i class='fa fa-info-circle missing' title='$msg'></i><span class='sr-only'>$msg</span></td></tr>";
      }

      echo "</table></div>\n";
   }

   /**
    * Display a HTML report about systeme information / configuration
   **/
   function showSystemInformations() {
      global $DB, $CFG_GLPI;

      if (!Config::canUpdate()) {
         return false;
      }

      $rand = mt_rand();

      echo "<div class='center' id='tabsbody'>";
      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL(__CLASS__)."\" method='post'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . __('General setup') . "</th></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_event_loglevel$rand'>" . __('Log Level') . "</label></td><td>";
      $values[1] = __('1- Critical (login error only)');
      $values[2] = __('2- Severe (not used)');
      $values[3] = __('3- Important (successful logins)');
      $values[4] = __('4- Notices (add, delete, tracking)');
      $values[5] = __('5- Complete (all)');

      Dropdown::showFromArray('event_loglevel', $values,
                              ['value' => $CFG_GLPI["event_loglevel"], 'rand' => $rand]);
      echo "</td><td><label for='dropdown_cron_limit$rand'>".__('Maximal number of automatic actions (run by CLI)')."</label></td><td>";
      Dropdown::showNumber('cron_limit', ['value' => $CFG_GLPI["cron_limit"],
                                          'min'   => 1,
                                          'max'   => 30,
                                          'rand'  => $rand]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_use_log_in_files$rand'>" . __('Logs in files (SQL, email, automatic action...)') . "</label></td><td>";
      Dropdown::showYesNo("use_log_in_files", $CFG_GLPI["use_log_in_files"], -1, ['rand' => $rand]);
      echo "</td><td><label for='dropdown__dbslave_status$rand'>" . _n('SQL replica', 'SQL replicas', 1) . "</label></td><td>";
      $active = DBConnection::isDBSlaveActive();
      Dropdown::showYesNo("_dbslave_status", $active, -1, ['rand' => $rand]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4' class='center b'>".__('Password security policy');
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_use_password_security$rand'>" . __('Password security policy validation') . "</label></td>";
      echo "<td>";
      Dropdown::showYesNo("use_password_security", $CFG_GLPI["use_password_security"], -1, ['rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_password_min_length$rand'>" . __('Password minimum length') . "</label></td>";
      echo "<td>";
      Dropdown::showNumber('password_min_length', ['value' => $CFG_GLPI["password_min_length"],
                                                   'min'   => 4,
                                                   'max'   => 30,
                                                   'rand'  => $rand]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_password_need_number$rand'>" . __('Password need digit') . "</label></td>";
      echo "<td>";
      Dropdown::showYesNo("password_need_number", $CFG_GLPI["password_need_number"], -1, ['rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_password_need_letter$rand'>" . __('Password need lowercase character') . "</label></td>";
      echo "<td>";
      Dropdown::showYesNo("password_need_letter", $CFG_GLPI["password_need_letter"], -1, ['rand' => $rand]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_password_need_caps$rand'>" . __('Password need uppercase character') . "</label></td>";
      echo "<td>";
      Dropdown::showYesNo("password_need_caps", $CFG_GLPI["password_need_caps"], -1, ['rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_password_need_symbol$rand'>" . __('Password need symbol') . "</label></td>";
      echo "<td>";
      Dropdown::showYesNo("password_need_symbol", $CFG_GLPI["password_need_symbol"], -1, ['rand' => $rand]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4' class='center b'>".__('Maintenance mode');
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='dropdown_maintenance_mode$rand'>" . __('Maintenance mode') . "</label></td>";
      echo "<td>";
      Dropdown::showYesNo("maintenance_mode", $CFG_GLPI["maintenance_mode"], -1, ['rand' => $rand]);
      echo "</td>";
      //TRANS: Proxy port
      echo "<td><label for='maintenance_text'>" . __('Maintenance text') . "</label></td>";
      echo "<td>";
      echo "<textarea cols='70' rows='4' name='maintenance_text' id='maintenance_text'>".$CFG_GLPI["maintenance_text"];
      echo "</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4' class='center b'>".__('Proxy configuration for upgrade check');
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='proxy_name'>" . __('Server') . "</label></td>";
      echo "<td><input type='text' name='proxy_name' id='proxy_name' value='".$CFG_GLPI["proxy_name"]."'></td>";
      //TRANS: Proxy port
      echo "<td><label for='proxy_port'>" . __('Port') . "</label></td>";
      echo "<td><input type='text' name='proxy_port' id='proxy_port' value='".$CFG_GLPI["proxy_port"]."'></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td><label for='proxy_user'>" . __('Login') . "</label></td>";
      echo "<td><input type='text' name='proxy_user' id='proxy_user' value='".$CFG_GLPI["proxy_user"]."'></td>";
      echo "<td><label for='proxy_passwd'>" . __('Password') . "</label></td>";
      echo "<td><input type='password' name='proxy_passwd' id='proxy_passwd' value='' autocomplete='off'>";
      echo "<br><input type='checkbox' name='_blank_proxy_passwd' id='_blank_proxy_passwd'><label for='_blank_proxy_passwd'>".__('Clear')."</label>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
      echo "</td></tr>";

      echo "</table>";
      Html::closeForm();

      $width = 128;

      echo "<p>" . Telemetry::getViewLink() . "</p>";

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>". __('Information about system installation and configuration')."</th></tr>";

       $oldlang = $_SESSION['glpilanguage'];
       // Keep this, for some function call which still use translation (ex showAllReplicateDelay)
       Session::loadLanguage('en_GB');

      // No need to translate, this part always display in english (for copy/paste to forum)

      // Try to compute a better version for .git
      $ver = $CFG_GLPI['version'];
      if (is_dir(GLPI_ROOT."/.git")) {
         $dir = getcwd();
         chdir(GLPI_ROOT);
         $returnCode = 1;
         $gitrev = @exec('git show --format="%h" --no-patch 2>&1', $output, $returnCode);
         $gitbranch = '';
         if (!$returnCode) {
            $gitbranch = @exec('git symbolic-ref --quiet --short HEAD || git rev-parse --short HEAD 2>&1', $output, $returnCode);
         }
         chdir($dir);
         if (!$returnCode) {
            $ver .= '-git-' .$gitbranch . '-' . $gitrev;
         }
      }
      echo "<tr class='tab_bg_1'><td><pre>[code]\n&nbsp;\n";
      echo "GLPI $ver (" . $CFG_GLPI['root_doc']." => " . GLPI_ROOT . ")\n";
      echo "Installation mode: " . GLPI_INSTALL_MODE . "\n";
      echo "\n</pre></td></tr>";

      echo "<tr><th>Server</th></tr>\n";
      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";
      echo wordwrap("Operating system: ".php_uname()."\n", $width, "\n\t");
      $exts = get_loaded_extensions();
      sort($exts);
      echo wordwrap("PHP ".phpversion().' '.php_sapi_name()." (".implode(', ', $exts).")\n",
                    $width, "\n\t");
      $msg = "Setup: ";

      foreach (['max_execution_time', 'memory_limit', 'post_max_size', 'safe_mode',
                     'session.save_handler', 'upload_max_filesize'] as $key) {
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

      self::displayCheckExtensions(true);

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
    * Retrieve full directory of a lib
    * @param  $libstring  object, class or function
    * @return string       the path or false
    *
    * @since version 9.1
    */
   static function getLibraryDir($libstring) {
      if (is_object($libstring)) {
         return realpath(dirname((new ReflectionObject($libstring))->getFileName()));

      } else if (class_exists($libstring)) {
         return realpath(dirname((new ReflectionClass($libstring))->getFileName()));

      } else if (function_exists($libstring)) {
         // Internal function have no file name
         $path = (new ReflectionFunction($libstring))->getFileName();
         return ($path ? realpath(dirname($path)) : false);

      }
      return false;
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
      $pm = new PHPMailer();
      $sp = new SimplePie();

      $deps = [[ 'name'    => 'htmLawed',
                 'version' => hl_version() ,
                 'check'   => 'hl_version' ],
               [ 'name'    => 'phpCas',
                 'version' => phpCAS::getVersion() ,
                 'check'   => 'phpCAS' ],
               [ 'name'    => 'PHPMailer',
                 'version' => $pm->Version ,
                 'check'   => 'PHPMailer' ],
               [ 'name'    => 'SimplePie',
                 'version' => SIMPLEPIE_VERSION,
                 'check'   => $sp ],
               [ 'name'    => 'TCPDF',
                 'version' => TCPDF_STATIC::getTCPDFVersion(),
                 'check'   => 'TCPDF' ],
               [ 'name'    => 'michelf/php-markdown',
                 'check'   => 'Michelf\\Markdown' ],
               [ 'name'    => 'true/punycode',
                 'check'   => 'TrueBV\\Punycode' ],
               [ 'name'    => 'iacaml/autolink',
                 'check'   => 'autolink' ],
               [ 'name'    => 'sabre/vobject',
                 'check'   => 'Sabre\\VObject\\Component' ],
      ];

      foreach ($deps as $dep) {
         $path = self::getLibraryDir($dep['check']);
         if ($path) {
            echo "{$dep['name']} ";
            if (isset($dep['version'])) {
               echo "version {$dep['version']} ";
            }
            echo "in ($path)\n";
         }
      }

      echo "\n</pre></td></tr>";
   }


   /**
    * Dropdown for global management config
    *
    * @param $name   select name
    * @param $value  default value
    * @param $rand   rand
   **/
   static function dropdownGlobalManagement($name, $value, $rand = null) {

      $choices[0] = __('Yes - Restrict to unit management for manual add');
      $choices[1] = __('Yes - Restrict to global management for manual add');
      $choices[2] = __('No');
      Dropdown::showFromArray($name, $choices, ['value'=>$value, 'rand' => $rand]);
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
         if ((strcasecmp($lang, $ID) == 0)
             || (strcasecmp($lang, $language[2]) == 0)
             || (strcasecmp($lang, $language[3]) == 0)) {
            return $ID;
         }
      }

      // native lang
      foreach ($CFG_GLPI["languages"] as $ID => $language) {
         if (strcasecmp($lang, $language[0]) == 0) {
            return $ID;
         }
      }

      // english lang name
      foreach ($CFG_GLPI["languages"] as $ID => $language) {
         if (strcasecmp($lang, $language[4]) == 0) {
            return $ID;
         }
      }

      return "";
   }


   static function detectRootDoc() {
      global $CFG_GLPI;

      if (!isset($CFG_GLPI["root_doc"])) {
         if (!isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
         }

         $currentdir = getcwd();
         chdir(GLPI_ROOT);
         $glpidir    = str_replace(str_replace('\\', '/', getcwd()), "",
                                   str_replace('\\', '/', $currentdir));
         chdir($currentdir);
         $globaldir  = Html::cleanParametersURL($_SERVER['REQUEST_URI']);
         $globaldir  = preg_replace("/\/[0-9a-zA-Z\.\-\_]+\.php/", "", $globaldir);

         // api exception
         if (strpos($globaldir, 'api/') !== false) {
            $globaldir = preg_replace("/(.*\/)api\/.*/", "$1", $globaldir);
         }

         $CFG_GLPI["root_doc"] = str_replace($glpidir, "", $globaldir);
         $CFG_GLPI["root_doc"] = preg_replace("/\/$/", "", $CFG_GLPI["root_doc"]);
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
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

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
               $tabs[7] = __('Performance');
               $tabs[8] = __('API');
            }

            if (DBConnection::isDBSlaveActive()
                && Config::canUpdate()) {
               $tabs[6]  = _n('SQL replica', 'SQL replicas', Session::getPluralNumber());  // Slave
            }
            return $tabs;
      }
      return '';
   }


   /**
    * @param $item         CommonGLPI object
    * @param $tabnum (default 1)
    * @param $withtemplate (default 0)
    *
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
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

            case 7 :
               $item->showPerformanceInformations();
               break;

            case 8 :
               $item->showFormAPI();
               break;

         }
      }
      return true;
   }


   /**
    * Display extensions checks report
    *
    * @since 9.2
    *
    * @param boolean    $fordebug display for debug (no html required) (false by default)
    *
    * @return integer 2: missing extension,  1: missing optionnal extension, 0: OK,
    **/
   static function displayCheckExtensions($fordebug = false) {
      global $CFG_GLPI;

      $report = self::checkExtensions();

      foreach ($report['good'] as $ext => $msg) {
         if (!$fordebug) {
            echo "<tr class=\"tab_bg_1\"><td class=\"left b\">" . sprintf(__('%s extension test'), $ext) . "</td>";
            echo "<td><img src=\"{$CFG_GLPI['root_doc']}/pics/ok_min.png\"
                           alt=\"$msg\"
                           title=\"$msg\"></td>";
            echo "</tr>";
         } else {
            echo  "<img src=\"{$CFG_GLPI['root_doc']}/pics/ok_min.png\"
                        alt=\"\">$msg\n";
         }
      }

      foreach ($report['may'] as $ext => $msg) {
         if (!$fordebug) {
            echo "<tr class=\"tab_bg_1\"><td class=\"left b\">" . sprintf(__('%s extension test'), $ext) . "</td>";
            echo "<td><img src=\"{$CFG_GLPI['root_doc']}/pics/warning_min.png\"> " . $msg . "</td>";
            echo "</tr>";
         } else {
            echo "<img src=\"{$CFG_GLPI['root_doc']}/pics/warning_min.png\">" . $msg . "\n";
         }

      }

      foreach ($report['missing'] as $ext => $msg) {
         if (!$fordebug) {
            echo "<tr class=\"tab_bg_1\"><td class=\"left b\">" . sprintf(__('%s extension test'), $ext) . "</td>";
            echo "<td class=\"red\"><img src=\"{$CFG_GLPI['root_doc']}/pics/ko_min.png\"> " . $msg . "</td>";
            echo "</tr>";
         } else {
            echo "<img src=\"{$CFG_GLPI['root_doc']}/pics/ko_min.png\">" . $msg . "\n";
         }
      }

      return $report['error'];
   }


   /**
    * Check for needed extensions
    *
    * @since 9.2 Method signature and return has changed
    *
    * @param null|array $list     Extensions list (from plugins)
    *
    * @return array [
    *                'error'     => integer 2: missing extension,  1: missing optionnal extension, 0: OK,
    *                'good'      => [ext => message],
    *                'missing'   => [ext => message],
    *                'may'       => [ext => message]
    *               ]
   **/
   static function checkExtensions($list = null) {
      if ($list === null) {
         $extensions_to_check = [
            'mysqli'   => [
               'required'  => true
            ],
            'ctype'    => [
               'required'  => true,
               'function'  => 'ctype_digit',
            ],
            'fileinfo' => [
               'required'  => true,
               'class'     => 'finfo'
            ],
            'json'     => [
               'required'  => true,
               'function'  => 'json_encode'
            ],
            'mbstring' => [
               'required'  => true,
            ],
            'zlib'     => [
               'required'  => true,
            ],
            'curl'      => [
               'required'  => true,
            ],
            'gd'       => [
               'required'  => true,
            ],
            'simplexml' => [
               'required'  => true,
            ],
            'xml'        => [
               'required'  => true,
               'function'  => 'utf8_decode'
            ],
            //to sync/connect from LDAP
            'ldap'       => [
               'required'  => false,
            ],
            //for mail collector
            'imap'       => [
               'required'  => false,
            ],
            //to enhance perfs
            'Zend OPcache' => [
               'required'  => false
            ],
            //to enhance perfs
            'APCu'      => [
               'required'  => false,
               'function'  => 'apcu_fetch'
            ],
            //for XMLRPC API
            'xmlrpc'     => [
               'required'  => false
            ]
         ];
      } else {
         $extensions_to_check = $list;
      }

      $report = [
         'error'     => 0,
         'good'      => [],
         'missing'   => [],
         'may'       => []
      ];

      //check for PHP extensions
      foreach ($extensions_to_check as $ext => $params) {
         $success = true;

         if (isset($params['call'])) {
            $success = call_user_func($params['call']);
         } else if (isset($params['function'])) {
            if (!function_exists($params['function'])) {
                $success = false;
            }
         } else if (isset($params['class'])) {
            if (!class_exists($params['class'])) {
               $success = false;
            }
         } else {
            if (!extension_loaded($ext)) {
               $success = false;
            }
         }

         if ($success) {
            $msg = sprintf(__('%s extension is installed'), $ext);
            $report['good'][$ext] = $msg;
         } else {
            if (isset($params['required']) && $params['required'] === true) {
               if ($report['error'] < 2) {
                  $report['error'] = 2;
               }
               $msg = sprintf(__('%s extension is missing'), $ext);
               $report['missing'][$ext] = $msg;
            } else {
               if ($report['error'] < 1) {
                  $report['error'] = 1;
               }
               $msg = sprintf(__('%s extension is not present'), $ext);
               $report['may'][$ext] = $msg;
            }
         }
      }

      return $report;
   }


   /**
    * Get data directories for checks
    *
    * @return array
    */
   private static function getDataDirectories() {
      $dir_to_check = [
         GLPI_CONFIG_DIR      => __('Checking write permissions for setting files'),
         GLPI_DOC_DIR         => __('Checking write permissions for document files'),
         GLPI_DUMP_DIR        => __('Checking write permissions for dump files'),
         GLPI_SESSION_DIR     => __('Checking write permissions for session files'),
         GLPI_CRON_DIR        => __('Checking write permissions for automatic actions files'),
         GLPI_GRAPH_DIR       => __('Checking write permissions for graphic files'),
         GLPI_LOCK_DIR        => __('Checking write permissions for lock files'),
         GLPI_PLUGIN_DOC_DIR  => __('Checking write permissions for plugins document files'),
         GLPI_TMP_DIR         => __('Checking write permissions for temporary files'),
         GLPI_CACHE_DIR       => __('Checking write permissions for cache files'),
         GLPI_RSS_DIR         => __('Checking write permissions for rss files'),
         GLPI_UPLOAD_DIR      => __('Checking write permissions for upload files'),
         GLPI_PICTURE_DIR     => __('Checking write permissions for pictures files')
      ];

      return $dir_to_check;
   }


   /**
    * Check Write Access to needed directories
    *
    * @param boolean $fordebug display for debug (no html, no gettext required) (false by default)
    *
    * @return 2 : creation error 1 : delete error 0: OK
   **/
   static function checkWriteAccessToDirs($fordebug = false) {
      global $CFG_GLPI;

      $dir_to_check = self::getDataDirectories();
      //log dir is tested differently below
      unset($dir_to_check[GLPI_LOG_DIR]);
      $error = 0;
      foreach ($dir_to_check as $dir => $message) {
         if (!$fordebug) {
            echo "<tr class='tab_bg_1'><td class='left b'>".$message."</td>";
         }
         $tmperror = Toolbox::testWriteAccessToDirectory($dir);

         $errors = [4 => __('The directory could not be created.'),
                         3 => __('The directory was created but could not be removed.'),
                         2 => __('The file could not be created.'),
                         1 => __("The file was created but can't be deleted.")];

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

      $check_access = false;
      $directories = array_keys(self::getDataDirectories());

      foreach ($directories as $dir) {
         if (Toolbox::startsWith($dir, GLPI_ROOT)) {
            //only check access if on of the data directories is under GLPI document root.
            $check_access = true;
            break;
         }
      }

      if ($check_access) {
         $oldhand = set_error_handler(function($errno, $errmsg, $filename, $linenum, $vars){return true;});
         $oldlevel = error_reporting(0);

         //create a context to set timeout
         $context = stream_context_create([
            'http' => [
               'timeout' => 2.0
            ]
         ]);

         /* TODO: could be improved, only default vhost checked */
         $protocol = 'http';
         if (isset($_SERVER['HTTPS'])) {
            $protocol = 'https';
         }
         $uri = $protocol . '://' . $_SERVER['SERVER_NAME'] . $CFG_GLPI['root_doc'];

         if ($fic = fopen($uri.'/index.php?skipCheckWriteAccessToDirs=1', 'r', false, $context)) {
            fclose($fic);
            if (!$fordebug) {
               echo "<tr class='tab_bg_1'><td class='b left'>".
                  __('Web access to files directory is protected')."</td>";
            }
            if ($fic = fopen($uri.'/files/_log/php-errors.log', 'r', false, $context)) {
               fclose($fic);
               if ($fordebug) {
                  echo "<img src='".$CFG_GLPI['root_doc']."/pics/warning_min.png'>".
                        __('Web access to the files directory should not be allowed')."\n".
                        __('Check the .htaccess file and the web server configuration.')."\n";
               } else {
                  echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/warning_min.png'>".
                     "<p class='red'>".__('Web access to the files directory should not be allowed')."<br/>".
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
         } else {
            $msg = __('Web access to the files directory should not be allowed but this cannot be checked automatically on this instance.')."\n".
               "Make sure acces to <a href='{$CFG_GLPI['root_doc']}/files/_log/php-errors.log'>".__('error log file')."</a> is forbidden; otherwise review .htaccess file and web server configuration.";

            if ($fordebug) {
               echo "<img src='".$CFG_GLPI['root_doc']."/pics/warning_min.png'>".$msg;
            } else {
               echo "<td><img src='".$CFG_GLPI['root_doc']."/pics/warning_min.png'>".
                     "<p class='red'>".nl2br($msg)."</p></td></tr>";
            }
         }

         error_reporting($oldlevel);
         set_error_handler($oldhand);
      }

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

      if (!$DB->tableExists('glpi_configs')) {
         $query = "SELECT `version`
                   FROM `glpi_config`
                   WHERE `id` = '1'";
      } else if ($DB->fieldExists('glpi_configs', 'version')) {
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
   static function getConfigurationValues($context, array $names = []) {
      global $DB;

      $query = [
         'FROM'   => self::getTable(),
         'WHERE'  => [
            'context'   => $context
         ]
      ];

      if (count($names) > 0) {
         $query['WHERE']['name'] = $names;
      }

      $iterator = $DB->request($query);
      $result = [];
      while ($line = $iterator->next()) {
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
    * @return void
   **/
   static function setConfigurationValues($context, array $values = []) {

      $config = new self();
      foreach ($values as $name => $value) {
         if ($config->getFromDBByQuery("WHERE `context` = '$context'
                                              AND `name` = '$name'")) {

            $input = ['id'      => $config->getID(),
                           'context' => $context,
                           'value'   => $value];

            $config->update($input);

         } else {
            $input = ['context' => $context,
                           'name'    => $name,
                           'value'   => $value];

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
    * @return void
   **/
   static function deleteConfigurationValues($context, array $values = []) {

      $config = new self();
      foreach ($values as $value) {
         if ($config->getFromDBByCrit([
            'context'   => $context,
            'name'      => $value
         ])) {
            $config->delete(['id' => $config->getID()]);
         }
      }
   }


   /**
    * @since version 0.85
    *
    * @see commonDBTM::getRights()
   **/
   function getRights($interface = 'central') {

      $values = parent::getRights();
      unset($values[CREATE], $values[DELETE],
            $values[PURGE]);

      return $values;
   }

   /**
    * Get message that informs the user he's using a development version
    *
    * @param boolean $bg Display a background
    *
    * @return void
    */
   public static function agreeDevMessage($bg = false) {
      $msg = '<p class="'.($bg ? 'mig' : '') .'red"><strong>' . __('You are using a development version, be careful!') . '</strong><br/>';
      $msg .= "<input type='checkbox' required='required' id='agree_dev' name='agree_dev'/><label for='agree_dev'>" . __('I know I am using a unstable version.') . "</label></p>";
      $msg .= "<script type=text/javascript>
            $(function() {
               $('[name=from_update]').on('click', function(event){
                  if(!$('#agree_dev').is(':checked')) {
                     event.preventDefault();
                     alert('" . __('Please check the unstable version checkbox.') . "');
                  }
               });
            });
            </script>";
      return $msg;
   }

   /**
    * Get a cache adapter from configuration
    *
    * @param string $optname name of the configuration field
    * @param string $context name of the configuration context (default 'core')
    *
    * @return Zend\Cache\Storage\StorageInterface object or false
    */
   public static function getCache($optname, $context = 'core') {
      global $DB;

      if (defined('TU_USER') && !defined('CACHED_TESTS')
         || !$DB || !$DB->tableExists(self::getTable())
         || !$DB->fieldExists(self::getTable(), 'context')) {
         return false;
      }

      /* Tested configuration values
       *
       * - (empty = no cache)
       * - {"adapter":"apcu"}
       * - {"adapter":"redis","options":{"server":{"host":"127.0.0.1"}},"plugins":["serializer"]}
       * - {"adapter":"filesystem"}
       * - {"adapter":"filesystem","options":{"cache_dir":"_cache_trans"},"plugins":["serializer"]}
       * - {"adapter":"dba"}
       * - {"adapter":"dba","options":{"pathname":"trans.db","handler":"flatfile"},"plugins":["serializer"]}
       * - {"adapter":"memcache","options":{"servers":["127.0.0.1"]}}
       * - {"adapter":"memcached","options":{"servers":["127.0.0.1"]}}
       * - {"adapter":"wincache"}
       *
       */
      // Read configuration
      $conf = self::getConfigurationValues($context, [$optname]);

      // Adapter default options
      $opt = [];
      if (isset($conf[$optname])) {
         if (empty($conf[$optname])) { // to disable cache
            return false;
         }
         $opt = json_decode($conf[$optname], true);
         //Toolbox::logDebug("CACHE CONFIG  $optname", $opt);
      }
      if (!isset($opt['options']['namespace'])) {
         $opt['options']['namespace'] = "glpi_${optname}_" . GLPI_VERSION;
      }
      if (!isset($opt['adapter'])) {
         if (function_exists('apcu_fetch')) {
            $opt['adapter'] = (version_compare(PHP_VERSION, '7.0.0') >= 0) ? 'apcu' : 'apc';
         } else if (function_exists('wincache_ucache_add')) {
            $opt['adapter'] = 'wincache';
         } else {
            return false;
         }
      }
      // Adapter specific options
      $ser = false;
      switch ($opt['adapter']) {
         case 'filesystem':
            if (!isset($opt['options']['cache_dir'])) {
               $opt['options']['cache_dir'] = $optname;
            }
            // Make configured directory relative to GLPI cache directory
            $opt['options']['cache_dir'] = GLPI_CACHE_DIR . '/' . $opt['options']['cache_dir'];
            if (!is_dir($opt['options']['cache_dir'])) {
               mkdir($opt['options']['cache_dir']);
            }
            $ser = true;
            break;

         case 'dba':
            if (!isset($opt['options']['pathname'])) {
               $opt['options']['pathname'] = "$optname.data";
            }
            // Make configured path relative to GLPI cache directory
            $opt['options']['pathname'] = GLPI_CACHE_DIR . '/' . $opt['options']['pathname'];
            $ser = true;
            break;

         case 'redis':
            $ser = true;
            break;
      }
      // Some know plugins require data serialization
      if ($ser && !isset($opt['plugins'])) {
         $opt['plugins'] = ['serializer'];
      }

      // Create adapter
      $cache = false;
      try {
         $cache = Zend\Cache\StorageFactory::factory($opt);
      } catch (Exception $e) {
         if (Session::DEBUG_MODE == $_SESSION['glpi_use_mode']) {
            Toolbox::logDebug($e->getMessage());
         }
      }
      //Toolbox::logDebug("CACHE $optname", get_class($cache));
      return $cache;
   }
}
