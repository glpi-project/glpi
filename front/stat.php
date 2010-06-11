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

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

commonHeader($LANG['Menu'][13],'',"maintain","stat");

checkRight("statistic","1");

//Affichage du tableau de presentation des stats
echo "<table class='tab_cadre_fixe'>";
echo "<tr><th>".$LANG['stats'][0]."&nbsp;:</th></tr>";
echo "<tr class='tab_bg_1'><td class='center b'><a href='stat.global.php'>".
      $LANG['stats'][1]."</a></td></tr>";
echo "<tr class='tab_bg_1'><td class='center b'><a href='stat.tracking.php'>".
      $LANG['stats'][47]."</a></td></tr>";
echo "<tr class='tab_bg_1'><td class='center'><a href='stat.location.php'><b>".
      $LANG['stats'][3]."</b></a><br> (".$LANG['common'][15].", ".$LANG['common'][17].", ".
      $LANG['computers'][9].", ".$LANG['devices'][4].", ".$LANG['computers'][36].", ".
      $LANG['devices'][2].", ".$LANG['devices'][5].")</td></tr>";
echo "<tr class='tab_bg_1'><td class='center b'><a href='stat.item.php'>".$LANG['stats'][45].
      "</a></td></tr>";

$names = array();
if (isset($PLUGIN_HOOKS["stats"]) && is_array($PLUGIN_HOOKS["stats"])) {
   foreach ($PLUGIN_HOOKS["stats"] as $plug => $pages) {
      $function = "plugin_version_$plug";
      $plugname = $function();
      if (is_array($pages) && count($pages)) {
         foreach ($pages as $page => $name) {
            $names[$plug.'/'.$page] = $plugname['name'].' - '.$name;
         }
      }
   }
   asort($names);
}

foreach ($names as $key => $val) {
   echo "<tr class='tab_bg_1'><td class='center b'><a href='".$CFG_GLPI["root_doc"].
         "/plugins/$key'>$val</a></td></tr>";
}
echo "</table>";

commonFooter();

?>
