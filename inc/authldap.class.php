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

/**
 *  Class used to manage Auth LDAP config
**/
class AuthLDAP extends CommonDBTM {

   const SIMPLE_INTERFACE = 'simple';
   const EXPERT_INTERFACE = 'expert';

   const ACTION_IMPORT      = 0;
   const ACTION_SYNCHRONIZE = 1;
   const ACTION_ALL         = 2;

   const USER_IMPORTED     = 0;
   const USER_SYNCHRONIZED = 1;
   const USER_DELETED_LDAP = 2;

   //Import user by giving his login
   const IDENTIFIER_LOGIN = 'login';

   //Import user by giving his email
   const IDENTIFIER_EMAIL = 'email';

   // From CommonDBTM
   public $dohistory = true;

   static function getTypeName() {
      global $LANG;

      return $LANG['login'][2];
   }


   function canCreate() {
      return haveRight('config', 'w');
   }


   function canView() {
      return haveRight('config', 'r');
   }


   function post_getEmpty () {

      $this->fields['port']               = '389';
      $this->fields['condition']          = '';
      $this->fields['login_field']        = 'uid';
      $this->fields['use_tls']            = 0;
      $this->fields['group_field']        = '';
      $this->fields['group_condition']    = '';
      $this->fields['group_search_type']  = 0;
      $this->fields['group_member_field'] = '';
      $this->fields['email_field']        = 'mail';
      $this->fields['realname_field']     = 'cn';
      $this->fields['firstname_field']    = 'givenname';
      $this->fields['phone_field']        = 'telephonenumber';
      $this->fields['phone2_field']       = '';
      $this->fields['mobile_field']       = '';
      $this->fields['comment_field']      = '';
      $this->fields['title_field']        = '';
      $this->fields['use_dn']             = 0;
   }


   /**
    * Preconfig datas for standard system
    * @param $type type of standard system : AD
    *
    *@return nothing
    **/
   function preconfig($type) {

      switch($type) {
         case 'AD' :
            $this->fields['port']               = "389";
            $this->fields['condition'] =
               '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
            $this->fields['login_field']        = 'samaccountname';
            $this->fields['use_tls']            = 0;
            $this->fields['group_field']        = 'memberof';
            $this->fields['group_condition'] =
               '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
            $this->fields['group_search_type']  = 0;
            $this->fields['group_member_field'] = '';
            $this->fields['email_field']        = 'mail';
            $this->fields['realname_field']     = 'sn';
            $this->fields['firstname_field']    = 'givenname';
            $this->fields['phone_field']        = 'telephonenumber';
            $this->fields['phone2_field']       = 'othertelephone';
            $this->fields['mobile_field']       = 'mobile';
            $this->fields['comment_field']      = 'info';
            $this->fields['title_field']        = 'title';
            $this->fields['entity_field']       = 'ou';
            $this->fields['entity_condition']   = '(objectclass=organizationalUnit)';
            $this->fields['use_dn']             = 1 ;
            break;

         default:
            $this->post_getEmpty();
      }
   }


   function prepareInputForUpdate($input) {

      if (isset($input["rootdn_password"]) && empty($input["rootdn_password"])) {
         unset($input["rootdn_password"]);
      }

      // Set attributes in lower case
      if (count($input)) {
         foreach ($input as $key => $val) {
            if (preg_match('/_field$/',$key)) {
               $input[$key] = utf8_strtolower($val);
            }
         }
      }
      return $input;
   }


   /**
   * Print the auth ldap form
   *
   * @param $ID integer ID of the item
   * @param $options array
   *     - target for the Form
   *
   *@return Nothing (display)
   **/
   function showForm($ID, $options=array()) {
      global $LANG;

      if (!haveRight("config", "w")) {
         return false;
      }
      $spotted = false;
      if (empty ($ID)) {
         if ($this->getEmpty()) {
            $spotted = true;
         }
         if (isset($_GET['preconfig'])) {
            $this->preconfig($_GET['preconfig']);
         }
      } else {
         if ($this->getFromDB($ID)) {
            $spotted = true;
         }
      }

      if (canUseLdap()) {
         $this->showTabs($options);
         $this->showFormHeader($options);
         if (empty($ID)) {
            $target = $_SERVER['PHP_SELF'];
            echo "<tr class='tab_bg_2'><td>".$LANG['ldap'][16]."&nbsp;:</td> ";
            echo "<td colspan='3'>";
            echo "<a href='$target?preconfig=AD'>".$LANG['ldap'][17]."</a>";
            echo "&nbsp;&nbsp;/&nbsp;&nbsp;";
            echo "<a href='$target?preconfig=default'>".$LANG['common'][44];
            echo "</a></td></tr>";
         }
         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][16] . "&nbsp;:</td>";
         echo "<td><input type='text' name='name' value='". $this->fields["name"] ."'></td>";
         echo ($ID>0 ?"<td>".$LANG['common'][26]."&nbsp;:</td><td>".
               convDateTime($this->fields["date_mod"]):"<td colspan='2'>&nbsp;");
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['ldap'][44] . "&nbsp;:</td>";
         echo "<td colspan='3'>";
         Dropdown::showYesNo('is_default',$this->fields['is_default']);
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][52] . "&nbsp;:</td>";
         echo "<td><input type='text' name='host' value='" . $this->fields["host"] . "'></td>";
         echo "<td>" . $LANG['setup'][172] . "&nbsp;:</td>";
         echo "<td><input id='port' type='text' name='port' value='" . $this->fields["port"] . "'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][159] . "&nbsp;:</td>";
         echo "<td colspan='3'>";
         echo "<input type='text' name='condition' value='".$this->fields["condition"]."' size='100'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][154] . "&nbsp;:</td>";
         echo "<td colspan='3'>";
         echo "<input type='text' name='basedn' size='100' value='" . $this->fields["basedn"] . "'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][155] . "&nbsp;:</td>";
         echo "<td colspan='3'><input type='text' name='rootdn' size='100' value='".
                $this->fields["rootdn"]."'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][156] . "&nbsp;:</td>";
         echo "<td><input type='password' name='rootdn_password' value='' autocomplete='off'></td>";
         echo "<td>" . $LANG['setup'][228] . "&nbsp;:</td>";
         echo "<td><input type='text' name='login_field' value='".$this->fields["login_field"]."'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][25] . "&nbsp;:</td>";
         echo "<td colspan='3'>";
         echo "<textarea cols='40' rows='4' name='comment'>".$this->fields["comment"]."</textarea>";

         //Fill fields when using preconfiguration models
         if (!$ID) {
            $hidden_fields = array ('port', 'condition' , 'login_field', 'use_tls', 'group_field',
                                    'group_condition', 'group_search_type', 'group_member_field',
                                    'email_field', 'realname_field', 'firstname_field', 'phone_field',
                                    'phone2_field', 'mobile_field', 'comment_field', 'title_field',
                                    'use_dn', 'entity_field', 'entity_condition');

            foreach ($hidden_fields as $hidden_field) {
               echo "<input type='hidden' name='$hidden_field' value='".$this->fields[$hidden_field]."'>";
            }
         }

         echo "</td></tr>";

         $this->showFormButtons($options);
         $this->addDivForTabs();
      } else {
         echo "<div class='center'>&nbsp;<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . $LANG['login'][2] . "</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<p class='red'>" . $LANG['setup'][157] . "</p>";
         echo "<p>" . $LANG['setup'][158] . "</p></td></tr></table></div>";
      }
   }


   function showFormAdvancedConfig($ID, $target) {
      global $LANG;

      echo "<form method='post' action='$target'>";
      echo "<div class='center'><table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_2'><th colspan='4'>";
      echo "<input type='hidden' name='id' value='$ID'>". $LANG['entity'][14] . "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['setup'][180] . "&nbsp;:</td><td>";
      if (function_exists("ldap_start_tls")) {
         $use_tls = $this->fields["use_tls"];
         echo "<select name='use_tls'>";
         echo "<option value='0' " . (!$use_tls ? " selected " : "") . ">" . $LANG['choice'][0] .
               "</option>";
         echo "<option value='1' " . ($use_tls ? " selected " : "") . ">" . $LANG['choice'][1] .
               "</option>";
         echo "</select>";
      } else {
         echo "<input type='hidden' name='use_tls' value='0'>".$LANG['setup'][181];
      }
      echo "</td>";
      echo "<td>" . $LANG['setup'][186] . "&nbsp;:</td><td>";
      Dropdown::showGMT("time_offset",$this->fields["time_offset"]);
      echo"</td></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['ldap'][30] . "&nbsp;:</td><td colspan='4'>";
      $alias_options[LDAP_DEREF_NEVER]     = $LANG['ldap'][31];
      $alias_options[LDAP_DEREF_ALWAYS]    = $LANG['ldap'][32];
      $alias_options[LDAP_DEREF_SEARCHING] = $LANG['ldap'][33];
      $alias_options[LDAP_DEREF_FINDING]   = $LANG['ldap'][34];
      Dropdown::showFromArray("deref_option", $alias_options,
                              array('value' => $this->fields["deref_option"]));
      echo"</td></tr>";
      echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
      echo "<input type='submit' name='update' class='submit' value='".$LANG['buttons'][2]."'></td>";
      echo "</td></tr>";
      echo "</table></form></div>";
   }


   function showFormReplicatesConfig($ID, $target) {
      global $LANG, $DB;

      AuthLdapReplicate::addNewReplicateForm($target, $ID);

      $sql = "SELECT *
              FROM `glpi_authldapreplicates`
              WHERE `authldaps_id` = '$ID'
              ORDER BY `name`";
      $result = $DB->query($sql);

      if ($DB->numrows($result) >0) {
         echo "<br>";
         $canedit = haveRight("config", "w");
         echo "<form action='$target' method='post' name='ldap_replicates_form'
                id='ldap_replicates_form'>";
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";

         echo "<input type='hidden' name='id' value='$ID'>";
         echo "<tr><th colspan='4'>".$LANG['ldap'][18] . "</th></tr>";

         if (isset($_SESSION["LDAP_TEST_MESSAGE"])) {
            echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
            echo $_SESSION["LDAP_TEST_MESSAGE"];
            echo"</td></tr>";
            unset($_SESSION["LDAP_TEST_MESSAGE"]);
         }

         echo "<tr class='tab_bg_2'><td></td>";
         echo "<td class='center b'>".$LANG['common'][16]."</td>";
         echo "<td class='center b'>".$LANG['ldap'][18]."</td><td class='center'></td></tr>";
         while ($ldap_replicate = $DB->fetch_array($result)) {
            echo "<tr class='tab_bg_1'><td class='center' width='10'>";
            if (isset ($_GET["select"]) && $_GET["select"] == "all") {
               $sel = "checked";
            }
            $sel ="";
            echo "<input type='checkbox' name='item[" . $ldap_replicate["id"] . "]' value='1' $sel>";
            echo "</td>";
            echo "<td class='center'>" . $ldap_replicate["name"] . "</td>";
            echo "<td class='center'>".$ldap_replicate["host"]."&nbsp;: ".$ldap_replicate["port"];
            echo "</td><td class='center'>";
            echo "<input type='submit' name='test_ldap_replicate[".$ldap_replicate["id"]."]'
                  class='submit' value='" . $LANG['buttons'][50] . "'></td>";
            echo"</tr>";
         }
         echo "</table>";

         openArrowMassive("ldap_replicates_form", true);
         closeArrowMassive('delete_replicate', $LANG['buttons'][6]);

         echo "</div></form>";
      }
   }


   function showFormGroupsConfig($ID, $target) {
      global $LANG;

      echo "<form method='post' action='$target'>";
      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<input type='hidden' name='id' value='$ID'>";

      echo "<th class='center' colspan='4'>" . $LANG['setup'][259] . "</th></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][254] . "&nbsp;:</td><td>";
      $group_search_type = $this->fields["group_search_type"];
      echo "<select name='group_search_type'>";
      echo "<option value='0' " . (($group_search_type == 0) ? " selected " : "") . ">" .
             $LANG['setup'][256] . "</option>";
      echo "<option value='1' " . (($group_search_type == 1) ? " selected " : "") . ">" .
             $LANG['setup'][257] . "</option>";
      echo "<option value='2' " . (($group_search_type == 2) ? " selected " : "") . ">" .
             $LANG['setup'][258] . "</option>";
      echo "</select></td>";
      echo "<td>" . $LANG['setup'][260] . "&nbsp;:</td>";
      echo "<td><input type='text' name='group_field' value='".$this->fields["group_field"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][253] . "&nbsp;:</td><td colspan='3'>";
      echo "<input type='text' name='group_condition' value='".
             $this->fields["group_condition"]."' size='100'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][255] . "&nbsp;:</td>";
      echo "<td><input type='text' name='group_member_field' value='".
                 $this->fields["group_member_field"]."'></td>";

      echo "<td>" . $LANG['setup'][262] . "&nbsp;:</td>";
      echo "<td>";
      Dropdown::showYesNo("use_dn",$this->fields["use_dn"]);
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
      echo "<input type='submit' name='update' class='submit' value='".$LANG['buttons'][2]."'></td>";
      echo "</td></tr>";
      echo "</table></form></div>";
   }


   function showFormTestLDAP ($ID, $target) {
      global $LANG;

      if ($ID>0) {
         echo "<form method='post' action='$target'>";
         echo "<div class='center'><table class='tab_cadre_fixe'>";
         echo "<input type='hidden' name='id' value='$ID'>";
         echo "<tr><th colspan='4'>" . $LANG['ldap'][9] . "</th></tr>";

         if (isset($_SESSION["LDAP_TEST_MESSAGE"])) {
            echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
            echo $_SESSION["LDAP_TEST_MESSAGE"];
            echo"</td></tr>";
            unset($_SESSION["LDAP_TEST_MESSAGE"]);
         }
         echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
         echo "<input type='submit' name='test_ldap' class='submit' value='".$LANG['buttons'][2]."'>";
         echo "</td></tr>";
         echo "</table></div></form>";
      }
   }


   function showFormUserConfig($ID,$target) {
      global $LANG;

      echo "<form method='post' action='$target'>";
      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<input type='hidden' name='id' value='$ID'>";

      echo "<tr class='tab_bg_1'>";
      echo "<th class='center' colspan='4'>" . $LANG['setup'][167] . "</th></tr>";

      echo "<tr class='tab_bg_2'><td>" . $LANG['common'][48] . "&nbsp;:</td>";
      echo "<td><input type='text' name='realname_field' value='".
                 $this->fields["realname_field"]."'></td>";
      echo "<td>" . $LANG['common'][43] . "&nbsp;:</td>";
      echo "<td><input type='text' name='firstname_field' value='".
                 $this->fields["firstname_field"]."'></td></tr>";

      echo "<tr class='tab_bg_2'><td>" . $LANG['common'][25] . "&nbsp;:</td>";
      echo "<td><input type='text' name='comment_field' value='".$this->fields["comment_field"]."'>";
      echo "</td>";
      echo "<td>" . $LANG['setup'][14] . "&nbsp;:</td>";
      echo "<td><input type='text' name='email_field' value='".$this->fields["email_field"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>" . $LANG['help'][35] . "&nbsp;:</td>";
      echo "<td><input type='text' name='phone_field'value='".$this->fields["phone_field"]."'>";
      echo "</td>";
      echo "<td>" . $LANG['help'][35] . " 2 &nbsp;:</td>";
      echo "<td><input type='text' name='phone2_field'value='".$this->fields["phone2_field"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>" . $LANG['common'][42] . "&nbsp;:</td>";
      echo "<td><input type='text' name='mobile_field'value='".$this->fields["mobile_field"]."'>";
      echo "</td>";
      echo "<td>" . $LANG['users'][1] . "&nbsp;:</td>";
      echo "<td><input type='text' name='title_field' value='".$this->fields["title_field"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td>" . $LANG['users'][2] . "&nbsp;:</td>";
      echo "<td><input type='text' name='category_field' value='".
                 $this->fields["category_field"]."'></td>";
      echo "<td>" . $LANG['setup'][41] . "&nbsp;:</td>";
      echo "<td><input type='text' name='language_field' value='".
                 $this->fields["language_field"]."'></td></tr>";
      echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
      echo "<input type='submit' name='update' class='submit' value='".$LANG['buttons'][2]."'></td>";
      echo "</td></tr>";
      echo "</table></div></form>";
   }


   function showFormEntityConfig($ID, $target) {
      global $LANG;

      echo "<form method='post' action='$target'>";
      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<input type='hidden' name='id' value='$ID'>";

      echo "<th class='center' colspan='4'>" . $LANG['setup'][623] . "</th></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][622] . "&nbsp;:</td>";
      echo "<td colspan='3'>";
      echo "<input type='text' name='entity_field' value='".$this->fields["entity_field"]."'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][621] . "&nbsp;:</td>";
      echo "<td colspan='3'><input type='text' name='entity_condition' value='".
            $this->fields["entity_condition"]."' size='100'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
      echo "<input type='submit' name='update' class='submit' value='".$LANG['buttons'][2]."'></td>";
      echo "</td></tr>";
      echo "</table></div></form>";
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $ong[1] = $LANG['title'][26];

      if ($this->fields['id'] > 0) {
         $ong[2]  = $LANG['Menu'][14];
         $ong[3]  = $LANG['Menu'][36];
         $ong[4]  = $LANG['entity'][0];
         $ong[5]  = $LANG['entity'][14];
         $ong[6]  = $LANG['ldap'][22];
         $ong[12] = $LANG['title'][38];
      }
      return $ong;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['login'][2];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'host';
      $tab[3]['linkfield'] = 'host';
      $tab[3]['name']      = $LANG['common'][52];

      $tab[4]['table']     = $this->getTable();
      $tab[4]['field']     = 'port';
      $tab[4]['linkfield'] = 'port';
      $tab[4]['name']      = $LANG['setup'][175];

      $tab[5]['table']     = $this->getTable();
      $tab[5]['field']     = 'basedn';
      $tab[5]['linkfield'] = 'basedn';
      $tab[5]['name']      = $LANG['setup'][154];

      $tab[6]['table']     = $this->getTable();
      $tab[6]['field']     = 'condition';
      $tab[6]['linkfield'] = 'condition';
      $tab[6]['name']      = $LANG['setup'][159];

      $tab[7]['table']     = $this->getTable();
      $tab[7]['field']     = 'is_default';
      $tab[7]['linkfield'] = '';
      $tab[7]['name']      = $LANG['ldap'][44];
      $tab[7]['datatype']  = 'bool';

      $tab[8]['table']     = $this->getTable();
      $tab[8]['field']     = 'login_field';
      $tab[8]['linkfield'] = '';
      $tab[8]['name']      = $LANG['setup'][14];

      $tab[9]['table']     = $this->getTable();
      $tab[9]['field']     = 'realname_field';
      $tab[9]['linkfield'] = '';
      $tab[9]['name']      = $LANG['common'][48];

      $tab[10]['table']     = $this->getTable();
      $tab[10]['field']     = 'firstname_field';
      $tab[10]['linkfield'] = '';
      $tab[10]['name']      = $LANG['common'][43];

      $tab[11]['table']     = $this->getTable();
      $tab[11]['field']     = 'phone_field';
      $tab[11]['linkfield'] = '';
      $tab[11]['name']      = $LANG['help'][35];

      $tab[12]['table']     = $this->getTable();
      $tab[12]['field']     = 'phone2_field';
      $tab[12]['linkfield'] = '';
      $tab[12]['name']      = $LANG['help'][35]." 2";

      $tab[13]['table']     = $this->getTable();
      $tab[13]['field']     = 'mobile_field';
      $tab[13]['linkfield'] = '';
      $tab[13]['name']      = $LANG['common'][42];

      $tab[14]['table']     = $this->getTable();
      $tab[14]['field']     = 'title_field';
      $tab[14]['linkfield'] = '';
      $tab[14]['name']      = $LANG['users'][1];

      $tab[15]['table']     = $this->getTable();
      $tab[15]['field']     = 'category_field';
      $tab[15]['linkfield'] = '';
      $tab[15]['name']      = $LANG['users'][2];

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';


      $tab[17]['table']     = $this->getTable();
      $tab[17]['field']     = 'email_field';
      $tab[17]['linkfield'] = '';
      $tab[17]['name']      = $LANG['setup'][14];

      $tab[18]['table']     = $this->getTable();
      $tab[18]['field']     = 'use_dn';
      $tab[18]['linkfield'] = '';
      $tab[18]['name']      = $LANG['setup'][262];
      $tab[18]['datatype']  = 'bool';

      $tab[19]['table']       = $this->getTable();
      $tab[19]['field']       = 'date_mod';
      $tab[19]['linkfield']   = '';
      $tab[19]['name']        = $LANG['common'][26];
      $tab[19]['datatype']    = 'datetime';

      $tab[20]['table']     = $this->getTable();
      $tab[20]['field']     = 'language_field';
      $tab[20]['linkfield'] = '';
      $tab[20]['name']      = $LANG['setup'][41];

      $tab[21]['table']     = $this->getTable();
      $tab[21]['field']     = 'group_field';
      $tab[21]['linkfield'] = '';
      $tab[21]['name']      = $LANG['setup'][260];

      $tab[22]['table']     = $this->getTable();
      $tab[22]['field']     = 'group_condition';
      $tab[22]['linkfield'] = '';
      $tab[22]['name']      = $LANG['setup'][253];

      $tab[23]['table']     = $this->getTable();
      $tab[23]['field']     = 'group_member_field';
      $tab[23]['linkfield'] = '';
      $tab[23]['name']      = $LANG['setup'][255];

      $tab[24]['table']     = $this->getTable();
      $tab[24]['field']     = 'group_search_type';
      $tab[24]['linkfield'] = '';
      $tab[24]['name']      = $LANG['setup'][254];

      return $tab;
   }


   function showSystemInformations($width) {
      global $LANG;

      $ldap_servers = AuthLdap::getLdapServers ();

      if (!empty($ldap_servers)) {
         echo "\n</pre></td><tr class='tab_bg_2'><th>" . $LANG['login'][2] . "</th></tr>\n";
         echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";
         foreach ($ldap_servers as $ID => $value) {
            $fields = array($LANG['common'][52] => 'host',
                            $LANG['setup'][172] => 'port',
                            $LANG['setup'][154] => 'basedn',
                            $LANG['setup'][159] => 'condition',
                            $LANG['setup'][155] => 'rootdn',
                            $LANG['setup'][180] => 'use_tls');
            $msg = '';
            $first = true;
            foreach($fields as $label => $field) {
               $msg .= (!$first?', ':'').$label.': '.($value[$field] != ''?'\''.$value[$field].
                        '\'':$LANG['common'][49]);
               $first = false;
            }
            echo wordwrap($msg."\n", $width, "\n\t\t");
         }
      }

      echo "\n</pre></td></tr>";
   }


   /**
    * Get LDAP fields to sync to GLPI data from a glpi_authldaps array
    *
    * @param $authtype_array Authentication method config array (from table)
    *
    * @return array of "user table field name" => "config value"
    */
   static function getSyncFields($authtype_array) {

      $ret = array();

      $fields = array('login_field'     => 'name',
                      'email_field'     => 'email',
                      'realname_field'  => 'realname',
                      'firstname_field' => 'firstname',
                      'phone_field'     => 'phone',
                      'phone2_field'    => 'phone2',
                      'mobile_field'    => 'mobile',
                      'comment_field'   => 'comment',
                      'title_field'     => 'usertitles_id',
                      'category_field'  => 'usercategories_id',
                      'language_field'  => 'language');

      foreach ($fields as $key => $val) {
         if (isset($authtype_array[$key])) {
            $ret[$val] = $authtype_array[$key];
         }
      }
      return $ret;
   }


   /** Display LDAP filter
    *
    * @param   $target target for the form
    * @param   $users boolean : for user ?
    * @return nothing
    */
   static function displayLdapFilter($target, $users=true) {
      global $LANG;

      $config_ldap = new AuthLDAP();
      $res = $config_ldap->getFromDB($_SESSION["ldap_server"]);

      if ($users) {
         $filter_name1 = "condition";
         $filter_var   = "ldap_filter";
      } else {
         $filter_var = "ldap_group_filter";
         switch ($config_ldap->fields["group_search_type"]) {
            case 0 :
               $filter_name1 = "condition";
               break;

            case 1 :
               $filter_name1 = "group_condition";
               break;

            case 2 :
               $filter_name1 = "group_condition";
               $filter_name2 = "condition";
               break;
         }
      }

      if (!isset($_SESSION[$filter_var]) || $_SESSION[$filter_var] == '') {
         $_SESSION[$filter_var] = $config_ldap->fields[$filter_name1];
      }

      echo "<div class='center'>";
      echo "<form method='post' action='$target'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>" . ($users?$LANG['setup'][263]:$LANG['setup'][253]) . "</th></tr>";
      echo "<tr class='tab_bg_2'><td class='center'>";
      echo "<input type='text' name='ldap_filter' value='" . $_SESSION[$filter_var] . "' size='70'>";

      //Only display when looking for groups in users AND groups
      if (!$users && $config_ldap->fields["group_search_type"] == 2) {
         if (!isset($_SESSION["ldap_group_filter2"]) || $_SESSION["ldap_group_filter2"] == '') {
            $_SESSION["ldap_group_filter2"] = $config_ldap->fields[$filter_name2];
         }

         echo "</td></tr>";
         echo "<tr><th colspan='2'>" . $LANG['setup'][263] . "</th></tr>";
         echo "<tr class='tab_bg_2'><td>";
         echo "<input type='text' name='ldap_filter2' value='".$_SESSION["ldap_group_filter2"].
               "' size='70'>";
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_2'><td class='center'>";
      echo "<input class=submit type='submit' name='change_ldap_filter' value='".
             $LANG['buttons'][2]."'></td></tr>";
      echo "</table></form></div>";
   }


   /** Converts LDAP timestamps over to Unix timestamps
    *
    * @param   $ldapstamp LDAP timestamp
    * @param   $ldap_time_offset time offset
    *
    * @return unix timestamp
    */
   static function ldapStamp2UnixStamp($ldapstamp, $ldap_time_offset=0) {
      global $CFG_GLPI;

      $year    = substr($ldapstamp,0,4);
      $month   = substr($ldapstamp,4,2);
      $day     = substr($ldapstamp,6,2);
      $hour    = substr($ldapstamp,8,2);
      $minute  = substr($ldapstamp,10,2);
      $seconds = substr($ldapstamp,12,2);
      $stamp   = gmmktime($hour,$minute,$seconds,$month,$day,$year);
      $stamp   += $CFG_GLPI["time_offset"]-$ldap_time_offset;

      return $stamp;
   }


   /** Converts a Unix timestamp to an LDAP timestamps
    *
    * @param   $days integer (number of days from now)
    *
    * @return ldap timestamp
    */
   static function date2ldapTimeStamp($days) {
      return date("YmdHis",strtotime("-$days day")).'Z';
   }


   /** Test a LDAP connection
    *
    * @param   $auths_id ID of the LDAP server
    * @param   $replicate_id use a replicate if > 0
    *
    * @return  boolean connection succeeded ?
    */
   static function testLDAPConnection($auths_id, $replicate_id=-1) {

      $config_ldap = new AuthLDAP();
      $res = $config_ldap->getFromDB($auths_id);
      $ldap_users = array ();

      // we prevent some delay...
      if (!$res) {
         return false;
      }

      //Test connection to a replicate
      if ($replicate_id != -1) {
         $replicate = new AuthLdapReplicate;
         $replicate->getFromDB($replicate_id);
         $host = $replicate->fields["host"];
         $port = $replicate->fields["port"];
      } else {
         //Test connection to a master ldap server
         $host = $config_ldap->fields['host'];
         $port = $config_ldap->fields['port'];
      }
      $ds = AuthLdap::connectToServer($host, $port, $config_ldap->fields['rootdn'],
                                      $config_ldap->fields['rootdn_password'],
                                      $config_ldap->fields['use_tls'],
                                      $config_ldap->fields['deref_option']);
      if ($ds) {
         return true;
      } else {
         return false;
      }
   }


   /** Show LDAP users to add or synchronise
    *
    * @return  nothing
    */
   static function showLdapUsers() {
      global $CFG_GLPI, $LANG;

      $values['order'] = 'DESC';
      $values['start'] = 0;

      foreach ($_SESSION['ldap_import'] as $option => $value) {
         $values[$option] = $value;
      }
      $results = array();
      $limitexceeded = false;
      $ldap_users = AuthLdap::getAllUsers($values,$results,$limitexceeded);

      if (is_array($ldap_users)) {
         $numrows = count($ldap_users);
         $action = "toprocess";
         $form_action = "process_ok";

         if ($numrows > 0) {
            if ($limitexceeded) {
               echo "<table class='tab_cadre_fixe'>";
               echo "<tr><th class='red'><img class='center' src='".$CFG_GLPI["root_doc"].
                                       "/pics/warning.png' alt='warning'>&nbsp;".$LANG['ldap'][8];
               echo "</th></tr></table><br>";
            }

            printPager($values['start'], $numrows, $_SERVER['PHP_SELF'],'');

            // delete end
            array_splice($ldap_users, $values['start'] + $_SESSION['glpilist_limit']);
            // delete begin
            if ($values['start'] > 0) {
               array_splice($ldap_users, 0, $values['start']);
            }

            echo "<form method='post' id='ldap_form' name='ldap_form' action='".$_SERVER['PHP_SELF']."'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>"
                  .(!$_SESSION['ldap_import']['mode']?$LANG['buttons'][37]:$LANG['ldap'][15])."</th>";
            $num = 0;
            echo Search::showHeaderItem(HTML_OUTPUT, $LANG['Menu'][14], $num,
                                        $_SERVER['PHP_SELF'].
                                                "?order=".($values['order']=="DESC"?"ASC":"DESC"));
            echo "<th>".$LANG['common'][26]." ".$LANG['ldap'][13]."</th>";
            if ($_SESSION['ldap_import']['mode']) {
               echo "<th>".$LANG['common'][26]." ".$LANG['ldap'][14]."</th>";
            }
            echo "</tr>";

            foreach ($ldap_users as $userinfos) {
               $link = $user = $userinfos["user"];
               if (isset($userinfos['id']) && haveRight('user','r')) {
                  $link = "<a href='".getItemTypeFormURL('User').'?id='.$userinfos['id']."'>$user</a>";
               }
               if (isset($userinfos["timestamp"])) {
                  $stamp = $userinfos["timestamp"];
               } else {
                  $stamp = '';
               }

               if (isset($userinfos["date_mod"])) {
                  $date_mod = $userinfos["date_mod"];
               } else {
                  $date_mod = '';
               }

               echo "<tr class='tab_bg_2 center'>";
               //Need to use " instead of ' because it doesn't work with names with ' inside !
               echo "<td><input type='checkbox' name=\"" . $action . "[" . $user . "]\"></td>";
               echo "<td>" . $link . "</td>";

               if ($stamp != '') {
                  echo "<td>" .convDateTime(date("Y-m-d H:i:s",$stamp)). "</td>";
               } else {
                  echo "<td>&nbsp;</td>";
               }
               if ($_SESSION['ldap_import']['mode']) {
                  if ($date_mod != '') {
                     echo "<td>" . convDateTime($date_mod) . "</td>";
                  } else {
                     echo "<td>&nbsp;</td>";
                  }
               }
               echo "</tr>";
            }
            if ($_SESSION['ldap_import']['mode']) {
               $colspan = 6;
            }
            else {
               $colspan = 5;
            }
            echo "</table>";

            openArrowMassive("ldap_form", true);
            closeArrowMassive($form_action,
                              ($_SESSION['ldap_import']['mode']?$LANG['ldap'][15]:$LANG['buttons'][37]));
            echo "</form>";

            printPager($values['start'], $numrows, $_SERVER['PHP_SELF'],'');
         } else {
            echo "<div class='center b'>".
                  ($_SESSION['ldap_import']['mode']?$LANG['ldap'][43]:$LANG['ldap'][3])."</div>";
         }
      } else {
         echo "<div class='center b'>".
               ($_SESSION['ldap_import']['mode']?$LANG['ldap'][43]:$LANG['ldap'][3])."</div>";
      }
   }


   /** Get the list of LDAP users to add/synchronize
    *
    * @param $options array options
    *          - ldapservers_id ID of the server to use
    *          - mode user to synchronise or add ?
    *          - ldap_filter ldap filter to use
    *          - basedn force basedn (default ldapservers_id one)
    *          - order display order
    *          - operator operator used to limit user updates days
    *          - days number of days to limit (with operator)
    *          - script true if called by an external script
    * @param $results result stats
    * @param $limitexceeded limit exceeded exception
    *
    * @return  array of the user
    */
   static function getAllUsers($options = array(), &$results, &$limitexceeded) {
      global $DB, $LANG,$CFG_GLPI;

      $config_ldap = new AuthLDAP();
      $res = $config_ldap->getFromDB($options['ldapservers_id']);

      $values['order']        = 'DESC';
      $values['mode']         = AuthLDAP::ACTION_SYNCHRONIZE;
      $values['ldap_filter']  = '';
      $values['basedn']       = $config_ldap->fields['basedn'];
      $values['days']         = 0;
      $values['operator']     = '<';
      //Called by an external script or not
      $values['script']       = 0;

      // TODO change loop ? : foreach ($values...) if isset($options[...])
      foreach ($options as $option => $value) {
         // this test break mode detection - if ($value != '') {
         $values[$option] = $value;
         //}
      }

      $ldap_users = array ();
      $limitexceeded = false;

      // we prevent some delay...
      if (!$res) {
         return false;
      }
      if ($values['order'] != "DESC") {
         $values['order'] = "ASC";
      }
      $ds = AuthLdap::connectToServer($config_ldap->fields['host'], $config_ldap->fields['port'],
                                      $config_ldap->fields['rootdn'],
                                      $config_ldap->fields['rootdn_password'],
                                      $config_ldap->fields['use_tls'],
                                      $config_ldap->fields['deref_option']);
      if ($ds) {
         //Search for ldap login AND modifyTimestamp,
         //which indicates the last update of the object in directory
         $attrs = array ($config_ldap->fields['login_field'], "modifyTimestamp");

         // Tenter une recherche pour essayer de retrouver le DN
         if ($values['ldap_filter'] == '') {
            $filter = "(".$config_ldap->fields['login_field']."=*)";
         } else {
            $filter = $values['ldap_filter'];
         }


         if ($values['script'] && $values['days']) {
            $filter_timestamp = self::addTimestampRestrictions($values['operator'],
                                                               $values['days']);
            $filter= "(&$filter $filter_timestamp)";
         }

         $sr = @ldap_search($ds, $values['basedn'], $filter, $attrs);

         if ($sr) {
            if (ldap_errno($ds) == 4) {
               // openldap return 4 for Size limit exceeded
               $limitexceeded = true;
            }
            $info = ldap_get_entries($ds, $sr);
            if (ldap_errno($ds) == 4) {
               $limitexceeded = true;
            }
            $user_infos = array();

            for ($ligne = 0 ; $ligne < $info["count"] ; $ligne++) {
               //If ldap add
               if ($values['mode'] == AuthLDAP::ACTION_IMPORT) {
                  if (in_array($config_ldap->fields['login_field'], $info[$ligne])) {
                     $ldap_users[$info[$ligne][$config_ldap->fields['login_field']][0]] =
                           $info[$ligne][$config_ldap->fields['login_field']][0];
                     $user_infos[$info[$ligne][$config_ldap->fields['login_field']][0]]["timestamp"] =
                           AuthLdap::ldapStamp2UnixStamp($info[$ligne]['modifytimestamp'][0],
                                                         $config_ldap->fields['time_offset']);
                  }
               } else {
                  //If ldap synchronisation
                  if (in_array($config_ldap->fields['login_field'],$info[$ligne])) {
                     $ldap_users[$info[$ligne][$config_ldap->fields['login_field']][0]] =
                           AuthLdap::ldapStamp2UnixStamp($info[$ligne]['modifytimestamp'][0],
                                                         $config_ldap->fields['time_offset']);
                     $user_infos[$info[$ligne][$config_ldap->fields['login_field']][0]]["timestamp"] =
                           AuthLdap::ldapStamp2UnixStamp($info[$ligne]['modifytimestamp'][0],
                                                         $config_ldap->fields['time_offset']);
                   }
               }
            }
         } else {
            return false;
         }
      } else {
         return false;
      }

      $glpi_users = array ();
      $sql = "SELECT *
              FROM `glpi_users`";

      if ($values['mode'] != AuthLDAP::ACTION_IMPORT) {
         $sql .= " WHERE `authtype` IN (-1,".Auth::LDAP.",".Auth::EXTERNAL.", ". Auth::CAS.")
                         AND `auths_id` = '".$options['ldapservers_id']."'";
      }
      $sql .= " ORDER BY `name` ".$values['order'];

      foreach ($DB->request($sql) as $user) {
         //Ldap add : fill the array with the login of the user
         if ($values['mode'] == AuthLDAP::ACTION_IMPORT) {
            $glpi_users[$user['name']] = $user['name'];
         } else {
            //Ldap synchronisation : look if the user exists in the directory
            //and compares the modifications dates (ldap and glpi db)
            if (!empty ($ldap_users[$user['name']])) {
               //If entry was modified or if script should synchronize all the users
               if (($values['action'] == AuthLDAP::ACTION_ALL)
                   || ($ldap_users[$user['name']] - strtotime($user['date_mod']) > 0)) {

                  $glpi_users[] = array('id'        => $user['id'],
                                        'user'      => $user['name'],
                                        'timestamp' => $user_infos[$user['name']]['timestamp'],
                                        'date_mod'  => $user['date_mod']);
               }
            // Only manage deleted user if ALL (because of entity visibility in delegated mode)
            } else if ($values['action'] == AuthLDAP::ACTION_ALL) {
               //If user is marked as coming from LDAP, but is not present in it anymore
               if (!$user['is_deleted']
                   && $user['is_active']
                   && $user['auths_id'] == $options['ldapservers_id']) {

                  User::manageDeletedUserInLdap($user['id']);
                  $results[AuthLDAP::USER_DELETED_LDAP] ++;
               }
            }
         }
      }

      //If add, do the difference between ldap users and glpi users
      if ($values['mode'] == AuthLDAP::ACTION_IMPORT) {
         $diff = array_diff_ukey($ldap_users,$glpi_users,'strcasecmp');
         $list = array();

         foreach ($diff as $user) {
            $list[] = array("user"      => $user,
                            "timestamp" => $user_infos[$user]["timestamp"],
                            "date_mod"  => DROPDOWN_EMPTY_VALUE);
         }
         if ($values['order'] == 'DESC') {
            rsort($list);
         } else {
            sort($list);
         }

         return $list;
      }
      return $glpi_users;
   }


   /** Show LDAP groups to add or synchronise in an entity
    *
    * @param   $target target page for the form
    * @param   $check check all ? -> need to be delete
    * @param   $start where to start the list
    * @param   $sync synchronise or add ?
    * @param   $filter ldap filter to use
    * @param   $filter2 second ldap filter to use (which case ?)
    * @param   $entity working entity
    * @param   $order display order
    *
    * @return  nothing
    */
   static function showLdapGroups($target, $check, $start, $sync = 0, $filter='', $filter2='',
                                  $entity, $order='DESC') {
      global $LANG;

      echo "<br>";
      $ldap_groups = AuthLdap::getAllGroups($_SESSION["ldap_server"], $filter, $filter2,
                                            $entity,$order);

      if (is_array($ldap_groups)) {
         $numrows = count($ldap_groups);
         $action = "toimport";
         $form_action = "import_ok";

         $colspan = (isMultiEntitiesMode()?5:4);
         if ($numrows > 0) {
            $parameters = "check=$check";
            printPager($start, $numrows, $target, $parameters);

            // delete end
            array_splice($ldap_groups, $start + $_SESSION['glpilist_limit']);
            // delete begin
            if ($start > 0) {
               array_splice($ldap_groups, 0, $start);
            }

            echo "<div class='center'>";
            echo "<form method='post' id='ldap_form' name='ldap_form'  action='$target'>";
            echo "<a href='" .
                  $target . "?check=all' onclick= \"if ( markCheckboxes('ldap_form') ) return false;\">" .
                  $LANG['buttons'][18] . "</a>&nbsp;/&nbsp;<a href='" .
                  $target . "?check=none' onclick= \"if ( unMarkCheckboxes('ldap_form') ) return false;\">" .
                  $LANG['buttons'][19] . "</a>";
            echo "<table class='tab_cadre'>";
            echo "<tr><th>" . $LANG['buttons'][37]. "</th>";
            $header_num=0;
            echo Search::showHeaderItem(HTML_OUTPUT, $LANG['common'][35], $header_num,
                                        $target."?order=".($order=="DESC"?"ASC":"DESC"),
                                        1, $order);
            echo "<th>".$LANG['setup'][261]."</th>";
            echo"<th>".$LANG['ocsng'][36]."</th>";
            if (isMultiEntitiesMode()) {
               echo"<th>".$LANG['entity'][9]."</th>";
            }

            echo "</tr>";

            foreach ($ldap_groups as $groupinfos) {
               $group = $groupinfos["cn"];
               $group_dn = $groupinfos["dn"];
               $search_type = $groupinfos["search_type"];

               echo "<tr class='tab_bg_2 center'>";
               //Need to use " instead of ' because it doesn't work with names with ' inside !
               echo "<td><input type='checkbox' name=\"" . $action . "[" .$group_dn . "]\" " .
                            ($check == "all" ? "checked" : "") ."></td>";
               echo "<td>" . $group . "</td>";
               echo "<td>" .$group_dn. "</td>";
               echo "<td>";
               Dropdown::show('Entity',
                              array('value'  => $entity,
                                    'name'   => "toimport_entities[" .$group_dn . "]=".$entity));
               echo "</td>";
               if (isMultiEntitiesMode()) {
                  echo "<td>";
                  Dropdown::showYesNo("toimport_recursive[" .$group_dn . "]", 0);
                  echo "</td>";
               }
               else {
                  echo "<input type='hidden' name=\"toimport_recursive[".$group_dn."]\" value='0'>";
               }
               echo "<input type='hidden' name=\"toimport_type[".$group_dn."]\" value=\"".
                        $search_type."\"></tr>";
            }
            echo "<tr class='tab_bg_1'><td colspan='$colspan' class='center'>";
            echo "<input class='submit' type='submit' name='".$form_action."' value='".
                  $LANG['buttons'][37] . "'>";
            echo "</td></tr>";
            echo "</table></form></div>";
            printPager($start, $numrows, $target, $parameters);
         } else {
            echo "<div class='center b'>" . $LANG['ldap'][25] . "</div>";
         }
      } else {
         echo "<div class='center b'>" . $LANG['ldap'][25] . "</div>";
      }
   }


   /** Get all LDAP groups from a ldap server which are not already in an entity
    *
    * @param   $auths_id ID of the server to use
    * @param   $filter ldap filter to use
    * @param   $filter2 second ldap filter to use if needed
    * @param   $entity entity to search
    * @param   $order order to use
    *
    * @return  array of the groups
    */
   static function getAllGroups($auths_id, $filter, $filter2, $entity, $order='DESC') {
      global $DB;

      $config_ldap = new AuthLDAP();
      $res = $config_ldap->getFromDB($auths_id);
      $infos = array();
      $groups = array();

      $ds = AuthLdap::connectToServer($config_ldap->fields['host'], $config_ldap->fields['port'],
                                      $config_ldap->fields['rootdn'],
                                      $config_ldap->fields['rootdn_password'],
                                      $config_ldap->fields['use_tls'],
                                      $config_ldap->fields['deref_option']);
      if ($ds) {
         switch ($config_ldap->fields["group_search_type"]) {
            case 0 :
               $infos = AuthLdap::getGroupsFromLDAP($ds, $config_ldap, $filter, false, $infos);
               break;

            case 1 :
               $infos = AuthLdap::getGroupsFromLDAP($ds, $config_ldap, $filter, true, $infos);
               break;

            case 2 :
               $infos = AuthLdap::getGroupsFromLDAP($ds, $config_ldap, $filter ,true, $infos);
               $infos = AuthLdap::getGroupsFromLDAP($ds, $config_ldap, $filter2, false, $infos);
               break;
         }

         if (!empty($infos)) {
            $glpi_groups = array();
            //Get all groups from GLPI DB for the current entity and the subentities
            $sql = "SELECT `name`
                    FROM `glpi_groups` ".
                    getEntitiesRestrictRequest("WHERE","glpi_groups");

            $res = $DB->query($sql);
            //If the group exists in DB -> unset it from the LDAP groups
            while ($group = $DB->fetch_array($res)) {
               $glpi_groups[$group["name"]] = 1;
            }
            $ligne = 0;

            foreach ($infos as $dn => $info) {
               if (!isset($glpi_groups[$info["cn"]])) {
                  $groups[$ligne]["dn"] = $dn;
                  $groups[$ligne]["cn"] = $info["cn"];
                  $groups[$ligne]["search_type"] = $info["search_type"];
                  $ligne++;
               }
            }
         }

         if ($order == 'DESC') {
            function local_cmp($b,$a) {
               return strcasecmp($a['cn'],$b['cn']);
            }
         } else {
            function local_cmp($a,$b) {
               return strcasecmp($a['cn'],$b['cn']);
            }
         }
         usort($groups,'local_cmp');

      }
      return $groups;
   }


   /**
    * Get the group's cn by giving his DN
    * @param $ldap_connection ldap connection to use
    * @param $group_dn the group's dn
    *
    * @return the group cn
    */
   static function getGroupCNByDn($ldap_connection, $group_dn) {

      $sr = @ ldap_read($ldap_connection, $group_dn, "objectClass=*", array("cn"));
      $v = ldap_get_entries($ldap_connection, $sr);
      if (!is_array($v) || count($v) == 0 || empty ($v[0]["cn"][0])) {
         return false;
      }
      return $v[0]["cn"][0];
   }


   static function getGroupsFromLDAP($ldap_connection, $config_ldap, $filter,
                                     $search_in_groups=true, $groups=array()) {

      //First look for groups in group objects
      $extra_attribute = ($search_in_groups?"cn":$config_ldap->fields["group_field"]);
      $attrs = array ("dn", $extra_attribute);

      if ($filter == '') {
         if ($search_in_groups) {
            $filter = (!empty($config_ldap->fields['group_condition'])?
                       $config_ldap->fields['group_condition']:"(objectclass=*)");
         } else {
            $filter = (!empty($config_ldap->fields['condition'])?
                       $config_ldap->fields['condition']:"(objectclass=*)");
         }
      }
      $sr = @ldap_search($ldap_connection, $config_ldap->fields['basedn'], $filter , $attrs);

      if ($sr) {
         $infos = ldap_get_entries($ldap_connection, $sr);
         for ($ligne=0 ; $ligne < $infos["count"] ; $ligne++) {
            if ($search_in_groups) {
               // No cn : not a real object
               if (isset($infos[$ligne]["cn"][0])) {
                  $cn = $infos[$ligne]["cn"][0];
                  $groups[$infos[$ligne]["dn"]] = (array("cn"          => $infos[$ligne]["cn"][0],
                                                         "search_type" => "groups"));
               }
            } else {
               if (isset($infos[$ligne][$extra_attribute])) {
                  for ($ligne_extra=0 ; $ligne_extra < $infos[$ligne][$extra_attribute]["count"] ;
                       $ligne_extra++) {

                     $groups[$infos[$ligne][$extra_attribute][$ligne_extra]] =
                        array("cn"          => AuthLdap::getGroupCNByDn($ldap_connection,
                                                   $infos[$ligne][$extra_attribute][$ligne_extra]),
                              "search_type" => "users");
                  }
               }
            }
         }
      }
      return $groups;
   }


   /** Form to choose a ldap server
    *
    * @param   $target target page for the form
    *
    * @return  nothing
    */
   static function ldapChooseDirectory($target) {
      global $DB, $LANG;

      $query = "SELECT *
                FROM `glpi_authldaps`
                ORDER BY `name` ASC";
      $result = $DB->query($query);

      if ($DB->numrows($result) == 1) {
         //If only one server, do not show the choose ldap server window
         $ldap = $DB->fetch_array($result);
         $_SESSION["ldap_server"] = $ldap["id"];
         glpi_header($_SERVER['PHP_SELF']);
      }

      echo "<form action='$target' method=\"post\">";
      echo "<div class='center'>";
      echo "<p >" . $LANG['ldap'][5] . "</p>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'><th colspan='2'>" . $LANG['ldap'][4] . "</th></tr>";
      //If more than one ldap server
      if ($DB->numrows($result) > 1) {
         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][16] . "</td>";
         echo "<td class='center'>";
         Dropdown::show('AuthLDAP', array('name'                => 'ldap_server',
                                          'display_emptychoice' => false,
                                          'comment'             => true));
         echo "</td></tr>";
         echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
         echo "<input class='submit' type='submit' name='ldap_showusers' value='".
               $LANG['buttons'][2] . "'></td></tr>";
      } else {
         //No ldap server
         echo "<tr class='tab_bg_2'><td class='center' colspan='2'>".$LANG['ldap'][7]."</td></tr>";
      }
      echo "</table></div></form>";
   }


   /** Import a user from a specific ldap server
    *
    * @param   $params  array of parameters : method (IDENTIFIER_LOGIN or IDENTIFIER_EMAIL) + value
    * @param   $action synchoronize (true) or import (false)
    * @param   $ldap_server ID of the LDAP server to use
    * @param   $display display message information on redirect
    *
    * @return  nothing
    */
   static function ldapImportUserByServerId($params=array(), $action, $ldap_server, $display=false) {
      global $DB, $LANG;

      $params = stripslashes_deep($params);

      $config_ldap = new AuthLDAP();
      $res = $config_ldap->getFromDB($ldap_server);
      $ldap_users = array ();

      // we prevent some delay...
      if (!$res) {
         return false;
      }

      $search_parameters = array();
      //Connect to the directory
      $ds = AuthLdap::connectToServer($config_ldap->fields['host'], $config_ldap->fields['port'],
                                      $config_ldap->fields['rootdn'],
                                      $config_ldap->fields['rootdn_password'],
                                      $config_ldap->fields['use_tls'],
                                      $config_ldap->fields['deref_option']);

      if ($ds) {
         $search_parameters['method'] = $params['method'];
         $search_parameters['fields'][AuthLdap::IDENTIFIER_LOGIN] =
                                                               $config_ldap->fields['login_field'];
         if ($params['method'] == AuthLdap::IDENTIFIER_EMAIL) {
            $search_parameters['fields'][AuthLdap::IDENTIFIER_EMAIL] =
                                                               $config_ldap->fields['email_field'];
         }

         //Get the user's dn & login
         $attribs = array('basedn'            => $config_ldap->fields['basedn'],
                          'login_field' => $search_parameters['fields'][$search_parameters['method']],
                          'search_parameters' => $search_parameters,
                          'user_params'       => $params,
                          'condition'         => $config_ldap->fields['condition']);

         $infos = AuthLdap::searchUserDn($ds,$attribs);

         if ($infos && $infos['dn']) {
            $user_dn = $infos['dn'];
            $login = $infos[$config_ldap->fields['login_field']];
            $groups = array();
            $user = new User();
            //Get informations from LDAP
            if ($user->getFromLDAP($ds, $config_ldap->fields, $user_dn, addslashes($login))) {
               //Add the auth method
               // Force date mod
               $user->fields["date_mod"] = $_SESSION["glpi_currenttime"];

               if ($action == AuthLdap::ACTION_IMPORT) {
                  $user->fields["authtype"] = Auth::LDAP;
                  $user->fields["auths_id"] = $ldap_server;
                  //Save informations in database !
                  $input = $user->fields;
                  unset ($user->fields);
                  // Display message after redirect
                  if ($display) {
                     $input['add'] = 1;
                  }
                  $user->fields["id"] = $user->add($input);
                  return array('action' => AuthLDAP::USER_IMPORTED,
                               'id'     => $user->fields["id"]);
               } else {
                  $input = $user->fields;
                  $input['id'] = User::getIdByName($login);

                  if ($display) {
                     $input['update'] = 1;
                  }
                  $user->update($input);
                  return array('action' => AuthLDAP::USER_SYNCHRONIZED,
                               'id'     => $input['id']);
               }
            } else {
               return false;
            }

         } else if ($action != AuthLDAP::ACTION_IMPORT) {
            $users_id = User::getIdByName($params['value']);
            User::manageDeletedUserInLdap($users_id);
            return array('action' => AuthLDAP::USER_DELETED_LDAP,
                         'id'     => $users_id);
         }
      } else {
         return false;
      }
   }


   /** Converts an array of parameters into a query string to be appended to a URL.
    *
    * @param   $group_dn  dn of the group to import
    * @param   $options array for
    *             - ldapservers_id
    *             - entities_id where group must to be imported
    *             - is_recursive
    *
    * @return  nothing
    */
   static function ldapImportGroup ($group_dn, $options=array()) {

      $config_ldap = new AuthLDAP();
      $res = $config_ldap->getFromDB($options['ldapservers_id']);
      $ldap_users = array ();
      $group_dn = $group_dn;

      // we prevent some delay...
      if (!$res) {
         return false;
      }

      //Connect to the directory
      $ds = AuthLdap::connectToServer($config_ldap->fields['host'], $config_ldap->fields['port'],
                                      $config_ldap->fields['rootdn'],
                                      $config_ldap->fields['rootdn_password'],
                                      $config_ldap->fields['use_tls'],
                                      $config_ldap->fields['deref_option']);
      if ($ds) {
         $group_infos = AuthLdap::getGroupByDn($ds, $config_ldap->fields['basedn'],
                                               stripslashes($group_dn),
                                               $config_ldap->fields["group_condition"]);
         $group = new Group();
         if ($options['type'] == "groups") {
            $group->add(array("name"          => addslashes($group_infos["cn"][0]),
                              "ldap_group_dn" => addslashes($group_infos["dn"]),
                              "entities_id"   => $options['entities_id'],
                              "is_recursive"  => $options['is_recursive']));
         } else {
            $group->add(array("name"         => addslashes($group_infos["cn"][0]),
                              "ldap_field"   => $config_ldap->fields["group_field"],
                              "ldap_value"   => addslashes($group_infos["dn"]),
                              "entities_id"  => $options['entities_id'],
                              "is_recursive" => $options['is_recursive']));
         }
      }
   }


   /**
    * Connect to a LDAP serveur
    *
    * @param $host : LDAP host to connect
    * @param $port : port to use
    * @param $login : login to use
    * @param $password : password to use
    * @param $use_tls : use a tls connection ?
    * @param $deref_options Deref options used
    *
    * @return link to the LDAP server : false if connection failed
   **/
   static function connectToServer($host, $port, $login = "", $password = "", $use_tls = false,
                                   $deref_options) {

      $ds = @ldap_connect($host, intval($port));
      if ($ds) {
         @ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
         @ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
         @ldap_set_option($ds, LDAP_OPT_DEREF, $deref_options);
         if ($use_tls) {
            if (!@ldap_start_tls($ds)) {
               return false;
            }
         }
         // Auth bind
         if ($login != '') {
            $b = @ldap_bind($ds, $login, $password);
         } else { // Anonymous bind
            $b = @ldap_bind($ds);
         }
         if ($b) {
            return $ds;
         }
      }
      return false;
   }


   /**
    * Try to connect to a ldap server
    *
    * @param $ldap_method : ldap_method array to use
    * @param $login User Login
    * @param $password User Password

    * @return link to the LDAP server : false if connection failed
   **/
   static function tryToConnectToServer($ldap_method, $login, $password){

      $ds = AuthLdap::connectToServer($ldap_method['host'], $ldap_method['port'],
                                      $ldap_method['rootdn'], $ldap_method['rootdn_password'],
                                      $ldap_method['use_tls'], $ldap_method['deref_option']);

      // Test with login and password of the user if exists
      if (!$ds && !empty($login)) {
         $ds = AuthLdap::connectToServer($ldap_method['host'], $ldap_method['port'], $login,
                                         $password, $ldap_method['use_tls'],
                                         $ldap_method['deref_option']);
      }

      //If connection is not successfull on this directory, try replicates (if replicates exists)
      if (!$ds && $ldap_method['id']>0) {
         foreach (getAllReplicateForAMaster($ldap_method['id']) as $replicate) {
            $ds = AuthLdap::connectToServer($replicate["host"], $replicate["port"],
                                            $ldap_method['rootdn'], $ldap_method['rootdn_password'],
                                            $ldap_method['use_tls'], $ldap_method['deref_option']);

            // Test with login and password of the user
            if (!$ds && !empty($login)) {
               $ds = AuthLdap::connectToServer($replicate["host"], $replicate["port"], $login,
                                               $password, $ldap_method['use_tls'],
                                               $ldap_method['deref_option']);
            }
            if ($ds) {
               return $ds;
            }
         }
      }
      return $ds;
   }


   static function getLdapServers () {
      return getAllDatasFromTable('glpi_authldaps');
   }


   /**
    * Is the LDAP authentication used ?
    *
    * @return boolean
   **/
   static function useAuthLdap() {
      global $DB;

      //Get all the ldap directories
      $sql = "SELECT COUNT(*)
              FROM `glpi_authldaps`";
      $result = $DB->query($sql);

      if ($DB->result($result,0,0) > 0) {
         return true;
      }
      return false;
   }


   /**
    * Import a user from ldap
    * Check all the directories. When the user is found, then import it
    * @param $options array containing condition :
    *          array ('name'=>'glpi') or array ('email' => 'test at test.com')
   **/
   static function importUserFromServers($options=array()) {
      global $LANG;

      $auth = new Auth;
      $params = array();
      if (isset($options['name'])) {
         $params['value']  = $options['name'];
         $params['method'] = self::IDENTIFIER_LOGIN;
      }
      if (isset($options['email'])) {
         $params['value']  = $options['email'];
         $params['method'] = self::IDENTIFIER_EMAIL;
      }

      $auth->user_present = $auth->userExists($options);

      //If the user does not exists
      if ($auth->user_present == 0) {
         $auth->getAuthMethods();
         $ldap_methods = $auth->authtypes["ldap"];
         $userid = -1;

         foreach ($ldap_methods as $ldap_method) {
            $result = AuthLdap::ldapImportUserByServerId($params, 0, $ldap_method["id"], true);
            if ($result != false) {
               return $result;
            }
         }
         addMessageAfterRedirect($LANG['login'][15], false, ERROR);
      } else {
         addMessageAfterRedirect($LANG['setup'][606], false, ERROR);
      }
      return false;
   }


   /**
    * Authentify a user by checking a specific directory
    * @param $auth : identification object
    * @param $login : user login
    * @param $password : user password
    * @param $ldap_method : ldap_method array to use
    *
    * @return identification object
   **/
   static function ldapAuth($auth, $login, $password, $ldap_method) {

      $user_dn = $auth->connection_ldap($ldap_method, $login, $password);
      if ($user_dn) {
         $auth->auth_succeded = true;
         $auth->extauth       = 1;
         $auth->user_present  = $auth->user->getFromDBbyName(addslashes($login));
         $auth->user->getFromLDAP($auth->ldap_connection,$ldap_method, $user_dn, $login);
         $auth->user->fields["authtype"] = Auth::LDAP;
         $auth->user->fields["auths_id"] = $ldap_method["id"];
      }
      return $auth;
   }


   /**
    * Try to authentify a user by checking all the directories
    * @param $auth : identification object
    * @param $login : user login
    * @param $password : user password
    * @param $auths_id : auths_id already used for the user
    *
    * @return identification object
   **/
   static function tryLdapAuth($auth, $login, $password, $auths_id = 0) {

      //If no specific source is given, test all ldap directories
      if ($auths_id <= 0) {
         foreach  ($auth->authtypes["ldap"] as $ldap_method) {
            if (!$auth->auth_succeded) {
               $auth = AuthLdap::ldapAuth($auth, $login, $password, $ldap_method);
            } else {
               break;
            }
         }
      //Check if the ldap server indicated as the last good one still exists !
      } else if(array_key_exists($auths_id, $auth->authtypes["ldap"])) {
         //A specific ldap directory is given, test it and only this one !
         $auth = AuthLdap::ldapAuth($auth, $login, $password, $auth->authtypes["ldap"][$auths_id]);
      }
      return $auth;
   }


   /**
    * Get dn for a user
    *
    * @param $ds : LDAP link
    * @param $options array
    *          - basedn : base dn used to search
    *          - login_field : attribute to store login
    *          - search_parameters array of search parameters
    *          - user_params  array of parameters : method (IDENTIFIER_LOGIN or IDENTIFIER_EMAIL) + value
    *          - condition : ldap condition used
    *
    * @return dn of the user, else false
   **/
   static function searchUserDn($ds, $options=array()) {

      $values['basedn']            = '';
      $values['login_field']       = '';
      $values['search_parameters'] = array();
      $values['user_params']       = '';
      $values['condition']         = '';

      foreach  ($options as $key => $value) {
         $values[$key] = $value;
      }

      //By default authentify users by login
      //$authentification_value = '';

      $login_attr = $values['search_parameters']['fields'][AuthLDAP::IDENTIFIER_LOGIN];
      $ldap_parameters = array("dn");
      foreach ($values['search_parameters']['fields'] as $parameter) {
         $ldap_parameters[] = $parameter;
      }

      //$authentification_value = $values['user_params']['value'];
      // Tenter une recherche pour essayer de retrouver le DN
      $filter = "(".$values['login_field']."=".$values['user_params']['value'].")";

      if (!empty ($values['condition'])) {
         $filter = "(& $filter ".$values['condition'].")";
      }

      if ($result = ldap_search($ds, $values['basedn'], $filter, $ldap_parameters)){
         $info = ldap_get_entries($ds, $result);
         if (is_array($info) && $info['count'] == 1) {
            return array('dn'        => $info[0]['dn'],
                         $login_attr => $info[0][$login_attr][0]);
         }
         //Why this code ? When do we need to guess the user's dn ?
         /*
          else { // Si echec, essayer de deviner le DN / Flat LDAP
            if ($values['search_parameters']['method'] == AuthLDAP::IDENTIFIER_LOGIN) {
               $dn = "$login_attr=$authentification_value," . $values['basedn'];
               return array('dn'=>$dn,$login_attr=>$values['user_params']['value']);
            }
            else {
               return false;
            }
         }*/
      }
      return false;
   }


   /**
    * Get infos for groups
    *
    * @param $ds : LDAP link
    * @param $basedn : base dn used to search
    * @param $group_dn : dn of the group
    * @param $condition : ldap condition used
    *
    * @return group infos if found, else false
   **/
   static function getGroupByDn($ds, $basedn, $group_dn, $condition) {

      if($result = @ ldap_read($ds, $group_dn, "objectClass=*", array("cn"))) {
         $info = ldap_get_entries($ds, $result);
         if (is_array($info) && $info['count'] == 1) {
            return $info[0];
         }
      }
      return false;
   }


   static function manageValuesInSession($options=array(), $delete=false) {

      $fields = array('interface',
                      'mode',
                      'criterias',
                      'action',
                      'entities_id',
                      'ldapservers_id',
                      'ldap_filter',
                      'basedn',
                      'action',
                      'operator',
                      'days');

      //If form accessed via popup, do not show expert mode link
      if (isset($options['popup'])) {
         //If coming form the helpdesk form : reset all criterias
         $_SESSION['ldap_import']['popup']          = 1;
         $_SESSION['ldap_import']['no_expert_mode'] = 1;
         $_SESSION['ldap_import']['action']         = 'show';
         $_SESSION['ldap_import']['interface']      = AuthLdap::SIMPLE_INTERFACE;
         $_SESSION['ldap_import']['mode']           = AuthLdap::ACTION_IMPORT;
      }

      if (!$delete) {

         if (isset($options["rand"])) {
            $_SESSION["glpipopup"]["rand"] = $options["rand"];
         }

         if (!isset($_SESSION['ldap_import']['entities_id'])) {
            $options['entities_id'] = $_SESSION['glpiactive_entity'];
         }

         if (isset($options['toprocess'])) {
            $_SESSION['ldap_import']['action'] = 'process';
         }

         if (isset($options['change_directory'])) {
            $options['ldap_filter'] = '';
         }

         if (!isset($_SESSION['ldap_import']['ldapservers_id'])) {
            $_SESSION['ldap_import']['ldapservers_id'] = NOT_AVAILABLE;
         }

         if ((!haveRight('config','w') && !haveRight('entity','w'))
             || (!isset($_SESSION['ldap_import']['interface']) && !isset($options['interface']))) {
            $options['interface'] = AuthLdap::SIMPLE_INTERFACE;
         }

         foreach($fields as $field) {
            if (isset($options[$field])) {
               $_SESSION['ldap_import'][$field] = $options[$field];
            }
         }

         if (!isset($_SESSION['ldap_import']['criterias'])) {
            $_SESSION['ldap_import']['criterias'] = array();
         }

         $authldap = new AuthLdap;
         //Filter computation
         if ($_SESSION['ldap_import']['interface'] == AuthLdap::SIMPLE_INTERFACE) {
            $entitydata = new EntityData;
            if ($entitydata->getFromDB($_SESSION['ldap_import']['entities_id'])
                && $entitydata->getField('ldapservers_id') > 0) {

               $authldap->getFromDB($_SESSION['ldap_import']['ldapservers_id']);
               $_SESSION['ldap_import']['ldapservers_id'] = $entitydata->getField('ldapservers_id');
               $_SESSION['ldap_import']['basedn']         = $entitydata->getField('ldap_dn');

               if ($entitydata->getField('entity_ldapfilter') != NOT_AVAILABLE) {
                  $_SESSION['ldap_import']['entity_filter'] =
                                                         $entitydata->getField('entity_ldapfilter');
               }

            } else {
               $_SESSION['ldap_import']['ldapservers_id'] = AuthLdap::getDefault();

               if ($_SESSION['ldap_import']['ldapservers_id'] > 0) {
                  $authldap->getFromDB($_SESSION['ldap_import']['ldapservers_id']);
                  $_SESSION['ldap_import']['basedn'] = $authldap->getField('basedn');
               }
            }

            if ($_SESSION['ldap_import']['ldapservers_id'] > 0) {
               $_SESSION['ldap_import']['ldap_filter'] = AuthLdap::buildLdapFilter($authldap);
            }

         } else {
            if ($_SESSION['ldap_import']['ldapservers_id'] == NOT_AVAILABLE
                || !$_SESSION['ldap_import']['ldapservers_id']) {

               $_SESSION['ldap_import']['ldapservers_id'] = AuthLdap::getDefault();

               if ($_SESSION['ldap_import']['ldapservers_id'] > 0) {
                  $authldap->getFromDB($_SESSION['ldap_import']['ldapservers_id']);
                  $_SESSION['ldap_import']['basedn'] = $authldap->getField('basedn');
               }
            }
            if (!isset($_SESSION['ldap_import']['ldap_filter'])
                || $_SESSION['ldap_import']['ldap_filter'] == '') {

               $authldap->getFromDB($_SESSION['ldap_import']['ldapservers_id']);
               $_SESSION['ldap_import']['basedn']      = $authldap->getField('basedn');
               $_SESSION['ldap_import']['ldap_filter'] = AuthLdap::buildLdapFilter($authldap);
            }
         }
      //Unset all values in session
      } else {
         unset($_SESSION['ldap_import']);
      }
   }


   static function showUserImportForm(AuthLDAP $authldap) {
      global $DB, $LANG;

      //Get data related to entity (directory and ldap filter)
      $authldap->getFromDB($_SESSION['ldap_import']['ldapservers_id']);
      echo "<div class='center'>";

      echo "<form method='post' action='".$_SERVER['PHP_SELF']."'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='4' class='middle'><div class='relative'>";
      echo "<span>" .($_SESSION['ldap_import']['mode']?$LANG['ldap'][1]:$LANG['ldap'][2]);

      // Expert interface allow user to override configuration.
      // If not coming from the ticket form, then give expert/simple link
      if ((haveRight('config','w') || haveRight('entity','w'))
          && !isset($_SESSION['ldap_import']['no_expert_mode'])) {

         echo "</span>&nbsp;<span class='ldap_right'>".$LANG['common'][65]."&nbsp;: ";
         echo "<a href='".$_SERVER['PHP_SELF']."?action=".$_SESSION['ldap_import']['action'].
                        "&amp;mode=".$_SESSION['ldap_import']['mode']. "&amp;interface=".
                        ($_SESSION['ldap_import']['interface'] == AuthLdap::SIMPLE_INTERFACE
                           ?AuthLdap::EXPERT_INTERFACE:AuthLdap::SIMPLE_INTERFACE)."'>".
                        ($_SESSION['ldap_import']['interface'] == AuthLdap::SIMPLE_INTERFACE
                           ?$LANG['ldap'][39]:$LANG['ldap'][40])."</a>";
      } else {
         $_SESSION['ldap_import']['interface'] = AuthLdap::SIMPLE_INTERFACE;
      }
      echo "</span></div>";
      echo "</th></tr>";

      switch ($_SESSION['ldap_import']['interface']) {
         case AuthLdap::EXPERT_INTERFACE :
            //If more than one directory configured
            //Display dropdown ldap servers
            if ($_SESSION['ldap_import']['ldapservers_id'] !=  NOT_AVAILABLE
                && $_SESSION['ldap_import']['ldapservers_id'] > 0) {

               if (AuthLdap::getNumberOfServers() > 1) {
                  echo "<tr class='tab_bg_2'><td>".$LANG['ldap'][4]."</td><td colspan='3'>";
                  Dropdown::show('AuthLdap',
                                 array('name'   => 'ldapservers_id',
                                       'value'  => $_SESSION['ldap_import']['ldapservers_id']));
                  echo "&nbsp;<input class='submit' type='submit' name='change_directory'
                        value='".$LANG['ldap'][41]."'>";
                  echo "</td></tr>";
               }
               echo "<tr class='tab_bg_2'><td>Basedn</td><td colspan='3'>";
               echo "<input type='text' name='basedn' value='".$_SESSION['ldap_import']['basedn'].
                     "' size='90' ".(!$_SESSION['ldap_import']['basedn']?"disabled":"").">";
               echo "</td></tr>";
               echo "<tr class='tab_bg_2'><td>".$LANG['setup'][263]."</td><td colspan='3'>";
               echo "<input type='text' name='ldap_filter' value='".
                        $_SESSION['ldap_import']['ldap_filter']."' size='90' ".
                        (!$_SESSION['ldap_import']['ldapservers_id']?"disabled":"").">";
               echo "</td></tr>";
            }
            break;

         //case AuthLdap::SIMPLE_INTERFACE :
         default :
            //If multi-entity mode and more than one entity visible
            //else no need to select entity
            if (isMultiEntitiesMode() && count($_SESSION['glpiactiveentities']) > 1) {
               echo "<tr class='tab_bg_2'><td>".$LANG['entity'][10]."</td><td colspan='3'>";
               Dropdown::show('Entity',
                              array('value'        => $_SESSION['ldap_import']['entities_id'],
                                    'entity'       => $_SESSION['glpiactiveentities'],
                                    'auto_submit'  => 1));
               echo "</td></tr>";
            } else {
               //Only one entity is active, store it
               echo "<input type='hidden' name='entities_id' value='".
                     $_SESSION['glpiactive_entity']."'>";
            }

            if (isset($_SESSION['ldap_import']['days']) && $_SESSION['ldap_import']['days']) {
               $enabled = 1;
            } else {
               $enabled = 0;
            }
            Dropdown::showAdvanceDateRestrictionSwitch($enabled);

            echo "<table class='tab_cadre_fixe'>";

            if ($_SESSION['ldap_import']['ldapservers_id'] !=  NOT_AVAILABLE
                && $_SESSION['ldap_import']['ldapservers_id'] > 0) {

               $field_counter = 0;
               $fields = array('login_field'     => $LANG['login'][6],
                               'email_field'     => $LANG['setup'][14],
                               'realname_field'  => $LANG['common'][48],
                               'firstname_field' => $LANG['common'][43],
                               'phone_field'     => $LANG['help'][35],
                               'phone2_field'    => $LANG['help'][35] . " 2",
                               'mobile_field'    => $LANG['common'][42],
                               'title_field'     => $LANG['users'][1],
                               'category_field'  => $LANG['users'][2]);
               $available_fields = array();
               foreach ($fields as $field => $label) {
                  if (isset($authldap->fields[$field]) && $authldap->fields[$field] != '') {
                     $available_fields[$field] = $label;
                  }
               }
               echo "<tr><th colspan='4'>" . $LANG['ldap'][38] . "</th></tr>";
               foreach ($available_fields as $field => $label) {
                  if ($field_counter == 0) {
                     echo "<tr class='tab_bg_1'>";
                  }
                  echo "<td>$label</td><td>";
                  $field_counter++;
                  echo "<input type='text' name='criterias[$field]' value='".
                        (isset($_SESSION['ldap_import']['criterias'][$field])?
                               $_SESSION['ldap_import']['criterias'][$field]:'')."'>";
                  echo "</td>";
                  if ($field_counter == 2) {
                     echo "</tr>";
                     $field_counter = 0;
                  }
               }
               if ($field_counter > 0) {
                  while ($field_counter < 2) {
                     echo "<td colspan='2'></td>";
                     $field_counter++;
                  }
                  $field_counter = 0;
                  echo "</tr>";
                }
            }
            break;
      }

      if ($_SESSION['ldap_import']['ldapservers_id'] !=  NOT_AVAILABLE
          && $_SESSION['ldap_import']['ldapservers_id'] > 0) {

         if ($_SESSION['ldap_import']['ldapservers_id']) {
            echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
            echo "<input class='submit' type='submit' name='search' value='".$LANG['buttons'][0]."'>";
            echo "</td></tr>";
         } else {
            echo "<tr class='tab_bg_2'><td colspan='4' class='center'>".$LANG['ldap'][42]."</td></tr>";
         }

      } else {
         echo "<tr class='tab_bg_2'><td colspan='4' class='center'>".$LANG['ldap'][36]."</td></tr>";
      }
      echo "</table></form></div>";
   }


   static function getNumberOfServers() {
      global $DB;

      $query = "SELECT COUNT(*) AS cpt
                FROM `glpi_authldaps`";
      $result = $DB->query($query);

      return $DB->result($result,0,'cpt');
   }


   static function getFirstLdapServer() {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_authldaps`";
      $result = $DB->query($query);

      return $DB->result($result,0,'id');
   }


   static private function buildLdapFilter(AuthLdap $authldap) {
      //Build search filter

      $counter = 0;
      $filter  = '';
      if (!empty($_SESSION['ldap_import']['criterias'])
          && $_SESSION['ldap_import']['interface'] == AuthLdap::SIMPLE_INTERFACE) {

         foreach ($_SESSION['ldap_import']['criterias'] as $criteria => $value) {
            if ($value!='') {
               $begin = 0;
               $end   = 0;
               if (($length = strlen($value)) >0) {
                  if (($value[0] == '^')) {
                     $begin = 1;
                  }
                  if ($value[$length-1] == '$'){
                     $end = 1;
                  }
               }
               if ($begin || $end) {
                  // no utf8_substr, to be consistent with strlen result
                  $value = substr($value, $begin, $length-$end-$begin);
               }
               $counter++;
               $filter .= '('.$authldap->fields[$criteria].'='.($begin?'':'*').$value.($end?'':'*').')';
             }
          }

      } else {
         $filter = "(".$authldap->getField("login_field")."=*)";
      }

      //If days restriction
      $operator = (isset($_SESSION['ldap_import']['operator'])?$_SESSION['ldap_import']['operator']:'<');
      $days     = (isset($_SESSION['ldap_import']['days'])?$_SESSION['ldap_import']['days']:0);
      $filter  .= self::addTimestampRestrictions($operator, $days);

      $ldap_condition = $authldap->getField('condition');
      //Add entity filter and filter filled in directory's configuration form
      return  "(&".(isset($_SESSION['ldap_import']['entity_filter'])?
                           $_SESSION['ldap_import']['entity_filter']:'')." $filter $ldap_condition)";
   }


   static function addTimestampRestrictions($operator, $days) {

      //If days restriction
      if ($days) {
         $operator = $operator.'=';
         $stampvalue = self::date2ldapTimeStamp($days);
         return "(modifyTimestamp".$operator.$stampvalue.")";
      }
      return "";
   }


   static function searchUser(AuthLDAP $authldap) {
      global $LANG;

      if (AuthLdap::connectToServer($authldap->getField('host'), $authldap->getField('port'),
                                    $authldap->getField('rootdn'),
                                    $authldap->getField('rootdn_password'),
                                    $authldap->getField('use_tls'),
                                    $authldap->getField('deref_option'))) {
         AuthLdap::showLdapUsers();

      } else {
         echo "<div class='center b'>".$LANG['ldap'][6]."<br>";
      }
   }


   static function getDefault() {
      global $DB;

      foreach ($DB->request('glpi_authldaps', array('is_default' => 1)) as $data) {
         return $data['id'];
      }
      return 0;
   }


   function post_updateItem($history=1) {
      global $DB;

      if (in_array('is_default',$this->updates) && $this->input["is_default"]==1) {
         $query = "UPDATE `". $this->getTable()."`
                   SET `is_default` = '0'
                   WHERE `id` <> '".$this->input['id']."'";
         $DB->query($query);
      }
   }


   function prepareInputForAdd($input) {

      //If it's the first ldap directory then set it as the default directory
      if (!AuthLdap::getNumberOfServers()) {
         $input['is_default'] = 1;
      }
      return $input;
   }


   static function dropdownUserDeletedActions($value=0) {
      global $LANG;

      $options[0] = $LANG['buttons'][49];
      $options[1] = $LANG['ldap'][47];
      $options[2] = $LANG['ldap'][46];
      $options[3] = $LANG['buttons'][42];
      asort($options);
      return Dropdown::showFromArray('user_deleted_ldap', $options, array('value' => $value));
   }


   /**
    * Return all the ldap servers where email field is configured
    *
    * @return array of LDAP server's ID
    */
   static function getServersWithImportByEmailActive() {
      global $DB;

      $ldaps = array();
      $query = "SELECT `id`
                FROM `glpi_authldaps`
                WHERE `email_field` <> ''";
      foreach ($DB->request($query) as $data) {
         $ldaps[] = $data['id'];
      }
      return $ldaps;
   }


   static function showDateRestrictionForm($options=array()) {
      global $LANG;

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'>";

      $enabled = (isset($options['enabled'])?$options['enabled']:false);
      if (!$enabled) {
         echo "<td colspan='4' class='center'>";
         echo "<a href='#' onClick='activateRestriction()'>".$LANG['ldap'][54]."</a>";
         echo "<input type='hidden' name='condition' value='<'>";
         echo "<input type='hidden' name='days' value='0'>";
         echo "</td></tr>";
      }
      if ($enabled) {
         echo "<td>";
         if ($_SESSION['ldap_import']['mode'] == self::ACTION_IMPORT) {
            echo $LANG['ldap'][49];
         } else {
            echo $LANG['ldap'][50];
         }
         echo "</td><td colspan='3'>";
         $infsup = array ('<' => $LANG['search'][22], '>' => $LANG['search'][21]);
         $options = array('value'=>(isset($_SESSION['ldap_import']['operator'])
                                    ?$_SESSION['ldap_import']['operator']:'<'));
         Dropdown::showFromArray('operator', $infsup, $options);
         echo "&nbsp;";
         $default = (isset($_SESSION['ldap_import']['days'])?$_SESSION['ldap_import']['days']:0);

         $values = array();
         for ($i=1 ; $i < 16 ; $i++) {
            $values[$i] = $i.' '.$LANG['stats'][31];
         }
         for ($i=3 ; $i < 9 ; $i++) {
            $values[$i*7] = $i.' '.$LANG['ldap'][56];
         }
         for ($i=3 ; $i < 13 ; $i++) {
            $values[$i*28] = $i.' '.$LANG['planning'][14];
         }

         Dropdown::showFromArray('days', $values, array('value' => $default));
         echo "&nbsp;</td></tr>";
         echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
         echo "<a href='#' onClick='deactivateRestriction()'>".$LANG['ldap'][55]."</a>";
         echo "</td></tr>";
      }
      echo "</table>";
   }
}

?>