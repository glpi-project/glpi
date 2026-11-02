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

namespace Glpi\Knowbase\Aside;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Glpi\RichText\RichText;
use KnowbaseItem;
use KnowbaseItemTranslation;

use function Safe\preg_match;
use function Safe\preg_replace;

/**
 * Builds one page of the aside search results.
 *
 * Unlike `Builder`, which loads the whole article hierarchy to render the
 * aside tree, the search results are a flat list ordered by relevance.
 */
final class SearchResultsBuilder
{
    /**
     * Results per page.
     */
    public const int PAGE_SIZE = 50;

    /**
     * Excerpts are cut at this many characters.
     */
    private const int EXCERPT_MAX_CHARS = 300;

    /**
     * Article columns a result row needs.
     */
    private const array SELECTED_COLUMNS = [
        'glpi_knowbaseitems.id',
        'glpi_knowbaseitems.name',
        'glpi_knowbaseitems.answer',
        'glpi_knowbaseitems.illustration',
    ];

    /**
     * Elements dropped from an excerpt.
     */
    private const array EMBEDDED_TAGS = [
        'img',
        'table',
        'hr',
        'figure',
        'iframe',
        'video',
        'audio',
        'svg',
        'object',
        'embed',
    ];

    /**
     * Video placeholders, written by the editor as an empty div.
     */
    private const string VIDEO_PLACEHOLDER_ATTRIBUTE = 'data-video-provider';

    public function __construct(private readonly int $current_id = 0) {}

    /**
     * @param string $contains Search terms, as typed by the reader.
     * @param int    $offset   Rank of the first result to return.
     */
    public function build(string $contains, int $offset = 0): SearchResults
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = KnowbaseItem::getListRequest(['contains' => $contains], 'search');
        $criteria['SELECT'] = $this->narrowSelect($criteria['SELECT']);

        // Add fallback to ID for sorting out tied content.
        $criteria['ORDERBY'] = array_merge(
            (array) ($criteria['ORDERBY'] ?? []),
            [KnowbaseItem::getTableField('id') . ' DESC'],
        );

        // Offset/limit
        $criteria['LIMIT'] = self::PAGE_SIZE + 1;
        $criteria['START'] = $offset;

        $ids = [];
        foreach ($DB->request($criteria) as $row) {
            $ids[] = (int) $row['id'];
        }
        $has_next_page = count($ids) > self::PAGE_SIZE;

        $results = [];
        foreach ($this->loadRows(array_slice($ids, 0, self::PAGE_SIZE), $criteria) as $row) {
            $results[] = $this->buildResult($row);
        }

        return new SearchResults(
            $results,
            $has_next_page ? $offset + self::PAGE_SIZE : null,
        );
    }

    /**
     * Reduce the select `getListRequest()` builds to what ranking the matches
     * needs: the id, and the computed columns the `ORDERBY` and `HAVING` use.
     *
     * The `GROUP BY` makes MySQL sort the whole result set in a temporary table
     * before the `LIMIT` applies, so every column left here is carried for each
     * match instead of for the page. The article columns a row displays are
     * loaded by `loadRows()` once the page is known.
     *
     * @param array<int, mixed> $select
     *
     * @return array<int, mixed>
     */
    private function narrowSelect(array $select): array
    {
        $narrowed = [];
        foreach ($select as $column) {
            if (is_string($column)) {
                // Article and translation columns, `loadRows()` handles them.
                continue;
            }
            // `visibility_count` and the `SCORE` the ORDERBY sorts on.
            $narrowed[] = $column;
        }
        array_unshift($narrowed, KnowbaseItem::getTableField('id'));

        return $narrowed;
    }

    /**
     * Article columns a result row needs, for one page worth of ids.
     *
     * @param int[]                $ids      Page ids, in the order they rank.
     * @param array<string, mixed> $criteria Criteria the page was ranked with.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadRows(array $ids, array $criteria): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        if ($ids === []) {
            return [];
        }

        $row_criteria = [
            'SELECT' => self::SELECTED_COLUMNS,
            'FROM'   => KnowbaseItem::getTable(),
            'WHERE'  => [KnowbaseItem::getTableField('id') => $ids],
        ];

        // `getListRequest()` joins the translations only when there are some,
        // and a result must show the translation the reader searched through.
        $translations_table = KnowbaseItemTranslation::getTable();
        if (isset($criteria['LEFT JOIN'][$translations_table])) {
            $row_criteria['LEFT JOIN'] = [
                $translations_table => $criteria['LEFT JOIN'][$translations_table],
            ];
            $row_criteria['SELECT'][] = KnowbaseItemTranslation::getTableField('name') . ' AS transname';
            $row_criteria['SELECT'][] = KnowbaseItemTranslation::getTableField('answer') . ' AS transanswer';
        }

        $rows = [];
        foreach ($DB->request($row_criteria) as $row) {
            $rows[(int) $row['id']] = $row;
        }

        // `IN` returns the rows in whatever order it likes, restore the ranking.
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($rows[$id])) {
                $ordered[] = $rows[$id];
            }
        }

        return $ordered;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function buildResult(array $row): SearchResult
    {
        $id = (int) $row['id'];

        // The search matches the translated columns too, so a result must show
        // the reader the translation they searched through, when there is one.
        $title  = ($row['transname'] ?? '') !== '' ? (string) $row['transname'] : (string) $row['name'];
        $answer = ($row['transanswer'] ?? '') !== '' ? (string) $row['transanswer'] : (string) $row['answer'];

        return new SearchResult(
            id: $id,
            title: $title,
            illustration: (string) ($row['illustration'] ?? ''),
            link: KnowbaseItem::getFormURLWithID($id),
            excerpt: $this->buildExcerpt($answer),
            is_current: $this->current_id > 0 && $id === $this->current_id,
        );
    }

    /**
     * Plain text preview of an article's content, capped at
     * `EXCERPT_MAX_CHARS`.
     */
    private function buildExcerpt(string $answer): string
    {
        if (trim($answer) === '') {
            return '';
        }

        $answer = $this->stripEmbeddedContent($answer);

        // Get raw text
        $text = RichText::getTextFromHtml(
            $answer,
            compact: true,       // an excerpt has no room for the URL of a link
            preserve_case: true, // headings would come out shouting otherwise
        );

        // Format list items and quotes
        $text = preg_replace('/^\h*(?:\*\h+|>+\h*)/mu', '', $text);

        // Remove spacing
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return $this->cap($text);
    }

    /**
     * Drop what an excerpt cannot carry: an image comes out as its alt text, a
     * video as the URL of its player, a table as its cells one after the other.
     */
    private function stripEmbeddedContent(string $html): string
    {
        // Parsing the answer is the most expensive thing here, 50 times a page.
        $tags = implode('|', self::EMBEDDED_TAGS);
        $pattern = '/<(' . $tags . ')[\s>\/]|' . self::VIDEO_PLACEHOLDER_ATTRIBUTE . '/i';
        if (preg_match($pattern, $html) !== 1) {
            return $html;
        }

        $document = new DOMDocument();

        // Wrapped: the parser guesses the encoding wrong without a document.
        $previous_state = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous_state);

        $body = $document->getElementsByTagName('body')->item(0);
        if (!$loaded || $body === null) {
            // Unparseable markup: a noisy excerpt beats no excerpt.
            return $html;
        }

        $xpath = new DOMXPath($document);
        $query = '//' . implode(' | //', self::EMBEDDED_TAGS)
            . ' | //*[@' . self::VIDEO_PLACEHOLDER_ATTRIBUTE . ']';
        $nodes = $xpath->query($query);
        if ($nodes === false) {
            return $html;
        }

        // A query result is a snapshot, so removing nested nodes is safe.
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $node->parentNode?->removeChild($node);
            }
        }

        // Only the article's own content: not the wrapper added above.
        $stripped = '';
        foreach ($body->childNodes as $child) {
            $stripped .= $document->saveHTML($child);
        }

        return $stripped;
    }

    /**
     * Cut the excerpt down to `EXCERPT_MAX_CHARS`, on a word boundary.
     */
    private function cap(string $text): string
    {
        if (mb_strlen($text) <= self::EXCERPT_MAX_CHARS) {
            return $text;
        }

        $cut = mb_substr($text, 0, self::EXCERPT_MAX_CHARS);

        // An unbroken run longer than the cap (a URL) has no boundary to use.
        $boundary = mb_strrpos($cut, ' ');
        if ($boundary !== false && $boundary >= (int) (self::EXCERPT_MAX_CHARS / 2)) {
            $cut = mb_substr($cut, 0, $boundary);
        }

        // The rows mark their own cut when the excerpt overflows them; this one
        // is for the excerpt that fits.
        return rtrim($cut) . '…';
    }
}
