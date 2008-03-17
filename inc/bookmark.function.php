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

if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}


/**
 * Display bookmark buttons
 *
 * @param $type bookmark type to use
 * @param $device_type device type of item where is the bookmark
 **/
function showSaveBookmarkButton($type,$device_type=0){
	global $CFG_GLPI,$LANG;

	echo "  <a href='#' onClick=\"var w = window.open('".$CFG_GLPI["root_doc"]."/front/popup.php?popup=edit_bookmark&amp;type=$type&amp;device_type=$device_type' ,'glpipopup', 'height=400, width=600, top=100, left=100, scrollbars=yes' );w.focus();\">"; 
	echo "<img src='".$CFG_GLPI["root_doc"]."/pics/menu_add.png'  value='OK'   title=\"".$LANG["buttons"][51]." ".$LANG["bookmark"][1]."\"  alt=\"".$LANG["buttons"][51]." ".$LANG["bookmark"][1]."\"  class='calendrier' >"; 
	echo "	</a>";
}

?>
