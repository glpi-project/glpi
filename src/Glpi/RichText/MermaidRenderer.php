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

namespace Glpi\RichText;

use function Safe\preg_replace_callback;

/**
 * Fallbacks for the Mermaid diagrams of the KB articles. They are stored as
 * `<pre><code class="language-mermaid">` blocks and drawn by the browser.
 */
final class MermaidRenderer
{
    private const BLOCK_PATTERN = '#<pre\b[^>]*>\s*<code\b[^>]*\bclass="[^"]*\blanguage-mermaid\b[^"]*"[^>]*>.*?</code>\s*</pre>#is';

    /**
     * HTML fallback for callers that paste KB content into a rich-text editor
     * (e.g. the "Use as solution" workflow), where the diagram is not drawn:
     * a link to the article replaces each diagram.
     */
    public function renderAllAsLink(string $html, string $article_url): string
    {
        if (!str_contains($html, 'language-mermaid')) {
            return $html;
        }

        $link = sprintf(
            '<p><a href="%s">%s</a></p>',
            htmlescape($article_url),
            htmlescape(__('See the diagram in the article'))
        );

        return preg_replace_callback(self::BLOCK_PATTERN, static fn(): string => $link, $html);
    }
}
