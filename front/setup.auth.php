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

include ('../inc/includes.php');

Session::checkRight("config", READ);

Html::header(__('External authentication sources'), $_SERVER['PHP_SELF'], "config", "auth", -1);

echo "<table class='tab_cadre'>";
echo "<tr><th>&nbsp;" . __('External authentications') . "</th></tr>";
if (Session::haveRight("config", UPDATE)) {
   echo "<tr class='tab_bg_1'><td class='center b'>".
        "<a href='auth.settings.php'>" .__('Setup')."</a></td></tr>";
}
echo "<tr class='tab_bg_1'><td class='center b'>";
if (Toolbox::canUseLdap()) {
   echo "<a href='authldap.php'>". _n('LDAP directory', 'LDAP directories', 2)."</a>";
} else {
   echo "<p class='red'>".__("The LDAP extension of your PHP parser isn't installed") ."</p>";
   echo "<p>".__('Impossible to use LDAP as external source of connection').'</p>';
}
echo "</td></tr>";
echo "<tr class='tab_bg_1'><td class='center b'>";
if (Toolbox::canUseImapPop()) {
   echo "<a href='authmail.php'>". _n('Mail server', 'Mail servers', 2)."</a>";
} else {
   echo "<p class='red'>".__('Your PHP parser was compiled without the IMAP functions') ."</p>";
   echo "<p>".__('Impossible to use email server as external source of connection').'</p>';
}
echo "</td> </tr>";
echo "<tr class='tab_bg_1'><td class='center'>".
     "<a href='auth.others.php'>" . __('Others authentication methods') ."</a></td></tr>";
echo "</table>";

Html::footer();
?>