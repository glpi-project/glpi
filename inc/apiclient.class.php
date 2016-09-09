<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2016 Teclib'.

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
* @since version 9.1
*/

class APIClient extends CommonDBTM {

   const DOLOG_DISABLED   = 0;
   const DOLOG_LOGS       = 1;
   const DOLOG_HISTORICAL = 2;

   static $rightname = 'config';

   // From CommonDBTM
   public $dohistory                   = true;



   static function canCreate() {
      return Session::haveRight(static::$rightname, UPDATE);
   }


   static function canPurge() {
      return Session::haveRight(static::$rightname, UPDATE);
   }


   static function getTypeName($nb = 0) {
      return _n("API client", "API clients", $nb);
   }


   /**
    * @see CommonGLPI::defineTabs()
   **/
   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong)
           ->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function getSearchOptions() {

      $tab = array();

      $tab['common']             = self::GetTypeName();

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false;
      $tab[2]['datatype']        = 'number';

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'is_active';
      $tab[3]['name']            = __('Active');
      $tab[3]['datatype']        = 'bool';

      $tab[4]['table']           = $this->getTable();
      $tab[4]['field']           = 'dolog_method';
      $tab[4]['name']            = __('Log connections');
      $tab[4]['datatype']        = 'specific';

      $tab['filter']             = __("Filter access");

      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'ipv4_range_start';
      $tab[5]['name']            = __('IPv4 address range')." - ".__("Start");
      $tab[5]['datatype']        = 'specific';

      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'ipv4_range_end';
      $tab[6]['name']            = __('IPv4 address range')." - ".__("End");
      $tab[6]['datatype']        = 'specific';

      $tab[7]['table']           = $this->getTable();
      $tab[7]['field']           = 'ipv6';
      $tab[7]['name']            = __('IPv6 address');
      $tab[7]['datatype']        = 'text';

      $tab[8]['table']           = $this->getTable();
      $tab[8]['field']           = 'app_token';
      $tab[8]['name']            = __('Application token');
      $tab[8]['massiveaction']   = false;
      $tab[8]['datatype']        = 'text';

      return $tab;
   }


   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      switch ($field) {
         case 'dolog_method' :
            $methods = self::getLogMethod();
            return $methods[$values[$field]];

         case 'ipv4_range_start':
         case 'ipv4_range_end':
            return long2ip($values[$field]);
      }

      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   function showForm ($ID, $options=array()) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='3'>".__('Comments')."</td>";
      echo "<td rowspan='3'>";
      echo "<textarea name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__('Active')."</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active",$this->fields["is_active"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__('Log connections')."</td>";
      echo "<td>";
      Dropdown::showFromArray("dolog_method",
                              self::getLogMethod(),
                              array('value' => $this->fields["dolog_method"]));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='4'>";
      echo "<div class='center'>". __("Filter access")."</div>";
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='4'>";
      echo "<i>".__('Leave these parameters empty to disable api access restriction')."</i>";
      echo "<br><br><br>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('IPv4 address range')."</td>";
      echo "<td colspan='3'>";
      echo "<input type='text' name='ipv4_range_start' value='".
            ($this->fields["ipv4_range_start"] ? long2ip($this->fields["ipv4_range_start"]) : '') .
            "' size='17'> - ";
      echo "<input type='text' name='ipv4_range_end' value='" .
            ($this->fields["ipv4_range_end"] ? long2ip($this->fields["ipv4_range_end"]) : '') .
            "' size='17'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('IPv6 address')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "ipv6");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".sprintf(__('%1$s (%2$s)'),  __('Application token'), "app_token")."</td>";
      echo "<td colspan='2'>";
      Html::autocompletionTextField($this, "app_token");
      echo "<br><input type='checkbox' name='_reset_app_token' id='app_token'>&nbsp;";
      echo "<label for='app_token'>".__('Regenerate')."</label>";
      echo "</td></tr>";

      $this->showFormButtons($options);
   }


   function prepareInputForAdd($input) {
      return $this->prepareInputForUpdate($input);
   }


   function prepareInputForUpdate($input) {

      if (isset($input['ipv4_range_start'])) {
         $input['ipv4_range_start'] = ip2long($input['ipv4_range_start']);
      }

      if (isset($input['ipv4_range_end'])) {
         $input['ipv4_range_end'] = ip2long($input['ipv4_range_end']);
      }

      if (isset($input['ipv4_range_start']) && isset($input['ipv4_range_end'])) {
         if (empty($input['ipv4_range_start'])) {
            $input['ipv4_range_start'] = "NULL";
            $input['ipv4_range_end'] = "NULL";
         } else {
            if (empty($input['ipv4_range_end'])) {
               $input['ipv4_range_end'] = $input['ipv4_range_start'];
            }

            if ($input['ipv4_range_end'] < $input['ipv4_range_start']) {
               $tmp = $input['ipv4_range_end'];
               $input['ipv4_range_end'] = $input['ipv4_range_start'];
               $input['ipv4_range_start'] = $tmp;
            }
         }
      }

      if (isset($input['_reset_app_token'])) {
         $input['app_token']      = self::getUniqueAppToken();
         $input['app_token_date'] = $_SESSION['glpi_currenttime'];
      }

      return $input;
   }


   static function getLogMethod() {

      return array(self::DOLOG_DISABLED   => __('Disabled'),
                   self::DOLOG_HISTORICAL => __('Historical'),
                   self::DOLOG_LOGS       => _n('Log', 'Logs',
                                                Session::getPluralNumber()));
   }


   /**
    * Get app token checking that it is unique
    *
    * @return string app token
   **/
   static function getUniqueAppToken() {
      global $DB;

      $ok = false;
      do {
         $key    = Toolbox::getRandomString(40);
         $query  = "SELECT COUNT(*)
                    FROM `".self::getTable()."`
                    WHERE `app_token` = '$key'";
         $result = $DB->query($query);

         if ($DB->result($result,0,0) == 0) {
            return $key;
         }
      } while (!$ok);

   }
}