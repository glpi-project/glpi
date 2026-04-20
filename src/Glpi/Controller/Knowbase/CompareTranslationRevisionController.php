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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\BadRequestHttpException;
use KnowbaseItem;
use KnowbaseItem_Revision;
use KnowbaseItemTranslation;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CompareTranslationRevisionController extends AbstractCompareController
{
    #[Route(
        "/Knowbase/{id}/CompareTranslationRevision/{revision_id}",
        name: "knowbase_article_compare_translation_revision",
        methods: ["GET"],
        requirements: [
            'id' => '\d+',
            'revision_id' => '\d+',
        ]
    )]
    public function __invoke(int $id, int $revision_id): Response
    {
        // Load target KB
        $kb = KnowbaseItem::getById($id);
        if (!$kb) {
            throw new BadRequestHttpException();
        }

        // Make sure the user is able to update the current KB
        if (!$kb->can($id, UPDATE)) {
            throw new AccessDeniedHttpException();
        }

        // Load target revision, making sure it match the parent KB
        $revision = KnowbaseItem_Revision::getById($revision_id);
        if (!$revision || (int) $revision->fields['knowbaseitems_id'] !== $id) {
            throw new BadRequestHttpException();
        }

        // Validate that this revision is targeting a translation
        $language = $revision->fields['language'];
        if ($language === '') {
            throw new BadRequestHttpException();
        }

        // Load the target translation
        $translation = new KnowbaseItemTranslation();
        $exists = $translation->getFromDBByCrit([
            'knowbaseitems_id' => $id,
            'language' => $language,
        ]);

        return $this->compare(
            old_answer: $revision->fields['answer'],

            // Current answer will be empty in case the translation was
            // deleted.
            new_answer: $exists ? $translation->fields['answer'] : '',
        );
    }
}
