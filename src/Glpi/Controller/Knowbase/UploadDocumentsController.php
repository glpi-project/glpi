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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Controller\AbstractDocumentUploadController;
use Glpi\Exception\Http\BadRequestHttpException;
use KnowbaseItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\json_decode;

final class UploadDocumentsController extends AbstractDocumentUploadController
{
    #[Route(
        "/Knowbase/{id}/UploadDocuments",
        name: "knowbase_upload_documents",
        methods: ["POST"],
        requirements: ['id' => '\d+']
    )]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        // Load target KB item
        $kb = KnowbaseItem::getById($id);
        if (!$kb) {
            throw new BadRequestHttpException();
        }

        // Read parameters
        $data  = json_decode($request->getContent(), true);
        $files = $data['files'] ?? [];
        if (!is_array($files) || count($files) === 0) {
            throw new BadRequestHttpException();
        }

        $result = [];
        $twig = TemplateRenderer::getInstance();

        foreach ($this->createDocuments(
            files: $files,
            itemtype: KnowbaseItem::class,
            items_id: $id,
            entities_id: $kb->getEntityID(),
            is_recursive: $kb->isRecursive(),
        ) as $doc) {
            // Compute dynamic styles based on file extension
            $styles = KnowbaseItem::getDocumentIconAndColor($doc['extension']);
            $doc['icon_class']  = $styles['icon_class'];
            $doc['color_class'] = $styles['color_class'];

            // Render badge
            $result[] = [
                'html' => $twig->render('pages/tools/kb/document_badge.html.twig', [
                    'doc'      => $doc,
                    'can_edit' => true,
                ]),
            ];
        }

        return new JsonResponse(['documents' => $result]);
    }
}
