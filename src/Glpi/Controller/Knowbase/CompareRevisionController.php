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
        $old_name = $revision->fields['name'];
        $new_name = $kb->fields['name'];

        $old_answer = RichText::getEnhancedHtml($revision->fields['answer'], ['text_maxsize' => 0]);
        $new_answer = RichText::getEnhancedHtml($kb->fields['answer'], ['text_maxsize' => 0]);

        // Normalize non-breaking spaces (same as the former JS normalizeHtml)
        $old_name   = $this->normalizeHtml($old_name);
        $new_name   = $this->normalizeHtml($new_name);
        $old_answer = $this->normalizeHtml($old_answer);
        $new_answer = $this->normalizeHtml($new_answer);

        $title_diff   = (new Diff($old_name, $new_name))->build();
        $content_diff = (new Diff($old_answer, $new_answer))->build();

        return new JsonResponse([
            'title_diff'   => $title_diff,
            'content_diff' => $content_diff,
        ]);
    }

    private function normalizeHtml(string $html): string
    {
        // Replace UTF-8 non-breaking space (0xC2 0xA0) and HTML entity
        return str_replace(["\xC2\xA0", '&nbsp;'], ' ', $html);
    }
}
