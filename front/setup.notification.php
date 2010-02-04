<?php
/*
 * @version $Id: setup.auth.php 9584 2009-12-08 20:36:44Z moyo $
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


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("notification","r");

commonHeader($LANG['title'][15], $_SERVER['PHP_SELF'],"config","mailing",-1);

echo "<table class='tab_cadre'>";
echo "<tr><th>&nbsp;" . $LANG['setup'][201] . " ".$LANG['setup'][704]."&nbsp;</th></tr>";

if (haveRight("config","r")) {
   echo "<tr class='tab_bg_1'><td class='center'><a href='notificationmailsetting.php'>" .
         $LANG['setup'][201]. ' '.$LANG['mailing'][118] .
         "</a></td></tr>";
   echo "<tr class='tab_bg_1'><td class='center'><a href='notificationtemplate.php'>" .
         $LANG['mailing'][113] ."</a></td> </tr>";
}
if (haveRight("notification","r")) {
   echo "<tr class='tab_bg_1'><td class='center'><a href='notification.php'>" . $LANG['setup'][704] .
         "</a></td></tr>";
}
echo "</table>";

commonFooter();

?>
