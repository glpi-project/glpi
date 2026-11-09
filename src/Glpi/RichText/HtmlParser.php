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

    public function parse(string $html): ?DOMNode
    {
        $fragment = (new MastermindsParser())->parse($html);
        if ($fragment?->ownerDocument !== null) {
            $this->wrapMisplacedTableContent($fragment->ownerDocument, $fragment);
        }
        return $fragment;
    }

    private function wrapMisplacedTableContent(DOMDocument $document, DOMNode $node): void
    {
        $allowed = self::ALLOWED_CHILDREN[strtolower($node->nodeName)] ?? null;

        foreach (iterator_to_array($node->childNodes) as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            $this->wrapMisplacedTableContent($document, $child);

            if ($allowed === null || in_array(strtolower($child->nodeName), $allowed, true)) {
                continue;
            }

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
