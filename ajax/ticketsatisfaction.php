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
// Original Author of file: Nelly Mahu-Lasson
// Purpose of file:
// ----------------------------------------------------------------------


// Direct access to file
if (strpos($_SERVER['PHP_SELF'],"ticketsatisfaction.php")) {
   $AJAX_INCLUDE = 1;
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   header_nocache();
}

$entity = new EntityData();

if (isset($_REQUEST['inquest_config']) && isset($_REQUEST['entities_id'])) {
   if ($entity->getFromDB($_REQUEST['entities_id'])) {
      $inquest_config = $entity->getfield('inquest_config');
      $inquest_delay  = $entity->getfield('inquest_delay');
      $inquest_rate   = $entity->getfield('inquest_rate');
      $max_closedate  = $entity->getfield('max_closedate');
   } else {
      $inquest_config = $_REQUEST['inquest_config'];
      $inquest_delay  = -1;
      $inquest_rate   = -1;
      $max_closedate  = '';
   }

   if ($_REQUEST['inquest_config']>0 ) {
      echo "<table class='tab_cadre_fixe' width='50%'>";
      echo "<tr class='tab_bg_1'><td width='50%'>".$LANG['entity'][20]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Dropdown::showInteger('inquest_delay', $inquest_delay, 0, 90, 1);
      echo "&nbsp;".$LANG['stats'][31]."</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='1'>".$LANG['entity'][21]."&nbsp;:&nbsp;</td>";
      echo "<td colspan='1'>";
      Dropdown::showInteger('inquest_rate', $inquest_rate, 10, 100, 10,
                            array(0 => $LANG['crontask'][31]));
      echo "&nbsp;%</td></tr>";

      echo "<tr class='tab_bg_1'><td colspan='1'>" . $LANG['entity'][22] . "&nbsp;:&nbsp;</td>";
      echo "<td colspan='1'>" . convDateTime($max_closedate)."</td></tr>";

      if ($_REQUEST['inquest_config']==2 ) {
         echo "<tr class='tab_bg_1'><td colspan='1'>" . $LANG['common'][94] . "&nbsp;:&nbsp;</td>";
         echo "<td>";
         autocompletionTextField($entity, "inquest_URL");
         echo "</td></tr>";
      }

      echo "</table>";
   }
}

?>
