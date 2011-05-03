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

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("link","r");

if (isset($_GET["lID"])) {
   $query = "SELECT `glpi_links`.`id`, `glpi_links`.`link`, `glpi_links`.`data`
             FROM `glpi_links`
             WHERE `glpi_links`.`id` = '".$_GET["lID"]."'";

   $result = $DB->query($query);

   if ($DB->numrows($result) == 1) {
      $file = $DB->result($result,0,"data");
      $link = $DB->result($result,0,"link");

      if (class_exists($_GET["itemtype"])) {
         $item = new $_GET["itemtype"]();
         if ($item->getFromDB($_GET["id"])) {
            $content_filename = Link::generateLinkContents($link,$item);
            $content_data = Link::generateLinkContents($file,$item);

            header("Content-disposition: filename=\"".$content_filename[0]."\"");
            $mime = "application/scriptfile";

            header("Content-type: ".$mime);
            header('Pragma: no-cache');
            header('Expires: 0');

            // Pour que les \x00 ne devienne pas \0
            $mc = get_magic_quotes_runtime();
            if ($mc) {
               @set_magic_quotes_runtime(0);
            }
            // May have several values due to network datas : use only first one
            echo current($content_data);

            if ($mc) {
               @set_magic_quotes_runtime($mc);
            }
         }
      }
   }
}

?>