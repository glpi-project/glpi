<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
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
            $content_filename = Link::generateLinkContents($link, $item);
            $content_data     = Link::generateLinkContents($file, $item);

            if (isset($_GET['rank']) && isset($content_filename[$_GET['rank']])) {
               $filename = $content_filename[$_GET['rank']];
            } else {
               // first one (the same for all IP)
               $filename = reset($content_filename);
            }
            if (isset($_GET['rank']) && isset($content_data[$_GET['rank']])) {
               $data = $content_data[$_GET['rank']];
            } else {
               // first one (probably missing arg)
               $data = reset($content_data);
            }
            header("Content-disposition: filename=\"$filename\"");
            $mime = "application/scriptfile";

            header("Content-type: ".$mime);
            header('Pragma: no-cache');
            header('Expires: 0');

            // Pour que les \x00 ne devienne pas \0
            $mc = get_magic_quotes_runtime();
            if ($mc) {
               @set_magic_quotes_runtime(0);
            }

            echo $data;

            if ($mc) {
               @set_magic_quotes_runtime($mc);
            }
         }
      }
   }
}

?>