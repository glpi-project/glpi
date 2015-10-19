<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

include ('../inc/includes.php');

Session::checkCentralAccess();
Html::header(__('Search'),$_SERVER['PHP_SELF']);

if (!$CFG_GLPI['allow_search_global']) {
   Html::displayRightError();
}
if (isset($_GET["globalsearch"])) {
   $searchtext=trim($_GET["globalsearch"]);

   foreach ($CFG_GLPI["globalsearch_types"] as $itemtype) {
      if (($item = getItemForItemtype($itemtype))
          && $item->canView()) {
         $_GET["reset"]        = 'reset';

         $params                 = Search::manageParams($itemtype,$_GET, false,true);
         $params["display_type"] = Search::GLOBAL_SEARCH;

         $count                  = count($params["criteria"]);

         $params["criteria"][$count]["field"]       = 'view';
         $params["criteria"][$count]["searchtype"]  = 'contains';
         $params["criteria"][$count]["value"]       = $searchtext;
//          $_SESSION["glpisearchcount"][$itemtype]  = $count+1;
//          $_SESSION["glpisearchcount2"][$itemtype] = 0;

         Search::showList($itemtype, $params);
         echo "<hr>";
      }
   }
}

Html::footer();
?>
