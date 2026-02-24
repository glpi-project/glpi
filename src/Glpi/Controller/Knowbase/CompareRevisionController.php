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

use Glpi\Controller\AbstractController;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\RichText\RichText;
use KnowbaseItem;
use KnowbaseItem_Revision;
use Ssddanbrown\HtmlDiff\Diff;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CompareRevisionController extends AbstractController
{
    #[Route(
        "/Knowbase/{id}/CompareRevision/{revision_id}",
        name: "knowbase_article_compare_revision",
        methods: ["GET"],
        requirements: [
            'id' => '\d+',
            'revision_id' => '\d+',
        ]
    )]
    public function __invoke(int $id, int $revision_id): Response
    {
        $kb = KnowbaseItem::getById($id);
        if (!$kb) {
            throw new BadRequestHttpException();
        }

        if (!$kb->can($id, READ)) {
            throw new AccessDeniedHttpException();
        }

        $revision = KnowbaseItem_Revision::getById($revision_id);
        if (!$revision || (int) $revision->fields['knowbaseitems_id'] !== $id) {
            throw new BadRequestHttpException();
        }

        // Old = revision content, New = current article content
        $oldName = $revision->fields['name'];
        $newName = $kb->fields['name'];

        $oldAnswer = RichText::getEnhancedHtml($revision->fields['answer'], ['text_maxsize' => 0]);
        $newAnswer = RichText::getEnhancedHtml($kb->fields['answer'], ['text_maxsize' => 0]);

        // Normalize non-breaking spaces (same as the former JS normalizeHtml)
        $oldName   = $this->normalizeHtml($oldName);
        $newName   = $this->normalizeHtml($newName);
        $oldAnswer = $this->normalizeHtml($oldAnswer);
        $newAnswer = $this->normalizeHtml($newAnswer);

        $titleDiff   = (new Diff($oldName, $newName))->build();
        $contentDiff = (new Diff($oldAnswer, $newAnswer))->build();

        return new JsonResponse([
            'titleDiff'   => $titleDiff,
            'contentDiff' => $contentDiff,
        ]);
    }

    private function normalizeHtml(string $html): string
    {
        // Replace UTF-8 non-breaking space (0xC2 0xA0) and HTML entity
        return str_replace(["\xC2\xA0", '&nbsp;'], ' ', $html);
    }
}
