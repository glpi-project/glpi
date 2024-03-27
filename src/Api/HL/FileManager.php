<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Api\HL;

use Document;
use Document_Item;
use Glpi\Api\HL\Controller\ManagementController;

/**
 * @phpstan-type FileUpload array{tmp_name: string, name: string, type: string, size: int, error: int}
 */
final class FileManager
{
    /**
     * Creates a Document with the uploaded file.
     * This function assumes the file is authorized to be uploaded and meets the GLPI file size requirements.
     * @param FileUpload $upload The file to upload
     * @param array $document_input The input for the Document creation
     * @return int|null The ID of the created Document, or null if the upload failed
     */
    public static function uploadAsDocument(array $upload, array $document_input = []): ?int
    {
        if ($upload['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        if (!file_exists($upload['tmp_name']) || !is_uploaded_file($upload['tmp_name'])) {
            return null;
        }
        $input = Search::getInputParamsBySchema(ManagementController::getKnownSchemas()['Document'], $document_input);
        $document = new Document();
        if (!$document->can($document->getID(), CREATE, $input)) {
            return null;
        }
        $sha = sha1_file($upload['tmp_name']);
        $dest = Document::getUploadFileValidLocationName(Document::isValidDoc($upload['name']), $sha);
        $documents_id = false;
        if (move_uploaded_file($upload['tmp_name'], GLPI_DOC_DIR . '/' . $dest)) {
            $input['filename'] = $upload['name'];
            $input['sha1sum'] = $sha;
            $input['filepath'] = $dest;
            $document = new Document();
            $documents_id = $document->add($input);
        }
        return $documents_id !== false ? $documents_id : null;
    }

    /**
     * Creates a Document with the uploaded file and links it to an item.
     * This function assumes the file is authorized to be uploaded and meets the GLPI file size requirements.
     * @param string $itemtype The itemtype of the item to link the document to
     * @param int $items_id The ID of the item to link the document to
     * @param FileUpload $upload The file to upload
     * @param array $document_input The input for the Document creation
     * @return int|null The ID of the created Document_Item link, or null if the upload/link failed
     * @see FileManager::uploadAsDocument
     */
    public static function uploadAsDocumentAndLink(string $itemtype, int $items_id, array $upload, array $document_input = []): ?int
    {
        $item = new $itemtype();
        if (!$item->can($items_id, UPDATE)) {
            return null;
        }
        $documents_id = self::uploadAsDocument($upload, $document_input);
        $link_id = false;
        if ($documents_id !== null) {
            $document_item = new Document_Item();
            $link_id = $document_item->add([
                'documents_id' => $documents_id,
                'itemtype' => $itemtype,
                'items_id' => $items_id,
            ]);
        }
        return $link_id !== false ? $link_id : null;
    }

    public static function replaceDocument(int $documents_id, array $upload, array $document_input = []): bool
    {
        $document = new Document();
        $document->getFromDB($documents_id);
        $input = Search::getInputParamsBySchema(ManagementController::getKnownSchemas()['Document'], $document_input);
        if (!$document->can($documents_id, UPDATE, $input)) {
            return false;
        }
        $sha = sha1_file($upload['tmp_name']);
        $dest = Document::getUploadFileValidLocationName(Document::isValidDoc($upload['name']), $sha);
        if (move_uploaded_file($upload['tmp_name'], GLPI_DOC_DIR . '/' . $dest)) {
            $input['filename'] = $upload['name'];
            $input['sha1sum'] = $sha;
            $input['filepath'] = $dest;
            $document->update($input);
            return true;
        }
        return false;
    }
}
