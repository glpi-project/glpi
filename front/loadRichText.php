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

Session::checkLoginUser();

if (isset($_GET['items_id']) && isset($_GET['itemtype']) && isset($_GET['field'])) {
   if ($item = getItemForItemtype($_GET['itemtype'])) {
      $item->getFromDB($_GET['items_id']);
      Html::popHeader(__('Availability'), '', true);
      echo "<div>";
      echo Toolbox::unclean_cross_side_scripting_deep($item->setRichTextContent($item->fields[$_GET['field']]));
      echo "</div>";
      $JS = "";
      if (isset($_GET['content_id'])) {
         $JS .= " window.top.window.document.getElementById('".$_GET['content_id']."').height = document.body.scrollHeight;";
      }
      echo Html::scriptBlock($JS);
      Html::popFooter();
   }
}

?>