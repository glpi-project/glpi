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

   // From CommonDBTM
   public $table = 'glpi_authmails';
   public $type = 'AuthMail';

   static function getTypeName() {
      global $LANG;

      return $LANG['login'][3];
   }

   static function canCreate() {
      return haveRight('config', 'w');
   }

   static function canView() {
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

   /**
    * Print the auth mail form
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
      } else {
         if ($this->getFromDB($ID)) {
            $spotted = true;
         }
      }

      if (canUseImapPop()) {
         echo "<form action=\"$target\" method=\"post\">";
         if (!empty ($ID)) {
            echo "<input type='hidden' name='id' value='" . $ID . "'>";
         }
         echo "<div class='center'>";
         echo "<table class='tab_cadre'>";
         echo "<tr><th colspan='2'>" . $LANG['login'][3] . "</th></tr>";
         echo "<tr class='tab_bg_1'><td>" . $LANG['common'][16] . "&nbsp;:</td>";
         echo "<td><input size='30' type='text' name='name' value='" . $this->fields["name"] . "'>";
         echo "</td></tr>";
         echo "<tr class='tab_bg_1'><td>" . $LANG['setup'][164] . "&nbsp;:</td>";
         echo "<td><input size='30' type='text' name='host' value='" . $this->fields["host"] . "'>";
         echo "</td></tr>";

         showMailServerConfig($this->fields["connect_string"]);

         if (empty ($ID)) {
            echo "<tr class='tab_bg_2'><td class='center' colspan=4>";
            echo "<input type='submit' name='add_mail' class='submit'
                   value=\"" . $LANG['buttons'][2] . "\" ></td></tr></table>";
         } else {
            echo "<tr class='tab_bg_2'><td class='center' colspan=2>";
            echo "<input type='submit' name='update_mail' class='submit'
                   value=\"" . $LANG['buttons'][7] . "\" >";
            echo "&nbsp<input type='submit' name='delete_mail' class='submit'
                        value=\"" . $LANG['buttons'][6] . "\" ></td></tr></table>";

            echo "<br><table class='tab_cadre'>";
            echo "<tr><th colspan='2'>" . $LANG['login'][21] . "</th></tr>";
            echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['login'][6] . "</td>";
            echo "<td><input size='30' type='text' name='imap_login' value=''></td></tr>";
            echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['login'][7] . "</td>";
            echo "<td><input size='30' type='password' name='imap_password' value=''></td>";
            echo "</tr><tr class='tab_bg_2'><td class='center' colspan=2>";
            echo "<input type='submit' name='test_mail' class='submit'
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