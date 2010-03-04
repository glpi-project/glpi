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

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if (empty($_POST["type"]) || ($_POST["type"] != "Helpdesk") || !$CFG_GLPI["use_anonymous_helpdesk"]) {
   checkRight("create_ticket","1");
}

$track = new Ticket();

// Security check
if (empty($_POST) || count($_POST) == 0) {
   glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
}

if (!empty($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
   nullHeader($LANG['title'][10]);

} else if ($_POST["_from_helpdesk"]) {
   helpHeader($LANG['title'][1],'',$_SESSION["glpiname"]);

} else {
   commonHeader($LANG['title'][1],'',$_SESSION["glpiname"],"maintain","tracking");
}

if (isset($_POST["_my_items"]) && !empty($_POST["_my_items"])) {
   if (preg_match('/(.*)_([1-9]+)/',$_POST["_my_items"],$matches)) {
      $_POST["itemtype"] = $matches[1];
      $_POST["items_id"] = $matches[2];
   }
}

if (!isset($_POST["itemtype"]) || (empty($_POST["items_id"]) && $_POST["itemtype"] != 0)) {
   $_POST["itemtype"] = '';
   $_POST["items_id"] = 0;
}

if ($newID = $track->add($_POST)){
   if (isset($_POST["type"]) && ($_POST["type"] == "Helpdesk")) {
      echo "<div class='center'>".$LANG['help'][18]."<br><br>";
      displayBackLink();
      echo "</div>";
   } else {
      echo "<div class='center b'>";
      echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/ok.png\" alt=\"OK\"><br><br>";
      echo $LANG['help'][18]." (".$LANG['job'][38];
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.form.php?id=$newID'>$newID</a>)<br>";
      echo $LANG['help'][19]."</div>";
   }
} else {
   echo "<div class='center'>";
   echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/warning.png\" alt='warning'><br></div>";
   displayMessageAfterRedirect();
   displayBackLink();
}
nullFooter();

?>
