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


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("config", "r");

Html::header($LANG['title'][14], $_SERVER['PHP_SELF'],"config","extauth",-1);

echo "<table class='tab_cadre'>";
echo "<tr><th>&nbsp;" . $LANG['setup'][67] . "&nbsp;</th></tr>";
if (Session::haveRight("config","w")) {
   echo "<tr class='tab_bg_1'><td class='center b'><a href='auth.settings.php'>";
}
echo $LANG['common'][12]. ' ' . $LANG['login'][10]."</a></td></tr>";
echo "<tr class='tab_bg_1'><td class='center b'>";
if (Toolbox::canUseLdap()) {
   echo "<a href='authldap.php'>". $LANG['Menu'][9] ."</a>";
} else {
   echo "<p class='red'>".$LANG['setup'][157] ."</p><p>".$LANG['setup'][158].'</p>';
}
echo "</td></tr>";
echo "<tr class='tab_bg_1'><td class='center b'>";
if (Toolbox::canUseImapPop()) {
   echo "<a href='authmail.php'>" .$LANG['Menu'][10] ."</a>";
} else {
   echo "<p class='red'>".$LANG['setup'][165] ."</p><p>".$LANG['setup'][166].'</p>';
}
echo "</td> </tr>";
echo "<tr class='tab_bg_1'><td class='center'><a href='auth.others.php'>" . $LANG['login'][17] .
      "</a></td></tr>";
echo "</table>";

Html::footer();
?>