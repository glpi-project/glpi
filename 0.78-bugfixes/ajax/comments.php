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
$AJAX_INCLUDE=1;
include (GLPI_ROOT."/inc/includes.php");

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
header_nocache();

checkLoginUser();

if (isset($_REQUEST["table"]) && isset($_REQUEST["value"])) {
   // Security
   if (! TableExists($_REQUEST['table']) ) {
      exit();
   }

   switch ($_REQUEST["table"]) {
      case "glpi_users" :
         if ($_REQUEST['value']==0) {
            $tmpname['link']=$CFG_GLPI['root_doc']."/front/user.php";
            $tmpname['comment']="";
         } else {
            $tmpname=getUserName($_REQUEST["value"],2);
         }
         echo $tmpname["comment"];
         if (isset($_REQUEST['withlink'])){
            echo "<script type='text/javascript' >\n";
            echo "Ext.get('".$_REQUEST['withlink']."').dom.href='".$tmpname['link']."';";
            echo "</script>\n";
         }
         break;

      default :
         if ($_REQUEST["value"]>0) {
            $tmpname=Dropdown::getDropdownName($_REQUEST["table"],$_REQUEST["value"],1);
            echo $tmpname["comment"];
         }
   }
}
?>