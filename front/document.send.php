<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include ('../inc/includes.php');

if (!$CFG_GLPI["use_public_faq"]) {
   Session::checkLoginUser();
}

$doc = new Document();

if (isset($_GET['docid'])) { // docid for document
   if (!$doc->getFromDB($_GET['docid'])) {
      Html::displayErrorAndDie(__('Unknown file'), true);
   }

   if (!file_exists(GLPI_DOC_DIR."/".$doc->fields['filepath'])) {
      Html::displayErrorAndDie(__('File not found'), true); // Not found

   } else if ($doc->canViewFile($_GET)) {
      if ($doc->fields['sha1sum']
          && $doc->fields['sha1sum'] != sha1_file(GLPI_DOC_DIR."/".$doc->fields['filepath'])) {

         Html::displayErrorAndDie(__('File is altered (bad checksum)'), true); // Doc alterated
      } else {
         $context = isset($_GET['context']) ? $_GET['context'] : null;
         $doc->send($context);
      }
   } else {
      Html::displayErrorAndDie(__('Unauthorized access to this file'), true); // No right
   }

} else if (isset($_GET["file"])) { // for other file
   $splitter = explode("/", $_GET["file"], 2);
   if (count($splitter) == 2) {
      $send = false;
      if (($splitter[0] == "_dumps")
          && Session::haveRight("backup", CREATE)) {
         $send = true;
      }

      if ($splitter[0] == "_pictures") {
         if (Document::isImage(GLPI_DOC_DIR."/".$_GET['file'])) {
            $send = true;
         }
      }

      if ($send && file_exists(GLPI_DOC_DIR."/".$_GET["file"])) {
         Toolbox::sendFile(GLPI_DOC_DIR."/".$_GET["file"], $splitter[1]);
      } else {
         Html::displayErrorAndDie(__('Unauthorized access to this file'), true);
      }
   } else {
      Html::displayErrorAndDie(__('Invalid filename'), true);
   }
}
