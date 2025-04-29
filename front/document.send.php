<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

use Glpi\Inventory\Conf;

$SECURITY_STRATEGY = 'no_check'; // may allow unauthenticated access, for public FAQ images

include('../inc/includes.php');

$doc = new Document();

if (isset($_GET['docid'])) {
    // Get file corresponding to given Document id.

    // Allow anonymous access at this point to be able to serve documents related to
    // public FAQ.
    // Document::canViewFile() will do appropriate checks depending on GLPI configuration.

    if (!$doc->getFromDB($_GET['docid'])) {
        Html::displayErrorAndDie(__('Unknown file'), true);
    }

    if (!file_exists(GLPI_DOC_DIR . "/" . $doc->fields['filepath'])) {
        Html::displayErrorAndDie(sprintf(__('File %s not found.'), $doc->fields['filename']), true); // Not found
    } elseif ($doc->canViewFile($_GET)) {
        if (
            $doc->fields['sha1sum']
            && $doc->fields['sha1sum'] != sha1_file(GLPI_DOC_DIR . "/" . $doc->fields['filepath'])
        ) {
            Html::displayErrorAndDie(__('File is altered (bad checksum)'), true); // Doc alterated
        } else {
            $context = $_GET['context'] ?? null;
            $doc->send($context);
        }
    } else {
        Html::displayErrorAndDie(__('Unauthorized access to this file'), true); // No right
    }
} elseif (isset($_GET["file"])) {
    // Get file corresponding to given path.

    Session::checkLoginUser(); // Do not allow anonymous access

    $splitter = explode("/", $_GET["file"], 2);
    $mime = null;
    if (count($splitter) == 2) {
        $expires_headers = false;
        $send = false;
        if (
            ($splitter[0] == "_dumps")
            && Session::haveRight("backup", CREATE)
        ) {
            $send = GLPI_DUMP_DIR . '/' . $splitter[1];
        }

        if ($splitter[0] == "_pictures") {
            if (Document::isImage(GLPI_PICTURE_DIR . '/' . $splitter[1])) {
                // Can use expires header as picture file path changes when picture changes.
                $expires_headers = true;
                $send = GLPI_PICTURE_DIR . '/' . $splitter[1];
            }
        }

        if ($splitter[0] == "_inventory" && Session::haveRight(Conf::$rightname, READ)) {
            $iconf = new Conf();
            if ($iconf->isInventoryFile(GLPI_INVENTORY_DIR . '/' . $splitter[1])) {
                $send = GLPI_INVENTORY_DIR . '/' . $splitter[1];

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = ($finfo->file($send));
                switch ($mime) {
                    case 'text/xml':
                        $mime = 'application/xml';
                        break;
                }
            }
        }

        if ($send && file_exists($send)) {
            Toolbox::sendFile($send, $splitter[1], $mime, $expires_headers);
        } else {
            Html::displayErrorAndDie(__('Unauthorized access to this file'), true);
        }
    } else {
        Html::displayErrorAndDie(__('Invalid filename'), true);
    }
}
