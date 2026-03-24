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

namespace Glpi\Controller\Knowbase;

use CommonITILObject;
use Document;
use Document_Item;
use Glpi\Controller\AbstractController;
use Glpi\Controller\CrudControllerTrait;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use Html;
use KnowbaseItem;
use Safe\Exceptions\FilesystemException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\json_decode;
use function Safe\realpath;

final class UploadInlineImageController extends AbstractController
{
    use CrudControllerTrait;
    #[Route(
        "/Knowbase/{knowbaseitems_id}/UploadInlineImage",
        name: "knowbase_upload_inline_image",
        methods: ["POST"],
        requirements: [
            'knowbaseitems_id' => '\d+',
        ]
    )]
    public function __invoke(int $knowbaseitems_id, Request $request): JsonResponse
    {
        $kbitem = new KnowbaseItem();
        if (!$kbitem->getFromDB($knowbaseitems_id)) {
            throw new NotFoundHttpException();
        }

        if (!$kbitem->can($knowbaseitems_id, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        $data = json_decode($request->getContent(), true);
        $filename = $data['filename'] ?? '';
        $prefix = $data['prefix'] ?? '';

        if (empty($filename)) {
            throw new BadRequestHttpException();
        }

        // Validate file is within GLPI_TMP_DIR (path traversal protection)
        $file_path = GLPI_TMP_DIR . "/$filename";
        if (!file_exists($file_path)) {
            throw new BadRequestHttpException();
        }
        try {
            $real_path = realpath($file_path);
            $real_tmp_dir = realpath(GLPI_TMP_DIR);
        } catch (FilesystemException) {
            throw new BadRequestHttpException();
        }
        if (!str_starts_with($real_path, $real_tmp_dir)) {
            throw new BadRequestHttpException();
        }

        // Validate file is an image
        if (!Document::isImage($real_path)) {
            throw new BadRequestHttpException();
        }

        // Create Document
        $doc = new Document();

        // Compute display name: strip prefix from filename if a real prefix exists
        $display_name = $filename;
        if (!empty($prefix) && $prefix !== $filename && str_starts_with($filename, $prefix)) {
            $display_name = substr($filename, strlen($prefix));
        }

        $doc_input = [
            '_filename'               => [$filename],
            '_only_if_upload_succeed'  => 1,
            'entities_id'             => $kbitem->fields['entities_id'] ?? 0,
            'is_recursive'            => $kbitem->fields['is_recursive'] ?? 1,
            'name'                    => $display_name,
        ];
        // Only pass prefix if it's a real prefix (shorter than filename)
        if (!empty($prefix) && $prefix !== $filename && str_starts_with($filename, $prefix)) {
            $doc_input['_prefix_filename'] = [$prefix];
        }

        $doc_id = $doc->add($doc_input);
        if (!$doc_id) {
            return new JsonResponse([
                'success' => false,
                'message' => __('Failed to create document'),
            ], 500);
        }

        // Link Document to KnowbaseItem, hidden from documents list
        $doc_item = new Document_Item();
        $link_id = $doc_item->add([
            'documents_id'      => $doc_id,
            'itemtype'          => KnowbaseItem::class,
            'items_id'          => $knowbaseitems_id,
            'timeline_position' => CommonITILObject::NO_TIMELINE,
            'users_id'          => \Session::getLoginUserID(),
        ]);
        if (!$link_id) {
            return new JsonResponse([
                'success' => false,
                'message' => __('Failed to link document to article'),
            ], 500);
        }

        // Build the document serving URL
        $url = Html::getPrefixedUrl(
            '/front/document.send.php?'
            . http_build_query([
                'docid'    => $doc_id,
                'itemtype' => KnowbaseItem::class,
                'items_id' => $knowbaseitems_id,
            ])
        );

        return new JsonResponse([
            'success' => true,
            'url'     => $url,
        ]);
    }
}
