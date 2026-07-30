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

use Document;
use Document_Item;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Controller\AbstractController;
use Glpi\Controller\CrudControllerTrait;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use KnowbaseItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\json_decode;

final class LinkDocumentController extends AbstractController
{
    use CrudControllerTrait;

    #[Route(
        "/Knowbase/{id}/LinkDocuments",
        name: "knowbase_link_documents",
        methods: ["POST"],
        requirements: ['id' => '\d+']
    )]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $kb = KnowbaseItem::getById($id);
        if (!$kb) {
            throw new BadRequestHttpException();
        }
        if (!$kb->can($id, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        $data = json_decode($request->getContent(), true);
        $documents_ids = $data['documents_ids'] ?? [];

        if (!is_array($documents_ids)) {
            throw new BadRequestHttpException();
        }

        $linked = [];
        $twig = TemplateRenderer::getInstance();

        foreach ($documents_ids as $doc_id) {
            $doc_id = (int) $doc_id;
            if ($doc_id <= 0) {
                continue;
            }

            try {
                $doc_item = $this->add(Document_Item::class, [
                    'documents_id' => $doc_id,
                    'itemtype'     => KnowbaseItem::class,
                    'items_id'     => $id,
                ]);
            } catch (\RuntimeException) {
                // Skip documents that fail (duplicates, permission issues, etc.)
                continue;
            }

            $document = new Document();
            if (!$document->getFromDB($doc_id)) {
                continue;
            }

            $filename  = (string) ($document->fields['filename'] ?? '');
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $styles    = KnowbaseItem::getDocumentIconAndColor($extension);

            $linked[] = [
                'html' => $twig->render('pages/tools/kb/document_badge.html.twig', [
                    'doc'      => [
                        'assoc_id'     => $doc_item->getID(),
                        'id'           => $doc_id,
                        'filename'     => $filename,
                        'download_url' => $document->getDownloadUrl(),
                        'icon_class'   => $styles['icon_class'],
                        'color_class'  => $styles['color_class'],
                    ],
                    'can_edit' => true,
                ]),
            ];
        }

        return new JsonResponse(['documents' => $linked]);
    }
}
