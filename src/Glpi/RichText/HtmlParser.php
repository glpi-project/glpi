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

use Dom\HTMLDocument;
use Dom\HTMLElement;
use Dom\Node;
use Dom\Text;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Parser\MastermindsParser;
use Symfony\Component\HtmlSanitizer\Parser\NativeParser;
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
 * For the same reason, table elements that are not in their expected parent (e.g. `<div><td>x</td></div>`)
 * are removed, keeping their content, as browsers ignore these tags.
 */
final class HtmlParser implements ParserInterface
{
    /**
     * Elements that browsers accept as children of each table container.
     */
    private const ALLOWED_CHILDREN = [
        'table' => ['caption', 'colgroup', 'col', 'thead', 'tbody', 'tfoot', 'tr', 'script', 'style', 'template'],
        'thead' => ['tr', 'script', 'style', 'template'],
        'tbody' => ['tr', 'script', 'style', 'template'],
        'tfoot' => ['tr', 'script', 'style', 'template'],
        'tr'    => ['td', 'th', 'script', 'style', 'template'],
    ];

    /**
     * Parents in which each table element is kept. Cells directly inside a table or a row group
     * are moved into an implicit row.
     */
    private const VALID_PARENTS = [
        'caption'  => ['table'],
        'colgroup' => ['table'],
        'col'      => ['table', 'colgroup'],
        'thead'    => ['table'],
        'tbody'    => ['table'],
        'tfoot'    => ['table'],
        'tr'       => ['table', 'thead', 'tbody', 'tfoot'],
        'td'       => ['table', 'thead', 'tbody', 'tfoot', 'tr'],
        'th'       => ['table', 'thead', 'tbody', 'tfoot', 'tr'],
    ];

    public function __construct(private HtmlSanitizerConfig $config) {}

    public function parse(string $html): Node|DOMNode|null
    {
        // Without the HTML namespace, elements moved out of their parent would be renamed (e.g. `default:table`) by libxml
        // and then dropped by the sanitizer.
        $parser = \PHP_VERSION_ID < 80400
            ? new MastermindsParser(['disable_html_ns' => true])
            : new NativeParser();

        $fragment = $parser->parse($html);
        if ($fragment?->ownerDocument !== null) {
            $this->fixTableStructure($fragment->ownerDocument, $fragment);
        }
        return $fragment;
    }

    private function fixTableStructure(DOMDocument|HTMLDocument $document, DOMNode|HTMLElement $node): void
    {
        $node_name = strtolower($node->nodeName);
        $allowed = self::ALLOWED_CHILDREN[$node_name] ?? null;
        $implicit_row = null;

        $child = $node->firstChild;
        while ($child !== null) {
            $next = $child->nextSibling;
            if (!$child instanceof DOMElement && !$child instanceof HTMLElement) {
                $child = $next;
                continue;
            }
            $child_name = strtolower($child->nodeName);

            // Element that is not accepted at this place of a table (cells are moved into an implicit row instead).
            $misplaced_in_table = $allowed !== null && !in_array($child_name, $allowed, true)
                && $child_name !== 'td' && $child_name !== 'th';

            if (
                (isset(self::VALID_PARENTS[$child_name]) && !in_array($node_name, self::VALID_PARENTS[$child_name], true))
                || ($misplaced_in_table && isset($this->config->getBlockedElements()[$child_name]))
            ) {
                // Misplaced table element, or element whose tag will be removed by the sanitizer:
                // keep only its content, which is processed next.
                $next = $child->firstChild ?? $next;
                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                $child = $next;
                continue;
            }

            if ($misplaced_in_table && !isset($this->config->getAllowedElements()[$child_name])) {
                // Element that will be dropped by the sanitizer: remove it now, so it does not leave an empty cell.
                $node->removeChild($child);
                $child = $next;
                continue;
            }

            if ($child_name === 'colgroup') {
                // Browsers close a column group at its first child that is not a column (e.g. a row when `</colgroup>`
                // is omitted) and process it and the following content in the table: move them after the column group.
                // Scripts and styles are dropped by the sanitizer, so they must not close the column group.
                $moved = $child->firstChild;
                while (
                    $moved !== null
                    && (
                        ($moved instanceof DOMElement || $moved instanceof HTMLElement)
                        ? in_array(strtolower($moved->nodeName), ['col', 'template', 'script', 'style'], true)
                        : (!$moved instanceof DOMText && !$moved instanceof Text) || trim($moved->data, " \t\n\f\r") === ''
                    )
                ) {
                    $moved = $moved->nextSibling;
                }
                while ($moved !== null) {
                    $following = $moved->nextSibling;
                    $node->insertBefore($moved, $next);
                    $moved = $following;
                }
                $next = $child->nextSibling;
            }

            if ($allowed === null || in_array($child_name, $allowed, true)) {
                if ($child_name !== 'script' && $child_name !== 'style') {
                    // Scripts and styles are dropped by the sanitizer, so they must not split an implicit row.
                    $implicit_row = null;
                }
            } elseif ($child_name === 'td' || $child_name === 'th') {
                // Cells outside a row: move consecutive cells into a single row, as browsers do.
                if ($implicit_row === null) {
                    $implicit_row = $document->createElement('tr');
                    $node->insertBefore($implicit_row, $child);
                }
                $implicit_row->appendChild($child);
            } else {
                $implicit_row = null;
                $cell = $wrapper = $document->createElement('td');
                if ($node_name !== 'tr') {
                    $wrapper = $document->createElement('tr');
                    $wrapper->appendChild($cell);
                }
                $node->replaceChild($wrapper, $child);
                $cell->appendChild($child);
            }

            $this->fixTableStructure($document, $child);
            $child = $next;
        }
    }
}
