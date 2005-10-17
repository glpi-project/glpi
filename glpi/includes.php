<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
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
 ------------------------------------------------------------------------
*/
 
// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");
include ($phproot . "/glpi/common/classes.php");
include ($phproot . "/glpi/common/functions.php");
include ($phproot . "/glpi/common/functions_dropdown.php");
include ($phproot . "/glpi/common/functions_reports.php");
include ($phproot . "/glpi/common/functions_display.php");
include ($phproot . "/glpi/common/functions_search.php");
include ($phproot . "/glpi/config/config.php");

include ($phproot . "/glpi/common/Timer.php");
$TIMER_DEBUG=new Script_Timer;
$TIMER_DEBUG->Start_Timer();

if ($cfg_debug["active"]){
	if ($cfg_debug["profile"]){		
		$SQL_TOTAL_TIMER=0;
		$SQL_TOTAL_REQUEST=0;
	}
	if ($cfg_debug["sql"]){		
		$DEBUG_SQL_STRING="";
	}
}

?>
