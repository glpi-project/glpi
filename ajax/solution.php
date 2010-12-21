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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

$AJAX_INCLUDE=1;

define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkLoginUser();
// print_r($_POST);exit();
if (isset($_POST['value']) && $_POST['value'] > 0) {
   $template = new TicketSolutionTemplate();
   if ($template->getFromDB($_POST['value'])) {
      echo "<textarea name='solution' rows='12' cols='80'>";
      echo $template->getField('content');
      echo "</textarea>";
      echo "<script type='text/javascript'>\n
         document.getElementById('".$_POST["type_id"]."').value = ".$template->getField('ticketsolutiontypes_id').";
      </script>";
   }
} else {
      echo "<textarea name='solution' rows='12' cols='80'>";
      echo "</textarea>";
}

?>