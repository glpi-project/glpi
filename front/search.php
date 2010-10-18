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

checkCentralAccess();
commonHeader($LANG['search'][0],$_SERVER['PHP_SELF']);

if (isset($_GET["globalsearch"])) {
   $searchtext=$_GET["globalsearch"];
   $types = array('Ticket','Computer', 'Monitor', 'Software', 'NetworkEquipment', 'Peripheral', 'Printer',
                  'Phone', 'Contact', 'Supplier', 'Document');

   foreach($types as $itemtype) {
      $item = new $itemtype();
      if ($item->canView()) {
         $_GET["reset"] = 'reset';
         $_GET["display_type"] = GLOBAL_SEARCH;

         Search::manageGetValues($itemtype,false,true);

         if ($_GET["field"][0] =='view') {
            $_GET["contains"][0]   = $searchtext;
            $_GET["searchtype"][0] = 'contains';
            $_SESSION["glpisearchcount"][$itemtype] = 1;

         } else {
            $_GET["field"][1] = 'view';
            $_GET["contains"][1]   = $searchtext;
            $_GET["searchtype"][1] = 'contains';
            $_SESSION["glpisearchcount"][$itemtype] = 2;
         }
         Search::showList($itemtype,$_GET);
         unset($_GET["contains"]);
         unset($_GET["searchtype"]);
         echo "<hr>";
         $_GET=array();
      }
   }
}

commonFooter();

?>
