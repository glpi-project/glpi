<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\RichText;

use DOMDocument;
use DOMElement;
use DOMNode;
use Symfony\Component\HtmlSanitizer\Parser\MastermindsParser;
use Symfony\Component\HtmlSanitizer\Parser\ParserInterface;

/**
 * HTML parser used by the rich text sanitizer.
 *
 * The Masterminds parser does not implement the HTML5 table insertion modes, so misplaced
 * elements (e.g. a `<table>` directly inside another `<table>` or `<tr>`, as produced by some
 * mail clients) are kept where they are. Once serialized, browsers will close the parent table
 * early and the remaining `</td>`/`</table>` end tags will close the tables in which the content
 * is embedded (e.g. the search results list).
 * Wrapping these misplaced elements in a table cell produces a structure that browsers will parse
 * as it is.
 * For the same reason, table elements that are not inside a `<table>` (e.g. `<div><td>x</td></div>`)
 * are removed, keeping their content, as browsers ignore these tags.
 */
final class HtmlParser implements ParserInterface
{
    private const ALLOWED_CHILDREN = [
        'table' => ['caption', 'colgroup', 'col', 'thead', 'tbody', 'tfoot', 'tr'],
        'thead' => ['tr'],
        'tbody' => ['tr'],
        'tfoot' => ['tr'],
        'tr'    => ['td', 'th'],
    ];

    private const TABLE_ELEMENTS = ['caption', 'colgroup', 'col', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th'];

    public function parse(string $html): ?DOMNode
    {
        $fragment = (new MastermindsParser(['disable_html_ns' => true]))->parse($html);
        if ($fragment?->ownerDocument !== null) {
            $this->fixTableStructure($fragment->ownerDocument, $fragment);
        }
        return $fragment;
    }

    private function fixTableStructure(DOMDocument $document, DOMNode $node, bool $in_table = false): void
    {
        $in_table = $in_table || strtolower($node->nodeName) === 'table';
        // Table elements outside a table are removed, so their content must not be wrapped.
        $allowed = $in_table ? (self::ALLOWED_CHILDREN[strtolower($node->nodeName)] ?? null) : null;
        $implicit_row = null;

        foreach (iterator_to_array($node->childNodes) as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            $this->fixTableStructure($document, $child, $in_table);

            if (!$in_table && in_array(strtolower($child->nodeName), self::TABLE_ELEMENTS, true)) {
                // Table element outside a table: keep only its content.
                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            if ($allowed === null || in_array(strtolower($child->nodeName), $allowed, true)) {
                $implicit_row = null;
                continue;
            }

            if (in_array(strtolower($child->nodeName), ['td', 'th'], true)) {
                // Cells outside a row: move consecutive cells into a single row, as browsers do.
                if ($implicit_row === null) {
                    $implicit_row = $document->createElement('tr');
                    $node->insertBefore($implicit_row, $child);
                }
                $implicit_row->appendChild($child);
                continue;
            }
            $implicit_row = null;

            $cell = $wrapper = $document->createElement('td');
            if (strtolower($node->nodeName) !== 'tr') {
                $wrapper = $document->createElement('tr');
                $wrapper->appendChild($cell);
            }
            $node->replaceChild($wrapper, $child);
            $cell->appendChild($child);
        }
    }
}
