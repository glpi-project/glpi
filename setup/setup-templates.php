<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2004 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
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
*/
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_setup.php");

checkAuthentication("normal");
commonHeader($lang["title"][2],$_SERVER["PHP_SELF"]);
listTemplates(COMPUTER_TYPE,$HTMLRel ."computers/computers-info-form.php");
listTemplates(NETWORKING_TYPE,$HTMLRel ."networking/networking-info-form.php");
listTemplates(PRINTER_TYPE,$HTMLRel ."printers/printers-info-form.php");
listTemplates(MONITOR_TYPE,$HTMLRel ."monitors/monitors-info-form.php");
listTemplates(SOFTWARE_TYPE,$HTMLRel ."software/software-info-form.php");
listTemplates(PERIPHERAL_TYPE,$HTMLRel ."peripherals/peripherals-info-form.php");

commonFooter();


?>
