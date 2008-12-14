<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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

$NEEDED_ITEMS = array (
	"setup",
	"auth",
	"ldap",
	"user"
);

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("config","r");

commonHeader($LANG["title"][14], $_SERVER['PHP_SELF'],"config","extauth",-1);

echo "<div align='center'><table class='tab_cadre' cellpadding='5'>";
echo "<tr><th>" . $LANG["setup"][67] . "</th></tr>";
echo "<tr class='tab_bg_1'><td  align='center'><a href=\"auth.ldap.php\"><strong>" . $LANG["login"][2] ."</strong></a></td></tr>";
echo "<tr class='tab_bg_1'><td align='center'><a href=\"auth.imap.php\"><strong>" .$LANG["login"][3] . "</strong></a></td> </tr>";
echo "<tr class='tab_bg_1'><td  align='center'><a href=\"auth.others.php\"><strong>" . $LANG["common"][67] . "</strong></a></td></tr>";
echo "</table></div>";
	
commonFooter();

?>