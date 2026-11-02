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

namespace tests\unit\Glpi\Knowbase\Aside;

use Glpi\Knowbase\Aside\SearchResult;
use Glpi\Knowbase\Aside\SearchResultsBuilder;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_User;
use KnowbaseItemTranslation;
use PHPUnit\Framework\Attributes\DataProvider;

final class SearchResultsBuilderTest extends DbTestCase
{
    public function testResultCarriesWhatARowShows(): void
    {
        $this->login();

        $article = $this->createItem(KnowbaseItem::class, [
            'name'         => 'Reset a password zqxjtokenone',
            'answer'       => '<p>Open the user form</p><p>Then click reset</p>',
            'illustration' => 'antivirus',
        ]);

        $results = (new SearchResultsBuilder())->build('zqxjtokenone')->getResults();

        $this->assertCount(1, $results);
        $result = $results[0];
        $this->assertInstanceOf(SearchResult::class, $result);
        $this->assertSame($article->getID(), $result->id);
        $this->assertSame('Reset a password zqxjtokenone', $result->title);
        $this->assertSame('antivirus', $result->illustration);
        $this->assertSame(KnowbaseItem::getFormURLWithID($article->getID()), $result->link);
        $this->assertSame('Open the user form Then click reset', $result->excerpt);
        $this->assertFalse($result->is_current);
    }

    public function testSearchWithoutMatchReturnsNothing(): void
    {
        $this->login();

        $this->createItem(KnowbaseItem::class, [
            'name'   => 'Reset a password zqxjtokentwo',
            'answer' => '<p>Content</p>',
        ]);

        $page = (new SearchResultsBuilder())->build('zqxjtokennomatch');

        $this->assertSame([], $page->getResults());
        $this->assertNull($page->getNextOffset());
    }

    /**
     * An excerpt is plain text: the markup of the answer never reaches the
     * browser, and neither does the part of it a row cannot show.
     */
    public function testExcerptIsPlainTextAndCapped(): void
    {
        $this->login();

        $this->createItem(KnowbaseItem::class, [
            'name'   => 'Long article zqxjtokenthree',
            'answer' => '<p>' . str_repeat('word ', 200) . '</p>',
        ]);

        $excerpt = (new SearchResultsBuilder())->build('zqxjtokenthree')->getResults()[0]->excerpt;

        $this->assertStringNotContainsString('<', $excerpt);
        $this->assertStringStartsWith('word word', $excerpt);

        // Cut short of the cap, on a word boundary, and marked as cut.
        $this->assertLessThanOrEqual(301, mb_strlen($excerpt));
        $this->assertStringEndsWith('word…', $excerpt);
    }

    /**
     * A word longer than the cap has no boundary to cut on, and must not be
     * thrown away down to the end of the previous one.
     */
    public function testExcerptOfAnUnbrokenRunOfCharactersIsCutInIt(): void
    {
        $this->login();

        $this->createItem(KnowbaseItem::class, [
            'name'   => 'Long article zqxjtokeneight',
            'answer' => '<p>' . str_repeat('x', 400) . '</p>',
        ]);

        $excerpt = (new SearchResultsBuilder())->build('zqxjtokeneight')->getResults()[0]->excerpt;

        $this->assertSame(str_repeat('x', 300) . '…', $excerpt);
    }

    public static function embeddedContentProvider(): iterable
    {
        yield 'an image, as its alt text' => [
            '<p>Before</p><p><img src="picture.png" alt="A screenshot"></p><p>After</p>',
            'Before After',
        ];
        yield 'a table, as its cells one after the other' => [
            '<p>Intro</p><table><tr><td>A1</td><td>B1</td></tr></table><p>Outro</p>',
            'Intro Outro',
        ];
        yield 'a table nested in another one' => [
            '<p>Intro</p><table><tr><td><table><tr><td>Inner</td></tr></table></td></tr></table><p>Outro</p>',
            'Intro Outro',
        ];
        yield 'a video, as the URL of its player' => [
            '<p>Watch</p><div data-video-provider="youtube" data-video-id="dQw4w9WgXcQ"></div><p>End</p>',
            'Watch End',
        ];
        yield 'a figure, with its caption' => [
            '<p>Before</p><figure class="image"><img src="picture.png" alt="Alt">'
                . '<figcaption>The caption</figcaption></figure><p>After</p>',
            'Before After',
        ];
        yield 'an iframe' => [
            '<p>Before</p><iframe src="https://example.org/embed"></iframe><p>After</p>',
            'Before After',
        ];
        yield 'a horizontal rule, as a row of dashes' => [
            '<p>Before</p><hr><p>After</p>',
            'Before After',
        ];
    }

    /**
     * Content an excerpt cannot show is left out of it, rather than padding it
     * with what a text renderer makes of it.
     */
    #[DataProvider('embeddedContentProvider')]
    public function testExcerptLeavesOutEmbeddedContent(string $answer, string $expected): void
    {
        $this->login();

        $this->createItem(
            KnowbaseItem::class,
            [
                'name'   => 'Article zqxjtokennine',
                'answer' => $answer,
            ],
            skip_fields: ['answer'],
        );

        $results = (new SearchResultsBuilder())->build('zqxjtokennine')->getResults();

        $this->assertCount(1, $results);
        $this->assertSame($expected, $results[0]->excerpt);
    }

    public static function proseProvider(): iterable
    {
        yield 'paragraphs are kept apart' => [
            '<p>First line</p><p>Second line</p>',
            'First line Second line',
        ];
        yield 'a heading is not shouted' => [
            '<h1>Title</h1><p>Body text</p>',
            'Title Body text',
        ];
        yield 'a list loses its bullets' => [
            '<p>Steps</p><ul><li>One</li><li>Two</li></ul>',
            'Steps One Two',
        ];
        yield 'a quote loses its marker' => [
            '<blockquote><p>Quoted</p></blockquote><p>Body</p>',
            'Quoted Body',
        ];
        yield 'a link keeps its text and drops its URL' => [
            '<p>See <a href="https://example.org/a/long/url">this page</a> now</p>',
            'See this page now',
        ];
        yield 'entities are decoded' => [
            '<p>Caf&eacute; &amp; croissant</p>',
            'Café & croissant',
        ];
    }

    /**
     * What the article actually says survives as a running sentence.
     */
    #[DataProvider('proseProvider')]
    public function testExcerptReadsAsFlowingText(string $answer, string $expected): void
    {
        $this->login();

        $this->createItem(
            KnowbaseItem::class,
            [
                'name'   => 'Article zqxjtokenten',
                'answer' => $answer,
            ],
            skip_fields: ['answer'],
        );

        $results = (new SearchResultsBuilder())->build('zqxjtokenten')->getResults();

        $this->assertCount(1, $results);
        $this->assertSame($expected, $results[0]->excerpt);
    }

    public function testArticleWithoutContentHasNoExcerpt(): void
    {
        $this->login();

        $this->createItem(KnowbaseItem::class, [
            'name'   => 'Empty article zqxjtokenfour',
            'answer' => '',
        ]);

        $results = (new SearchResultsBuilder())->build('zqxjtokenfour')->getResults();

        $this->assertCount(1, $results);
        $this->assertSame('', $results[0]->excerpt);
    }

    /**
     * The results are cut into pages, and every match is on exactly one of
     * them: a reader scrolling through the pages must not be shown the same
     * article twice, nor miss one.
     */
    public function testResultsAreCutIntoPages(): void
    {
        $this->login();

        $page_size = SearchResultsBuilder::PAGE_SIZE;
        $extra     = 3;

        $expected_ids = [];
        for ($i = 0; $i < $page_size + $extra; $i++) {
            $expected_ids[] = $this->createItem(KnowbaseItem::class, [
                'name'   => "Article $i zqxjtokenfive",
                'answer' => '<p>Content</p>',
            ])->getID();
        }

        $builder = new SearchResultsBuilder();

        $first = $builder->build('zqxjtokenfive');
        $this->assertCount($page_size, $first->getResults());
        $this->assertSame($page_size, $first->getNextOffset());

        $second = $builder->build('zqxjtokenfive', $first->getNextOffset());
        $this->assertCount($extra, $second->getResults());
        $this->assertNull($second->getNextOffset());

        $returned_ids = array_merge(
            array_column($first->getResults(), 'id'),
            array_column($second->getResults(), 'id'),
        );
        $this->assertSameSize($returned_ids, array_unique($returned_ids));
        $this->assertEqualsCanonicalizing($expected_ids, $returned_ids);
    }

    /**
     * The results are loaded in two queries: the ranking one, then the columns
     * a row displays. The second must not lose the order of the first.
     */
    public function testResultsKeepTheirRelevanceOrder(): void
    {
        $this->login();

        // Identical content: the scores tie, so the order is the `id DESC`
        // fallback and is known ahead of time.
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $ids[] = $this->createItem(KnowbaseItem::class, [
                'name'   => 'Tied article zqxjtokenten',
                'answer' => '<p>Content</p>',
            ])->getID();
        }
        rsort($ids);

        $results = (new SearchResultsBuilder())->build('zqxjtokenten')->getResults();

        $this->assertSame($ids, array_column($results, 'id'));
    }

    public function testResultShowsTheTranslationTheReaderSearchedThrough(): void
    {
        $this->login();

        $article = $this->createItem(KnowbaseItem::class, [
            'name'   => 'Original title zqxjtokeneleven',
            'answer' => '<p>Original content</p>',
        ]);
        $this->createItem(KnowbaseItemTranslation::class, [
            'knowbaseitems_id' => $article->getID(),
            'language'         => $_SESSION['glpilanguage'],
            'name'             => 'Translated title zqxjtokentwelve',
            'answer'           => '<p>Translated content</p>',
        ]);

        // The token only exists in the translation, so a result proves the
        // translated columns are both searched and read back.
        $results = (new SearchResultsBuilder())->build('zqxjtokentwelve')->getResults();

        $this->assertCount(1, $results);
        $this->assertSame($article->getID(), $results[0]->id);
        $this->assertSame('Translated title zqxjtokentwelve', $results[0]->title);
        $this->assertSame('Translated content', $results[0]->excerpt);
    }

    public function testCurrentIdMarksMatchingResult(): void
    {
        $this->login();

        $other = $this->createItem(KnowbaseItem::class, [
            'name'   => 'Other article zqxjtokensix',
            'answer' => '<p>Content</p>',
        ]);
        $current = $this->createItem(KnowbaseItem::class, [
            'name'   => 'Current article zqxjtokensix',
            'answer' => '<p>Content</p>',
        ]);

        $results = (new SearchResultsBuilder($current->getID()))
            ->build('zqxjtokensix')
            ->getResults();

        $by_id = array_column($results, null, 'id');
        $this->assertTrue($by_id[$current->getID()]->is_current);
        $this->assertFalse($by_id[$other->getID()]->is_current);
    }

    /**
     * The search sees exactly what the reader is allowed to see.
     */
    public function testResultsRespectVisibility(): void
    {
        // Authored by another user, so the "author" visibility bypass never
        // makes the article visible to the restricted user below.
        $glpi_user = getItemByTypeName('User', 'glpi', true);
        $this->login();

        $article = $this->createItem(KnowbaseItem::class, [
            'name'     => 'Restricted article zqxjtokenseven',
            'answer'   => '<p>Content</p>',
            'users_id' => $glpi_user,
        ]);

        // Without any visibility grant, the article is not searchable.
        $this->login('normal', 'normal');
        $this->assertSame(
            [],
            (new SearchResultsBuilder())->build('zqxjtokenseven')->getResults(),
        );

        // Granted, the very same search returns it.
        $this->login();
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $article->getID(),
            'users_id'         => getItemByTypeName('User', 'normal', true),
        ]);

        $this->login('normal', 'normal');
        $results = (new SearchResultsBuilder())->build('zqxjtokenseven')->getResults();
        $this->assertSame(
            [$article->getID()],
            array_column($results, 'id'),
        );
    }
}
