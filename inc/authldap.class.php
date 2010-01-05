<?php
/*
 * @version $Id$
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

/**
 *  Class used to manage Auth LDAP config
**/
class AuthLDAP extends CommonDBTM {

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
      $this->fields['port']='389';
      $this->fields['condition']='';
      $this->fields['login_field']='uid';
      $this->fields['use_tls']=0;
      $this->fields['group_field']='';
      $this->fields['group_condition']='';
      $this->fields['group_search_type']=0;
      $this->fields['group_member_field']='';
      $this->fields['email_field']='mail';
      $this->fields['realname_field']='cn';
      $this->fields['firstname_field']='givenname';
      $this->fields['phone_field']='telephonenumber';
      $this->fields['phone2_field']='';
      $this->fields['mobile_field']='';
      $this->fields['comment_field']='';
      $this->fields['title_field']='';
      $this->fields['use_dn']=0;
   }

   /**
    * Preconfig datas for standard system
    * @param $type type of standard system : AD
    *@return nothing
    **/
   function preconfig($type) {
      switch($type) {
         case 'AD' :
            $this->fields['port']="389";
            $this->fields['condition']=
               '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
            $this->fields['login_field']='samaccountname';
            $this->fields['use_tls']=0;
            $this->fields['group_field']='memberof';
            $this->fields['group_condition']=
               '(&(objectClass=user)(objectCategory=person)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))';
            $this->fields['group_search_type']=0;
            $this->fields['group_member_field']='';
            $this->fields['email_field']='mail';
            $this->fields['realname_field']='sn';
            $this->fields['firstname_field']='givenname';
            $this->fields['phone_field']='telephonenumber';
            $this->fields['phone2_field']='othertelephone';
            $this->fields['mobile_field']='mobile';
            $this->fields['comment_field']='info';
            $this->fields['title_field']='title';
            //$this->fields['language_field']='preferredlanguage';
            $this->fields['use_dn']=1;
            break;

         default:
            $this->post_getEmpty();
            break;
      }
   }
   function prepareInputForUpdate($input) {
      if (isset($input["rootdn_password"]) && empty($input["rootdn_password"])) {
         unset($input["rootdn_password"]);
      }
      return $input;
   }

   /**
    * Print the auth ldap form
    *
    *@param $target form target
    *@param $ID Integer : ID of the item
    *
    *@return Nothing (display)
    **/
   function showForm($target, $ID) {
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
         $this->showTabs($ID);
         $this->showFormHeader($target,$ID,'',2);
         if (empty($ID)) {
            echo "<tr class='tab_bg_2'><td>".$LANG['ldap'][16]."&nbsp;:</td> ";
            echo "<td colspan='3'>";
            echo "<a href='$target?preconfig=AD'>".$LANG['ldap'][17]."</a>";
            echo "&nbsp;&nbsp;/&nbsp;&nbsp;";
            echo "<a href='$target?preconfig=default'>".$LANG['common'][44];
            echo "</a></td></tr>";
         }
         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][16] . "&nbsp;:</td>";
         echo "<td colspan='3'><input type='text' name='name' value='". $this->fields["name"] ."'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][52] . "&nbsp;:</td>";
         echo "<td><input type='text' name='host' value='" . $this->fields["host"] . "'></td>";
         echo "<td>" . $LANG['setup'][172] . "&nbsp;:</td>";
         echo "<td><input id='port' type='text' name='port' value='" . $this->fields["port"] . "'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][154] . "&nbsp;:</td>";
         echo "<td><input type='text' name='basedn' value='" . $this->fields["basedn"] . "'>";
         echo "</td>";
         echo "<td>" . $LANG['setup'][155] . "&nbsp;:</td>";
         echo "<td><input type='text' name='rootdn' value='" . $this->fields["rootdn"] . "'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][156] . "&nbsp;:</td>";
         echo "<td><input type='password' name='rootdn_password' value=''></td>";
         echo "<td>" . $LANG['setup'][228] . "&nbsp;:</td>";
         echo "<td><input type='text' name='login_field' value='".$this->fields["login_field"]."'>";
         echo "</td></tr>";

         echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][159] . "&nbsp;:</td>";
         echo "<td colspan='3'><input type='text' name='condition' value='".
                                 $this->fields["condition"]."' size='100'></td></tr>";

         //Fill fields when using preconfiguration models
         if (!$ID) {
            $hidden_fields = array ('port', 'condition' , 'login_field', 'use_tls', 'group_field',
                                    'group_condition', 'group_search_type', 'group_member_field',
                                    'email_field', 'realname_field', 'firstname_field',
                                    'phone_field', 'phone2_field', 'mobile_field', 'comment_field',
                                    'title_field', 'use_dn');

            foreach ($hidden_fields as $hidden_field) {
               echo "<input type='hidden' name='$hidden_field' value='".
                     $this->fields[$hidden_field]."'>";
            }
         }

         $this->showFormButtons($ID,'',2);

         echo "<div id='tabcontent'></div>";
         echo "<script type='text/javascript'>loadDefaultTab();</script>";
      }
   }

   function showFormAdvancedConfig($ID, $target) {
      global $LANG, $CFG_GLPI, $DB;

      echo "<form method='post' action='$target'>";
      echo "<div class='center'><table class='tab_cadre_fixe'>";

      echo "<tr class='tab_bg_2'><th colspan='4'>";
      echo "<input type='hidden' name='id' value='$ID'>";
      echo $LANG['entity'][14] . "</th></tr>";

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
         echo "<input type='hidden' name='use_tls' value='0'>";
         echo $LANG['setup'][181];
      }
      echo "</td>";
      echo "<td>" . $LANG['setup'][186] . "&nbsp;:</td><td>";
      Dropdown::showGMT("time_offset",$this->fields["time_offset"]);
      echo"</td></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['ldap'][30] . "&nbsp;:</td><td colspan='3'>";
      $alias_options[LDAP_DEREF_NEVER] = $LANG['ldap'][31];
      $alias_options[LDAP_DEREF_ALWAYS] = $LANG['ldap'][32];
      $alias_options[LDAP_DEREF_SEARCHING] = $LANG['ldap'][33];
      $alias_options[LDAP_DEREF_FINDING] = $LANG['ldap'][34];
      Dropdown::showFromArray("deref_option",$alias_options,
                     array('value' => $this->fields["deref_option"]));
      echo"</td></tr>";
      echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
      echo "<input type='submit' name='update' class='submit' value='".
                $LANG['buttons'][2]."'></td>";
      echo "</td></tr>";
      echo "</table></form></div>";
   }

   function showFormReplicatesConfig($ID, $target) {
      global $LANG, $CFG_GLPI, $DB;

      AuthLdapReplicate::addNewReplicateForm($target, $ID);

      $sql = "SELECT *
              FROM `glpi_authldapreplicates`
              WHERE `authldaps_id` = '".$ID."'
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
            echo "<input type='checkbox' name='item[" . $ldap_replicate["id"] . "]'
                   value='1' $sel>";
            echo "</td>";
            echo "<td class='center'>" . $ldap_replicate["name"] . "</td>";
            echo "<td class='center'>".$ldap_replicate["host"]." : ".$ldap_replicate["port"] . "</td>";
            echo "<td class='center'>";
            echo "<input type='submit' name='test_ldap_replicate[".$ldap_replicate["id"]."]'
                  class='submit' value=\"" . $LANG['buttons'][50] . "\" ></td>";
            echo"</tr>";
         }
         echo "</table>";

         openArrowMassive("ldap_replicates_form", true);
         closeArrowMassive('delete_replicate', $LANG['buttons'][6]);

         echo "</div></form>";
      }
   }

   function showFormGroupsConfig($ID, $target) {
      global $LANG,$CFG_GLPI;

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
      echo "<input type='submit' name='update' class='submit' value='".
                $LANG['buttons'][2]."'></td>";
      echo "</td></tr>";
      echo "</table></form></div>";
   }

   function showFormTestLDAP ($ID, $target) {
      global $LANG,$CFG_GLPI;

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
      echo "<input type='submit' name='test_ldap' class='submit' value='".
            $LANG['buttons'][2]."'></td></tr>";
      echo "</table></div>";
   }

   function showFormUserConfig($ID,$target) {
      global $LANG,$CFG_GLPI;

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
      echo "<td><input type='text' name='comment_field' value='".
                 $this->fields["comment_field"]."'></td>";
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
                 $this->fields["language_field"]. "'></td></tr>";
      echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
      echo "<input type='submit' name='update' class='submit' value='".
                $LANG['buttons'][2]."'></td>";
      echo "</td></tr>";
      echo "</table></form></div>";
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong = array();
      $ong[1] = $LANG['title'][26];

      if ($ID>0) {
            $ong[2] = $LANG['Menu'][14];
            $ong[3] = $LANG['Menu'][36];
            $ong[4] = $LANG['entity'][14];
            $ong[5] = $LANG['ldap'][22];
      }
      return $ong;
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['login'][2];

      $tab[1]['table']         = 'glpi_authldaps';
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = 'AuthLDAP';

      $tab[2]['table']        = 'glpi_authldaps';
      $tab[2]['field']        = 'id';
      $tab[2]['linkfield']    = '';
      $tab[2]['name']         = $LANG['common'][2];

      $tab[3]['table']         = 'glpi_authldaps';
      $tab[3]['field']         = 'host';
      $tab[3]['linkfield']     = 'host';
      $tab[3]['name']          = $LANG['common'][52];

      $tab[4]['table']         = 'glpi_authldaps';
      $tab[4]['field']         = 'port';
      $tab[4]['linkfield']     = 'port';
      $tab[4]['name']          = $LANG['setup'][175];

      $tab[5]['table']         = 'glpi_authldaps';
      $tab[5]['field']         = 'basedn';
      $tab[5]['linkfield']     = 'basedn';
      $tab[5]['name']          = $LANG['setup'][154];

      $tab[6]['table']         = 'glpi_authldaps';
      $tab[6]['field']         = 'condition';
      $tab[6]['linkfield']     = 'condition';
      $tab[6]['name']          = $LANG['setup'][159];

      return $tab;
   }

   function showSystemInformations($width) {
      global $LANG;

      $ldap_servers = getLdapServers ();

      if (!empty($ldap_servers)) {
         echo "\n</pre></td><tr class='tab_bg_2'><th>" . $LANG['login'][2] . "</th></tr>\n";
         echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";
         foreach ($ldap_servers as $ID => $value) {
            $fields = array($LANG['common'][52]=>'host',
                            $LANG['setup'][172]=>'port',
                            $LANG['setup'][154]=>'basedn',
                            $LANG['setup'][159]=>'condition',
                            $LANG['setup'][155]=>'rootdn',
                            $LANG['setup'][180]=>'use_tls');
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
}


?>