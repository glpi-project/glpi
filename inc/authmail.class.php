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
 *  Class used to manage Auth mail config
**/
class AuthMail extends CommonDBTM {

   static function getTypeName() {
      global $LANG;

      return $LANG['login'][3];
   }

   function canCreate() {
      return haveRight('config', 'w');
   }

   function canView() {
      return haveRight('config', 'r');
   }

   function prepareInputForUpdate($input) {
      if (isset ($input['mail_server']) && !empty ($input['mail_server'])) {
         $input["connect_string"] = constructMailServerConfig($input);
      }
      return $input;
   }

   function prepareInputForAdd($input) {
      if (isset ($input['mail_server']) && !empty ($input['mail_server'])) {
         $input["connect_string"] = constructMailServerConfig($input);
      }
      return $input;
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['login'][2];

      $tab[1]['table']         = 'glpi_authmails';
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = 'AuthMail';

      $tab[2]['table']        = 'glpi_authmails';
      $tab[2]['field']        = 'id';
      $tab[2]['linkfield']    = '';
      $tab[2]['name']         = $LANG['common'][2];

      $tab[3]['table']         = 'glpi_authmails';
      $tab[3]['field']         = 'host';
      $tab[3]['linkfield']     = 'host';
      $tab[3]['name']          = $LANG['common'][52];

      $tab[4]['table']         = 'glpi_authmails';
      $tab[4]['field']         = 'connect_string';
      $tab[4]['linkfield']     = '';
      $tab[4]['name']          = $LANG['setup'][170];

      $tab[19]['table']       = 'glpi_authmails';
      $tab[19]['field']       = 'date_mod';
      $tab[19]['linkfield']   = '';
      $tab[19]['name']        = $LANG['common'][26];
      $tab[19]['datatype']    = 'datetime';

      $tab[16]['table']     = 'glpi_authmails';
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';


      return $tab;
   }

   /**
    * Print the auth mail form
    *
    *@param $target form target
    *@param $ID Integer : ID of the item
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
      } else {
         if ($this->getFromDB($ID)) {
            $spotted = true;
         }
      }

      if (canUseImapPop()) {
         echo "<form action='".$options['target']."' method='post'>";
         if (!empty ($ID)) {
            echo "<input type='hidden' name='id' value='$ID'>";
         }
         echo "<div class='center'>";
         echo "<table class='tab_cadre'>";
         echo "<tr><th>" . $LANG['login'][3] . "</th><th>";
         echo ($ID>0?$LANG['common'][26]."&nbsp;: ".convDateTime($this->fields["date_mod"]):'&nbsp;');
         echo "</th></tr>";
         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][16] . "&nbsp;:</td>";
         echo "<td><input size='30' type='text' name='name' value='" . $this->fields["name"] . "'>";
         echo "</td></tr>";
         echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][164] . "&nbsp;:</td>";
         echo "<td><input size='30' type='text' name='host' value='" . $this->fields["host"] . "'>";
         echo "</td></tr>";

         showMailServerConfig($this->fields["connect_string"]);

         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][25] . "&nbsp;:</td>";
         echo "<td>";
         echo "<textarea cols='40' name='comment'>".$this->fields["comment"]."</textarea>";
         echo "</td></tr>";

         if (empty ($ID)) {
            echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
            echo "<input type='submit' name='add' class='submit'
                   value=\"" . $LANG['buttons'][2] . "\" ></td></tr></table>";
         } else {
            echo "<tr class='tab_bg_2'><td class='center' colspan=2>";
            echo "<input type='submit' name='update' class='submit'
                   value=\"" . $LANG['buttons'][7] . "\" >";
            echo "&nbsp<input type='submit' name='delete' class='submit'
                        value=\"" . $LANG['buttons'][6] . "\" ></td></tr></table>";

            echo "<br><table class='tab_cadre'>";
            echo "<tr><th colspan='2'>" . $LANG['login'][21] . "</th></tr>";
            echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['login'][6] . "</td>";
            echo "<td><input size='30' type='text' name='imap_login' value=''></td></tr>";
            echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['login'][7] . "</td>";
            echo "<td><input size='30' type='password' name='imap_password' value=''></td>";
            echo "</tr><tr class='tab_bg_2'><td class='center' colspan=2>";
            echo "<input type='submit' name='test' class='submit'
                   value=\"" . $LANG['buttons'][2] . "\" ></td></tr>";
            echo "</table>&nbsp;";
         }
         echo "</div>";
      } else {
         echo "<input type='hidden' name='IMAP_Test' value='1'>";
         echo "<div class='center'>&nbsp;<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . $LANG['setup'][162] . "</th></tr>";
         echo "<tr class='tab_bg_2'><td class='center'>";
         echo "<p class='red'>" . $LANG['setup'][165] . "</p>";
         echo "<p>" . $LANG['setup'][166] . "</p></td></tr></table></div>";
      }
      echo "</form>";
   }
}

?>