<?php
/*
 * @version $Id: document.php 8830 2009-09-01 06:28:12Z remi $
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
// Original Author of file: Remi collet
// Purpose of file:
// ----------------------------------------------------------------------


define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

/// TODO need to add right check !!

commonHeader($LANG['common'][12],$_SERVER['PHP_SELF'],"config","dropdowns");

$optgroup = getAllDropdowns();

echo "<div class='center'>";
/*
$nb=0;
foreach($optgroup as $label => $dp) {
   $nb += count($dp);
}
$step = ($nb>15 ? ($nb/3) : $nb);

echo "<table><tr class='top'><td><table class='tab_cadre'>";
$i=1;
foreach($optgroup as $label => $dp) {

   echo "<tr><th>$label</th></tr>\n";

   foreach ($dp as $key => $val) {
      echo "<tr class='tab_bg_1'><td><a href='".GLPI_ROOT.'/'.$SEARCH_PAGES[$key]."'>";
      echo "$val</td></tr>\n";
      $i++;
   }
   if ($i>=$step) {
      echo "</table></td><td width='25'>&nbsp;</td><td><table class='tab_cadre'>";
      $step += $step;
   }
}
echo "</table></td></tr></table>";

echo "</table>";
*/
echo "<table class='tab_cadre_fixe'>";

$i=1;
foreach($optgroup as $label => $dp) {

   echo "<tr><th>";
   echo "<a href=\"javascript:showHideDiv('dropdowncat$i','imgcat$i','" .
         GLPI_ROOT . "/pics/folder.png','" . GLPI_ROOT . "/pics/folder-open.png');\">";
   echo "<img alt='' name='imgcat$i' src=\"" . GLPI_ROOT . "/pics/folder.png\">&nbsp;$label";
   echo "</a></th></tr>\n";
   echo "<tr class='tab_bg_2'><td class='center'>";
   echo "<div id='dropdowncat$i' style='display:none;'><ul>";

   foreach ($dp as $key => $val) {
      echo "<li><a href='".GLPI_ROOT.'/'.$SEARCH_PAGES[$key]."'>$val</a></li>\n";
   }

   echo "</ul></div></td></tr>";
   $i++;
}
echo "</table>";

commonFooter();

?>
