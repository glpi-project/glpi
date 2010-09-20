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

define('GLPI_ROOT','..');
include (GLPI_ROOT."/inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkRight("networking", "w");

// Make a select box
if (class_exists($_POST["itemtype"])) {
   $table = getTableForItemType($_POST["itemtype"]);
   $rand  = mt_rand();

   $use_ajax = true;
   $paramsconnectpdt
      = array('searchText'      => '__VALUE__',
              'itemtype'        => $_POST['itemtype'],
              'rand'            => $rand,
              'myname'          => "items",
              'entity_restrict' => $_POST["entity_restrict"],
              'update_item'
                  => array('value_fieldname' => 'item',
                           'to_update'       => "results_item_$rand",
                           'url'             => $CFG_GLPI["root_doc"]."/ajax/dropdownConnectPort.php",
                           'moreparams'      => array('current'  => $_POST['current'],
                                                      'itemtype' => $_POST['itemtype'],
                                                      'myname'   => $_POST['myname'])));

   $default = "<select name='item$rand'><option value='0'>".DROPDOWN_EMPTY_VALUE."</option></select>\n";
   ajaxDropdown($use_ajax, "/ajax/dropdownValue.php", $paramsconnectpdt, $default, $rand);

   echo "<span id='results_item_$rand'>";
   echo "</span>\n";

}

?>