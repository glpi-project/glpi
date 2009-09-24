<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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


$NEEDED_ITEMS = array ('document', 'tracking');

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if (!$CFG_GLPI["use_public_faq"]) {
   checkLoginUser();
}

$doc=new Document;

if (isset($_GET['docid'])) { // docid for document
   if (!$doc->getFromDB($_GET['docid'])) {
      displayErrorAndDie($LANG['document'][43]);
   }

   $send=false;

   if (isset($_SESSION["glpiactiveprofile"]["interface"])
       && $_SESSION["glpiactiveprofile"]["interface"]=="central") {
      // My doc Check and Common doc right access
      if ((haveRight("document","r") && haveAccessToEntity($doc->fields['entities_id']))
            || $doc->fields["users_id"]==$_SESSION["glpiID"]) {
         $send=true;
      }

      // Knowbase Case
      if (!$send && haveRight("knowbase","r")) {
         $query = "SELECT *
            FROM `glpi_documents_items`
            WHERE `glpi_documents_items`.`itemtype` = '".KNOWBASE_TYPE."'
               AND `glpi_documents_items`.`documents_id`='".$doc->fields["id"]."'";

         $result=$DB->query($query);
         if ($DB->numrows($result)>0)
            $send=true;
      }

      if (!$send && haveRight("faq","r")) {
         $query = "SELECT *
            FROM `glpi_documents_items`
               LEFT JOIN `glpi_knowbaseitems`
                      ON (`glpi_knowbaseitems`.`id` = `glpi_documents_items`.`items_id`)
            WHERE `glpi_documents_items`.`itemtype` = '".KNOWBASE_TYPE."'
               AND `glpi_documents_items`.`documents_id`='".$doc->fields["id"]."'
               AND `glpi_knowbaseitems`.`is_faq`='1''";

         $result=$DB->query($query);
         if ($DB->numrows($result)>0)
            $send=true;
      }

      // Tracking Case
      if (!$send && isset($_GET["tickets_id"])) {
         $job=new Job;
         $job->getFromDB($_GET["tickets_id"]);

         if ($job->fields["users_id"]==$_SESSION["glpiID"]
             || $job->fields["users_id_assign"]==$_SESSION["glpiID"]) {
            $query = "SELECT *
               FROM `glpi_documents_items`
               WHERE `glpi_documents_items`.`items_id` = '".$_GET["tickets_id"]."'
                  AND `glpi_documents_items`.`itemtype` = '".TRACKING_TYPE."'
                  AND `documents_id`='".$doc->fields["id"]."'";
            $result=$DB->query($query);
            if ($DB->numrows($result)>0)
               $send=true;
         }
      }
   } else { // ! central

      // Check if it is my doc
      if (isset($_SESSION["glpiID"]) && $doc->fields["users_id"]==$_SESSION["glpiID"]) {
         $send=true;
      } else {
         if (haveRight("faq","r") || $CFG_GLPI["use_public_faq"]) {
            // Check if it is a FAQ document
            $query = "SELECT *
               FROM `glpi_documents_items`
                  LEFT JOIN `glpi_knowbaseitems`
                         ON (`glpi_knowbaseitems`.`id` = `glpi_documents_items`.`items_id`)
               WHERE `glpi_documents_items`.`itemtype` = '".KNOWBASE_TYPE."'
                  AND `glpi_documents_items`.`documents_id`='".$doc->fields["id"]."'
                  AND `glpi_knowbaseitems.is_faq`='1''";

            $result=$DB->query($query);
            if ($DB->numrows($result)>0)
               $send=true;
         }

         // Tracking Case
         if (!$send && isset($_GET["tickets_id"])) {
            $job=new Job;
            $job->getFromDB($_GET["tickets_id"]);

            if ($job->fields["users_id"]==$_SESSION["glpiID"]) {
               $query = "SELECT *
                  FROM `glpi_documents_items`
                  WHERE `glpi_documents_items`.`items_id` = '".$_GET["tickets_id"]."'
                     AND `glpi_documents_items`.`itemtype` = '".TRACKING_TYPE."'
                     AND `documents_id`='".$doc->fields["id"]."'";
               $result=$DB->query($query);
               if ($DB->numrows($result)>0)
                  $send=true;
            }
         }
      }
   }
   if (!file_exists(GLPI_DOC_DIR."/".$doc->fields['filepath'])) {
      displayErrorAndDie($LANG['document'][38]); // Not found

   } else if ($send) {
      if ($doc->fields['sha1sum']
          && $doc->fields['sha1sum']!=sha1_file(GLPI_DOC_DIR."/".$doc->fields['filepath'])) {
         displayErrorAndDie($LANG['document'][49]); // Doc alterated
      } else {
         $doc->send();
      }
   } else {
      displayErrorAndDie($LANG['document'][45]); // No right
   }
}
else if (isset($_GET["file"])) { // for other file

   $splitter=explode("/",$_GET["file"]);

   if (count($splitter)==2) {
      $send=false;

      if ($splitter[0]=="_dumps" && haveRight("backup","w")) {
         $send=true;
      }

      if ($send && file_exists(GLPI_DOC_DIR."/".$_GET["file"])) {
         sendFile(GLPI_DOC_DIR."/".$_GET["file"],$splitter[1]);
      } else {
         displayErrorAndDie($LANG['document'][45]);
      }
   } else {
      displayErrorAndDie($LANG['document'][44]);
   }
}

?>
