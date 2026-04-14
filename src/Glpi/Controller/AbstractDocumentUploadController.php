<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Controller;

use CommonDBTM;
use Document;
use Document_Item;
use Glpi\Exception\Http\BadRequestHttpException;

abstract class AbstractDocumentUploadController extends AbstractController
{
    use CrudControllerTrait;

    /**
     * Process a list of uploaded file entries and create Document + Document_Item records.
     *
     * Each entry in $files must contain:
     *   - _filename        : temp filename
     *   - _prefix_filename : temp filename prefix
     *   - _tag_filename    : file tag UUID
     *   - name             : document display name (optional)
     *   - comment          : document description (optional)
     *
     * Returns an array of successfully created document data:
     *   ['assoc_id', 'id', 'filename', 'download_url', 'extension']
     *
     * @param array<array<string,string>> $files
     * @param class-string<CommonDBTM> $itemtype
     * @return array<array{assoc_id: int, filename: string, download_url: string, extension: string}>
     */
    final protected function createDocuments(
        array $files,
        string $itemtype,
        int $items_id,
        int $entities_id,
        bool $is_recursive,
    ): array {
        $created = [];

        foreach ($files as $file_data) {
            // Read inputs
            $temp_name = $file_data['_filename']        ?? '';
            $prefix    = $file_data['_prefix_filename'] ?? '';
            $tag       = $file_data['_tag_filename']    ?? '';
            $name      = $file_data['name']             ?? '';
            $comment   = $file_data['comment']          ?? '';

            // Validate mandatory parameters
            if (empty($temp_name) || empty($prefix) || empty($tag)) {
                throw new BadRequestHttpException();
            }

            // Add document
            $input = [
                'name'             => $name,
                'comment'          => $comment,
                '_filename'        => [$temp_name],
                '_prefix_filename' => [$prefix],
                '_tag_filename'    => [$tag],
                'itemtype'         => $itemtype,
                'items_id'         => $items_id,
                'entities_id'      => $entities_id,
                'is_recursive'     => $is_recursive,
            ];
            $document = $this->add(Document::class, $input);

            // Fetch document item link
            $doc_id   = $document->getID();
            $doc_item = new Document_Item();
            if (!$doc_item->getFromDBByCrit([
                'documents_id' => $doc_id,
                'itemtype'     => $itemtype,
                'items_id'     => $items_id,
            ])) {
                continue;
            }

            // Compute file extension
            $filename  = (string) ($document->fields['filename'] ?? '');
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            $created[] = [
                'assoc_id'     => $doc_item->getID(),
                'filename'     => $filename,
                'download_url' => $document->getDownloadUrl(),
                'extension'    => $extension,
            ];
        }

        return $created;
    }
}
