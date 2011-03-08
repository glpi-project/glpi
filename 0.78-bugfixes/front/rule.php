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
// Original Author of file: Walid Nouh
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

commonHeader($LANG['common'][12],$_SERVER['PHP_SELF'],"admin","rule",-1);

echo "<table class='tab_cadre'>";
echo "<tr><th>" . $LANG['rulesengine'][24] . "</th></tr>";
if ($CFG_GLPI["use_ocs_mode"] && haveRight("rule_ocs","r")) {
   echo "<tr class='tab_bg_1'><td class='center b'>";
   echo "<a href='ruleocs.php'>" . $LANG['rulesengine'][18] . "</a></td></tr>";
}

if (haveRight("rule_ldap","r")) {
   echo "<tr class='tab_bg_1'><td class='center b'>";
   echo "<a href='ruleright.php'>" .$LANG['rulesengine'][19] . "</a></td> </tr>";
}

if (haveRight("rule_mailcollector","r")
      && canUseImapPop()
         && MailCollector::getNumberOfMailCollectors()) {
   echo "<tr class='tab_bg_1'><td class='center b'>";
   echo "<a href='rulemailcollector.php'>" . $LANG['rulesengine'][70] . "</a></td></tr>";
}

if (haveRight("rule_ticket","r") || haveRight("entity_rule_ticket","r")) {
   echo "<tr class='tab_bg_1'><td class='center b'>";
   echo "<a href='ruleticket.php'>" . $LANG['rulesengine'][28] . "</a></td></tr>";
}

if (haveRight("rule_softwarecategories","r")) {
   echo "<tr class='tab_bg_1'><td class='center b'>";
   echo "<a href='rulesoftwarecategory.php'>&nbsp;" . $LANG['rulesengine'][37] . "&nbsp;</a></td></tr>";
}

echo "</table>";
commonFooter();

?>
