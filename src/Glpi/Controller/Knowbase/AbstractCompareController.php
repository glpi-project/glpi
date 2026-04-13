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
use Glpi\RichText\RichText;
use Ssddanbrown\HtmlDiff\Diff;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class AbstractCompareController extends AbstractController
{
    protected function compare(
        string $old_answer,
        string $new_answer,
    ): JsonResponse {
        // Load full rich text content
        $rich_text_options = ['text_maxsize' => 0];
        $old_answer = RichText::getEnhancedHtml($old_answer, $rich_text_options);
        $new_answer = RichText::getEnhancedHtml($new_answer, $rich_text_options);

        // Normalize content
        $old_answer = $this->normalizeHtml($old_answer);
        $new_answer = $this->normalizeHtml($new_answer);

        $content_diff = (new Diff($old_answer, $new_answer))->build();
        return new JsonResponse([
            'content_diff' => $content_diff,
        ]);
    }

    private function normalizeHtml(string $html): string
    {
        // Replace UTF-8 non-breaking space (0xC2 0xA0) and HTML entity
        return str_replace(["\xC2\xA0", '&nbsp;'], ' ', $html);
    }
}
