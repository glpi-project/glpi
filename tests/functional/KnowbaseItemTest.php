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

namespace test\units;

use Glpi\DBAL\QueryExpression;
use Glpi\ShareToken;
use Glpi\Tests\DbTestCase;
use InvalidArgumentException;
use KnowbaseItem;
use KnowbaseItem_User;
use PHPUnit\Framework\Attributes\DataProvider;
use Session;
use Symfony\Component\DomCrawler\Crawler;

/* Test for inc/knowbaseitem.class.php */

class KnowbaseItemTest extends DbTestCase
{
    public function testGetTypeName()
    {
        $expected = 'Knowledge base';
        $this->assertSame($expected, KnowbaseItem::getTypeName(1));

        $expected = 'Knowledge base';
        $this->assertSame($expected, KnowbaseItem::getTypeName(0));
        $this->assertSame($expected, KnowbaseItem::getTypeName(2));
        $this->assertSame($expected, KnowbaseItem::getTypeName(10));
    }

    public function testCleanDBonPurge()
    {
        global $DB;

        $users_id = getItemByTypeName('User', TU_USER, true);

        $kb = new KnowbaseItem();
        $this->assertGreaterThan(
            0,
            (int) $kb->add([
                'name'     => 'Test to remove',
                'answer'   => 'An KB entry to remove',
                'is_faq'   => 0,
                'users_id' => $users_id,
                'date'     => '2017-10-06 12:27:48',
            ])
        );

        //add some comments
        $comment = new \KnowbaseItem_Comment();
        $input = [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => $users_id,
        ];

        $id = 0;
        for ($i = 0; $i < 4; ++$i) {
            $input['comment'] = "Comment $i";
            $this->assertGreaterThan(
                $id,
                (int) $comment->add($input)
            );
            $id = (int) $comment->getID();
        }

        //change KB entry
        $this->assertTrue(
            $kb->update([
                'id'     => $kb->getID(),
                'answer' => 'Answer has changed',
            ])
        );

        //add an user
        $kbu = new KnowbaseItem_User();
        $this->assertGreaterThan(
            0,
            (int) $kbu->add([
                'knowbaseitems_id'   => $kb->getID(),
                'users_id'           => $users_id,
            ])
        );

        //add an entity
        $kbe = new \Entity_KnowbaseItem();
        $this->assertGreaterThan(
            0,
            (int) $kbe->add([
                'knowbaseitems_id'   => $kb->getID(),
                'entities_id'        => 0,
            ])
        );

        //add a group
        $group = new \Group();
        $this->assertGreaterThan(
            0,
            (int) $group->add([
                'name'   => 'KB group',
            ])
        );
        $kbg = new \Group_KnowbaseItem();
        $this->assertGreaterThan(
            0,
            (int) $kbg->add([
                'knowbaseitems_id'   => $kb->getID(),
                'groups_id'          => $group->getID(),
            ])
        );

        //add a profile
        $profiles_id = getItemByTypeName('Profile', 'Admin', true);
        $kbp = new \KnowbaseItem_Profile();
        $this->assertGreaterThan(
            0,
            (int) $kbp->add([
                'knowbaseitems_id'   => $kb->getID(),
                'profiles_id'        => $profiles_id,
            ])
        );

        //add an item
        $kbi = new \KnowbaseItem_Item();
        $tickets_id = getItemByTypeName('Ticket', '_ticket01', true);
        $this->assertGreaterThan(
            0,
            (int) $kbi->add([
                'knowbaseitems_id'   => $kb->getID(),
                'itemtype'           => 'Ticket',
                'items_id'           => $tickets_id,
            ])
        );

        $relations = [
            $comment->getTable(),
            \KnowbaseItem_Revision::getTable(),
            KnowbaseItem_User::getTable(),
            \Entity_KnowbaseItem::getTable(),
            \Group_KnowbaseItem::getTable(),
            \KnowbaseItem_Profile::getTable(),
            \KnowbaseItem_Item::getTable(),
        ];

        //check all relations have been created
        foreach ($relations as $relation) {
            $iterator = $DB->request([
                'FROM'   => $relation,
                'WHERE'  => ['knowbaseitems_id' => $kb->getID()],
            ]);
            $this->assertGreaterThan(0, count($iterator));
        }

        //remove KB entry
        $this->assertTrue(
            $kb->delete(['id' => $kb->getID()], true)
        );

        //check all relations has been removed
        foreach ($relations as $relation) {
            $iterator = $DB->request([
                'FROM'   => $relation,
                'WHERE'  => ['knowbaseitems_id' => $kb->getID()],
            ]);
            $this->assertSame(0, count($iterator));
        }
    }

    public function testScreenshotConvertedIntoDocument()
    {

        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

        // Test uploads for item creation
        $fpath = FIXTURE_DIR . '/uploads/foo.png';
        $fcontents = file_get_contents($fpath);
        $this->assertNotSame(false, $fcontents, 'Cannot read ' . $fpath);
        $base64Image = base64_encode($fcontents);
        $filename = '5e5e92ffd9bd91.11111111image_paste22222222.png';
        $users_id = getItemByTypeName('User', TU_USER, true);
        $instance = new KnowbaseItem();
        $input = [
            'name'     => 'Test to remove',
            'answer'   => <<<HTML
<p>Test with a ' (add)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1d1a1.00000000" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML,
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.00000000',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.11111111',
            ],
            'is_faq'   => 0,
            'users_id' => $users_id,
            'date'     => '2017-10-06 12:27:48',
        ];
        $fpath = FIXTURE_DIR . '/uploads/foo.png';
        $this->assertTrue(
            copy($fpath, GLPI_TMP_DIR . '/' . $filename),
            'Cannot copy ' . $fpath
        );
        $this->assertGreaterThan(0, $instance->add($input));
        $this->assertFalse($instance->isNewItem());
        $this->assertTrue($instance->getFromDB($instance->getId()));
        $expected = 'a href="/front/document.send.php?docid=';
        $this->assertStringContainsString($expected, $instance->fields['answer']);

        // Test uploads for item update
        $fpath = FIXTURE_DIR . '/uploads/bar.png';
        $fcontents = file_get_contents($fpath);
        $this->assertNotSame(false, $fcontents, 'Cannot read ' . $fpath);
        $base64Image = base64_encode($fcontents);
        $filename = '5e5e92ffd9bd91.44444444image_paste55555555.png';
        $tmpFilename = GLPI_TMP_DIR . '/' . $filename;
        file_put_contents($tmpFilename, base64_decode($base64Image));
        $success = $instance->update([
            'id'       => $instance->getID(),
            'answer'   => <<<HTML
<p>Test with a ' (update)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1ffff.33333333" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML,
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1ffff.33333333',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.44444444',
            ],
        ]);
        $this->assertTrue($success);
        $this->assertTrue($instance->getFromDB($instance->getId()));
        // Ensure there is an anchor to the uploaded document
        $expected = 'a href="/front/document.send.php?docid=';
        $this->assertStringContainsString($expected, $instance->fields['answer']);
    }

    public function testUploadDocuments()
    {

        $this->login(); // must be logged as Document_Item uses Session::getLoginUserID()

        // Test uploads for item creation
        $filename = '5e5e92ffd9bd91.11111111' . 'foo.txt';
        $instance = new KnowbaseItem();
        $input = [
            'name'    => 'a kb item',
            'answer' => 'testUploadDocuments',
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1ffff.00000000',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.11111111',
            ],
        ];
        $fpath = FIXTURE_DIR . '/uploads/foo.txt';
        $this->assertTrue(
            copy($fpath, GLPI_TMP_DIR . '/' . $filename),
            'Cannot copy ' . $fpath
        );
        $instance->add($input);
        $this->assertFalse($instance->isNewItem());
        $this->assertStringContainsString('testUploadDocuments', $instance->fields['answer']);
        $count = (new \DBUtils())->countElementsInTable(\Document_Item::getTable(), [
            'itemtype' => 'KnowbaseItem',
            'items_id' => $instance->getID(),
        ]);
        $this->assertEquals(1, $count);

        // Test uploads for item update (adds a 2nd document)
        $filename = '5e5e92ffd9bd91.44444444bar.txt';
        $fpath = FIXTURE_DIR . '/uploads/bar.txt';
        $this->assertTrue(
            copy($fpath, GLPI_TMP_DIR . '/' . $filename),
            'Cannot copy ' . $fpath
        );
        $success = $instance->update([
            'id' => $instance->getID(),
            'answer' => 'update testUploadDocuments',
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.33333333',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.44444444',
            ],
        ]);
        $this->assertTrue($success);
        $this->assertStringContainsString('update testUploadDocuments', $instance->fields['answer']);
        $count = (new \DBUtils())->countElementsInTable(\Document_Item::getTable(), [
            'itemtype' => 'KnowbaseItem',
            'items_id' => $instance->getID(),
        ]);
        $this->assertEquals(2, $count);
    }

    public function testGetForCategoryMatchesBrowseListing(): void
    {
        global $DB;

        $this->login('glpi', 'glpi');
        $author_id = (int) $_SESSION['glpiID'];

        $category = $this->createItem(\KnowbaseItemCategory::class, [
            'name'        => __FUNCTION__,
            'entities_id' => 0,
        ]);

        // Two articles in the category: one draft (author-only), one published
        // with a visibility entry for tech.
        $draft = $this->createItem(KnowbaseItem::class, [
            'name' => 'draft', 'answer' => '...', 'users_id' => $author_id, 'is_draft' => 1,
        ]);
        $this->createItem(\KnowbaseItem_KnowbaseItemCategory::class, [
            'knowbaseitems_id' => $draft->getID(),
            'knowbaseitemcategories_id' => $category->getID(),
        ]);

        $published = $this->createItem(KnowbaseItem::class, [
            'name' => 'published', 'answer' => '...', 'users_id' => $author_id, 'is_draft' => 0,
        ]);
        $this->createItem(\KnowbaseItem_KnowbaseItemCategory::class, [
            'knowbaseitems_id' => $published->getID(),
            'knowbaseitemcategories_id' => $category->getID(),
        ]);
        $tech_id = (int) getItemByTypeName('User', 'tech', true);
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $published->getID(),
            'users_id'         => $tech_id,
        ]);

        // Stranger (tech) — entitled only to the published one via visibility list.
        $this->login('tech', 'tech');
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | UPDATE;

        $actual = array_map('intval', KnowbaseItem::getForCategory($category->getID()));

        $reference = array_map(
            'intval',
            array_column(
                iterator_to_array($DB->request(
                    KnowbaseItem::getListRequest(
                        ['knowbaseitemcategories_id' => $category->getID()],
                        'browse'
                    )
                )),
                'id'
            )
        );

        sort($actual);
        sort($reference);
        $this->assertSame(
            $reference,
            $actual,
            'getForCategory must mirror getListRequest(browse) for the same category.'
        );
        $this->assertNotContains((int) $draft->getID(), $actual);
        $this->assertContains((int) $published->getID(), $actual);
    }

    public static function fullTextSearchProvider(): iterable
    {
        // Spaces around search terms are trimmed
        yield [
            'search'   => ' keyword',
            'expected' => 'keyword*', // * added when there is no boolean operators
        ];
        yield [
            'search'   => 'keyword ',
            'expected' => 'keyword*', // * added when there is no boolean operators
        ];
        yield [
            'search'   => "\t +smtp",
            'expected' => "+smtp",
        ];
        yield [
            'search'   => "+smtp\r\n",
            'expected' => "+smtp",
        ];
        yield [
            'search'   => " \t +Бесплатное -программное ",
            'expected' => "+Бесплатное -программное",
        ];

        // Non word/letter/_ chars are removed
        yield [
            'search'   => '[^.,%$=°^¨%§#@?^!&\'|/\\\\~\\[\\]{}+="-]*',
            'expected' => '*',
        ];
        yield [
            'search'   => '😃 😅 😂 🫠 +unicode',
            'expected' => '+unicode',
        ];
        yield [
            'search'   => 'unicode😃unicode',
            'expected' => 'unicodeunicode*', // * added when there is no boolean operators
        ];
        yield [
            'search'   => '+underscore -_',
            'expected' => '+underscore -_',
        ];
        yield [
            'search'   => 'Бесплатное программное обеспечение',
            'expected' => 'Бесплатное* программное* обеспечение*', // * added when there is no boolean operators
        ];

        // Ponderation chars are preserved only when they are located before a search term
        yield [
            'search'   => '+(>IMAP <auth) -test ~unit',
            'expected' => '+(>IMAP <auth) -test ~unit',
        ];
        yield [
            'search'   => '+программное +(>Бесплатное <обеспечение)',
            'expected' => '+программное +(>Бесплатное <обеспечение)',
        ];
        yield [
            'search'   => 'search wi+th ope-rators in~side t<ext>s',
            'expected' => 'search* with* operators* inside* texts*', // * added when there is no boolean operators
        ];
        yield [
            'search'   => '++search --with +~-surnumerous >><<+operators',
            'expected' => '+search -with +surnumerous >operators',
        ];
        yield [
            'search'   => '+collector IMAP+OAuth',
            'expected' => '+collector IMAPOAuth',
        ];
        yield [
            'search'   => '+прогр~аммное Беспл>атное обесп<ечение',
            'expected' => '+программное Бесплатное обеспечение',
        ];

        // Parenthesis are removed when inside texts, when odd or when empty
        yield [
            'search'   => 'search <(+knowbase -item)',
            'expected' => 'search <(+knowbase -item)',
        ];
        yield [
            'search'   => '+search empty () parenthesis',
            'expected' => '+search empty parenthesis',
        ];
        yield [
            'search'   => '(search with) odd (count of parenthesis',
            'expected' => 'search* with* odd* count* of* parenthesis*', // * added when there is no boolean operators
        ];
        yield [
            'search'   => '+search -(with many) +(parenthesis in) ~(search terms) >(surrounded by) <(search operators) ("and around parenthesis")',
            'expected' => '+search -(with many) +(parenthesis in) ~(search terms) >(surrounded by) <(search operators) ("and around parenthesis")',
        ];
        yield [
            'search'   => '+tìm kiếm -(với nhiều) +(dấu ngoặc đơn trong) ~(cụm từ tìm kiếm) >(được bao quanh bởi) <(toán tử tìm kiếm) ("và xung quanh dấu ngoặc đơn")',
            'expected' => '+tìm kiếm -(với nhiều) +(dấu ngoặc đơn trong) ~(cụm từ tìm kiếm) >(được bao quanh bởi) <(toán tử tìm kiếm) ("và xung quanh dấu ngoặc đơn")',
        ];
        yield [
            'search'   => 'search with paren(thesis inside wor)ds "(or quotes)"',
            'expected' => 'search with parenthesis inside words "or quotes"', // * added when there is no boolean operators
        ];

        // Asterisks are removed when not at end of a text
        yield [
            'search'   => '+search* wildcard*',
            'expected' => '+search* wildcard*',
        ];
        yield [
            'search'   => '+search "with misplaced*" wild*card',
            'expected' => '+search "with misplaced" wildcard',
        ];
        yield [
            'search'   => 'misplaced wild*card',
            'expected' => 'misplaced* wildcard*', // * added when there is no boolean operators
        ];

        // Double quotes are removed when inside texts, when odd or when empty
        yield [
            'search'   => '+search "knowbase item"',
            'expected' => '+search "knowbase item"',
        ];
        yield [
            'search'   => '+search empty "" quotes',
            'expected' => '+search empty quotes',
        ];
        yield [
            'search'   => '"search with" odd "count of quotes',
            'expected' => 'search* with* odd* count* of* quotes*', // * added when there is no boolean operators
        ];
        yield [
            'search'   => '+search -"with many" +"quotes in" ~"search terms" >"surrounded by" <"search operators" ("and parenthesis")',
            'expected' => '+search -"with many" +"quotes in" ~"search terms" >"surrounded by" <"search operators" ("and parenthesis")',
        ];
        yield [
            'search'   => '+търсене -"с много" +"кавички в" ~"терми за търсене" >"заобиколен от" <"оператори за търсене" ("и скоби")',
            'expected' => '+търсене -"с много" +"кавички в" ~"терми за търсене" >"заобиколен от" <"оператори за търсене" ("и скоби")',
        ];
        yield [
            'search'   => 'search with qu"otes inside wor"ds',
            'expected' => 'search* with* quotes* inside* words*', // * added when there is no boolean operators
        ];

        // Search with only operators is considered as searching anything
        yield [
            'search'   => '+() ~() -() >() <()',
            'expected' => '*',
        ];

        // Extra spaces are merged
        yield [
            'search'   => '+search  -with  >many   <spaces',
            'expected' => '+search -with >many <spaces',
        ];
        yield [
            'search'   => 'որոնում   շատ   բացատներով',
            'expected' => 'որոնում* շատ* բացատներով*',  // * added when there is no boolean operators
        ];
    }

    #[DataProvider('fullTextSearchProvider')]
    public function testComputeBooleanFullTextSearch(string $search, string $expected): void
    {
        $search = $this->callPrivateMethod(new KnowbaseItem(), 'computeBooleanFullTextSearch', $search);
        $this->assertEquals($expected, $search);
    }

    public static function getListRequestProvider(): array
    {
        return [
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "+macintosh",
                    //Find rows that contain the word 'macintosh'
                ],
                'type' => 'search',
                'count' => 1,
                'sort' => ['_knowbaseitem02'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "+apple",
                    //Find rows that contain the word 'apple'
                ],
                'type' => 'search',
                'count' => 2,
                'sort' => ['_knowbaseitem01', '_knowbaseitem02'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "apple macintosh",
                    //Find rows that contain at least one of the two words.
                ],
                'type' => 'search',
                'count' => 2,
                'sort' => ['_knowbaseitem02', '_knowbaseitem01'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "base entry _knowbaseitem02",
                    //Find rows that contain at least one of the three words.
                ],
                'type' => 'search',
                'count' => 2,
                'sort' => ['_knowbaseitem02', '_knowbaseitem01'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "apple",
                    //Find rows that contain at least 'apple'
                ],
                'type' => 'search',
                'count' => 2,
                'sort' => ['_knowbaseitem01', '_knowbaseitem02'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "macintosh",
                    //Find rows that contain at least 'macintosh'
                ],
                'type' => 'search',
                'count' => 1,
                'sort' => ['_knowbaseitem02'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "Knowledge",
                    //Find rows that contain at least 'macintosh'
                ],
                'type' => 'search',
                'count' => 2,
                'sort' => ['_knowbaseitem01', '_knowbaseitem02'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "+juice +macintosh",
                    //Find rows that contain both words.
                ],
                'type' => 'search',
                'count' => 0,
                'sort' => null,
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "+apple -macintosh",
                    //Find rows that contain the word “apple” but not “macintosh”.
                ],
                'type' => 'search',
                'count' => 1,
                'sort' => ['_knowbaseitem01'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "+apple ~macintosh",
                    //Find rows that contain the word “apple”, but if the row also contains the word “macintosh”, rate it lower than if row does not.
                ],
                'type' => 'search',
                'count' => 2,
                'sort' => ['_knowbaseitem01', '_knowbaseitem02'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "+apple macintosh",
                    //Find rows that contain the word “apple”, but rank rows higher if they also contain “macintosh”.
                ],
                'type' => 'search',
                'count' => 2,
                'sort' => ['_knowbaseitem02', '_knowbaseitem01'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "+apple +(>macintosh <juice)",
                    //Find rows that contain the words “apple” and "juice", or “apple” and "macintosh" (in any order), but rank “apple macintosh" higher than “apple juice".
                ],
                'type' => 'search',
                'count' => 2,
                'sort' => ['_knowbaseitem02', '_knowbaseitem01'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "Know*",
                    //Find rows that contain "Know" such as "Knowledge"
                ],
                'type' => 'search',
                'count' => 2,
                'sort' => ['_knowbaseitem01', '_knowbaseitem02'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => "turn*",
                    //Find rows that contain "turn" such as "turnover"
                ],
                'type' => 'search',
                'count' => 1,
                'sort' => ['_knowbaseitem01'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => '"macintosh strudel"',
                    //Find rows that contain the exact phrase “macintosh strudel”
                ],
                'type' => 'search',
                'count' => 1,
                'sort' => ['_knowbaseitem02'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => '"base entry _knowbaseitem02"',
                    //Find rows that contain the exact phrase “base entry _knowbaseitem02”
                ],
                'type' => 'search',
                'count' => 1,
                'sort' => ['_knowbaseitem02'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => ' ',
                    // Make sure no errors are triggered when sending this specific request
                ],
                'type' => 'search',
                'count' => 2,
                'sort' => ['_knowbaseitem01', '_knowbaseitem02'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => '      ',
                    // Make sure no errors are triggered when sending this specific request
                ],
                'type' => 'search',
                'count' => 2,
                'sort' => ['_knowbaseitem01', '_knowbaseitem02'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => '*',
                    // Make sure no errors are triggered when sending this specific request
                ],
                'type' => 'search',
                'count' => 2,
                'sort' => ['_knowbaseitem01', '_knowbaseitem02'],
            ],
            [
                'params' => [
                    'knowbaseitemcategories_id' => 0,
                    'faq' => false,
                    'contains' => '%',
                    // Make sure no errors are triggered when sending this specific request
                ],
                'type' => 'search',
                'count' => 2,
                'sort' => ['_knowbaseitem01', '_knowbaseitem02'],
            ],
        ];
    }

    #[DataProvider('getListRequestProvider')]
    public function testGetListRequest(array $params, string $type, int $count, ?array $sort): void
    {
        global $DB;
        $this->login(); //to prevent KnowBaseItem entity restrict criteria for anonymous user

        // Build criteria array
        $criteria = KnowbaseItem::getListRequest($params, $type);
        $this->assertIsArray($criteria);

        // Check that the request is valid
        $iterator = $DB->request($criteria);

        //count KnowBaseItem found
        $this->assertEquals($count, $iterator->numrows());

        // check order if needed
        if ($sort != null) {
            $names = array_column(iterator_to_array($iterator), 'name');
            $this->assertEquals($sort, $names);
        }
    }

    public function testGetAnswerAnchors(): void
    {
        // Create test KB with multiple headers
        $kb_name = 'Test testGetAnswerAnchors' . mt_rand();
        $input = [
            'name' => $kb_name,
            'answer' => '<h1>title 1a</h1><h2>title2</h2><h1>title 1b</h1><h1>title 1c</h1>',
        ];
        $this->createItems('KnowbaseItem', [$input]);

        // Load KB
        /** @var KnowbaseItem */
        $kbi = getItemByTypeName("KnowbaseItem", $kb_name);
        $answer = $kbi->getAnswer();

        // Test anchors, there should be one per header
        $this->assertStringContainsString('<h1 id="title-1a">', $answer);
        $this->assertStringContainsString('<a href="#title-1a">', $answer);
        $this->assertStringContainsString('<h2 id="title2">', $answer);
        $this->assertStringContainsString('<a href="#title2">', $answer);
        $this->assertStringContainsString('<h1 id="title-1b">', $answer);
        $this->assertStringContainsString('<a href="#title-1b">', $answer);
        $this->assertStringContainsString('<h1 id="title-1c">', $answer);
        $this->assertStringContainsString('<a href="#title-1c">', $answer);
    }

    /**
     * To be deleted after 11.0 release
     */
    /*public function testCreateWithCategoriesDeprecated()
    {
        $root_entity = getItemByTypeName('Entity', '_test_root_entity', true);

        // Create a KB category
        $category = $this->createItem(\KnowbaseItemCategory::class, [
            'name' => __FUNCTION__ . '_1',
            'comment' => __FUNCTION__ . '_1',
            'entities_id' => $root_entity,
            'is_recursive' => 1,
            'knowbaseitemcategories_id' => 0,
        ]);

        // Create KB item with category
        $kb_item = @$this->createItem(\KnowbaseItem::class, [
            'name' => __FUNCTION__ . '_1',
            'answer' => __FUNCTION__ . '_1',
            'knowbaseitemcategories_id' => $category->getID(),
        ], ['knowbaseitemcategories_id']);

        // Get categories linked to our kb_item
        $linked_categories = (new \KnowbaseItem_KnowbaseItemCategory())->find([
            'knowbaseitems_id' => $kb_item->getID(),
        ]);

        // We expect one category
        $this->assertCount(1, $linked_categories);

        // Check category id
        $data = array_pop($linked_categories);
        $this->assertEquals($category->getID(), $data['knowbaseitemcategories_id']);
    }*/

    public function testCreateWithCategories()
    {
        global $DB;

        // Create 2 new KB categories
        $kb_category = new \KnowbaseItemCategory();
        $root_entity = getItemByTypeName('Entity', '_test_root_entity', true);
        $kb_cat_id1 = $kb_category->add([
            'name' => __FUNCTION__ . '_1',
            'comment' => __FUNCTION__ . '_1',
            'entities_id' => $root_entity,
            'is_recursive' => 1,
            'knowbaseitemcategories_id' => 0,
        ]);
        $this->assertGreaterThan(0, $kb_cat_id1);

        $kb_cat_id2 = $kb_category->add([
            'name' => __FUNCTION__ . '_2',
            'comment' => __FUNCTION__ . '_2',
            'entities_id' => $root_entity,
            'is_recursive' => 1,
            'knowbaseitemcategories_id' => 0,
        ]);
        $this->assertGreaterThan(0, $kb_cat_id2);

        $kbitem = new KnowbaseItem();
        // Create a new KB item with the first category
        $kbitems_id1 = $kbitem->add([
            'name' => __FUNCTION__ . '_1',
            'answer' => __FUNCTION__ . '_1',
            '_categories' => [$kb_cat_id1],
        ]);
        $this->assertGreaterThan(0, $kbitems_id1);

        // Expect the KB item to have the first category
        $iterator = $DB->request([
            'FROM' => \KnowbaseItem_KnowbaseItemCategory::getTable(),
            'WHERE' => [
                'knowbaseitems_id' => $kbitems_id1,
            ],
        ]);
        $this->assertEquals(1, $iterator->count());
        $this->assertEquals($kb_cat_id1, $iterator->current()['knowbaseitemcategories_id']);

        // Create a new KB item with both categories
        $kbitems_id2 = $kbitem->add([
            'name' => __FUNCTION__ . '_2',
            'answer' => __FUNCTION__ . '_2',
            '_categories' => [$kb_cat_id1, $kb_cat_id2],
        ]);
        $this->assertGreaterThan(0, $kbitems_id2);

        // Expect the KB item to have both categories
        $iterator = $DB->request([
            'FROM' => \KnowbaseItem_KnowbaseItemCategory::getTable(),
            'WHERE' => [
                'knowbaseitems_id' => $kbitems_id2,
            ],
        ]);
        $this->assertEquals(2, $iterator->count());
        $category_ids = [];
        foreach ($iterator as $row) {
            $category_ids[] = $row['knowbaseitemcategories_id'];
        }
        $this->assertEqualsCanonicalizing([$kb_cat_id1, $kb_cat_id2], $category_ids);
    }

    protected function testGetVisibilityCriteriaProvider(): iterable
    {
        yield from $this->testGetVisibilityCriteriaProvider_FAQ_public();
        yield from $this->testGetVisibilityCriteriaProvider_KB();
        yield from $this->testGetVisibilityCriteriaProvider_FAQ_logged();
    }

    protected function testGetVisibilityCriteriaProvider_FAQ_public(): iterable
    {
        global $DB, $CFG_GLPI;

        // Removing existing data
        $DB->delete(KnowbaseItem::getTable(), [new QueryExpression('true')]);
        $this->assertEquals(0, countElementsInTable(KnowbaseItem::getTable()));

        // Create set of test subjects
        $glpi_user = getItemByTypeName("User", "glpi", true);
        $this->createItems("KnowbaseItem", [
            [
                'name'     => 'FAQ 1',
                'answer'   => 'FAQ 1',
                'is_faq'   => true,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'FAQ 2',
                'answer'   => 'FAQ 2',
                'is_faq'   => true,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'FAQ 3',
                'answer'   => 'FAQ 3',
                'is_faq'   => false, // Not really a FAQ article
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
        ]);

        // Set entity for FAQ 2
        $faq_2 = getItemByTypeName("KnowbaseItem", "FAQ 2", true);
        $this->createItem("Entity_KnowbaseItem", [
            'knowbaseitems_id' => $faq_2,
            'entities_id'      => 0,
            'is_recursive'     => 1,
        ]);

        // First FAQ test case: public FAQ disabled
        $CFG_GLPI['use_public_faq'] = false;
        yield ['articles' => []];

        // Second FAQ test case: public FAQ enabled + multi entities
        $_SESSION['glpi_multientitiesmode'] = 1;
        $CFG_GLPI['use_public_faq'] = true;
        yield ['articles' => ['FAQ 2']];

        // Third FAQ test case: public FAQ enabled + single entity
        $_SESSION['glpi_multientitiesmode'] = 0;
        yield ['articles' => ['FAQ 1', 'FAQ 2']];

        // Revert session / config
        $_SESSION['glpi_multientitiesmode'] = 1;
        $CFG_GLPI['use_public_faq'] = false;
    }

    protected function testGetVisibilityCriteriaProvider_FAQ_logged(): iterable
    {
        global $DB;

        $this->login('glpi', 'glpi');

        // Removing existing data
        $DB->delete(KnowbaseItem::getTable(), [new QueryExpression('true')]);
        $this->assertEquals(0, countElementsInTable(KnowbaseItem::getTable()));

        // Create set of test subjects
        $glpi_user = getItemByTypeName("User", "glpi", true);
        $this->createItems("KnowbaseItem", [
            [
                'name'     => 'FAQ 1',
                'answer'   => 'FAQ 1',
                'is_faq'   => true,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'FAQ 2',
                'answer'   => 'FAQ 2',
                'is_faq'   => true,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'FAQ 3',
                'answer'   => 'FAQ 3',
                'is_faq'   => true,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'FAQ 4',
                'answer'   => 'FAQ 4',
                'is_faq'   => true,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'FAQ 5',
                'answer'   => 'FAQ 5',
                'is_faq'   => true,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'FAQ 6',
                'answer'   => 'FAQ 6',
                'is_faq'   => true,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'FAQ 7',
                'answer'   => 'FAQ 7',
                'is_faq'   => true,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'FAQ 8',
                'answer'   => 'FAQ 8',
                'is_faq'   => true,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'FAQ 9',
                'answer'   => 'FAQ 9',
                'is_faq'   => true,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'FAQ 10',
                'answer'   => 'FAQ 10',
                'is_faq'   => true,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'FAQ 11',
                'answer'   => 'FAQ 11',
                'is_faq'   => true,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
        ]);

        // Target user
        $this->createItems("KnowbaseItem_User", [
            [
                'knowbaseitems_id' => getItemByTypeName("KnowbaseItem", "FAQ 1", true),
                'users_id'         => getItemByTypeName("User", "post-only", true),
            ],
            [
                'knowbaseitems_id' => getItemByTypeName("KnowbaseItem", "FAQ 2", true),
                'users_id'         => getItemByTypeName("User", "tech", true),
            ],
        ]);

        // Target profile
        $this->createItems("KnowbaseItem_Profile", [
            [
                'knowbaseitems_id' => getItemByTypeName("KnowbaseItem", "FAQ 3", true),
                'profiles_id'      => getItemByTypeName("Profile", "Self-Service", true),
            ],
            [
                'knowbaseitems_id' => getItemByTypeName("KnowbaseItem", "FAQ 4", true),
                'profiles_id'      => getItemByTypeName("Profile", "Technician", true),
            ],
        ]);

        // Target group
        $group = new \Group();
        $postonly_group = (int) $group->add([
            'name' => 'Post-only group',
            'entities_id' => getItemByTypeName("Entity", "_test_root_entity", true),
            'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $postonly_group);
        $group_user = new \Group_User();
        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id'    => $postonly_group,
                'users_id'     => getItemByTypeName("User", "post-only", true),
            ])
        );
        $tech_group = (int) $group->add([
            'name' => 'Tech group',
            'entities_id' => getItemByTypeName("Entity", "_test_root_entity", true),
            'is_recursive' => 1,
        ]);
        $this->assertGreaterThan(0, $tech_group);
        $this->assertGreaterThan(
            0,
            (int) $group_user->add([
                'groups_id'    => $tech_group,
                'users_id'     => getItemByTypeName("User", "tech", true),
            ])
        );

        $this->createItems("Group_KnowbaseItem", [
            [
                'knowbaseitems_id' => getItemByTypeName("KnowbaseItem", "FAQ 5", true),
                'groups_id'        => $postonly_group,
                'entities_id'      => getItemByTypeName("Entity", "_test_root_entity", true),
                'is_recursive'     => 1,
            ],
            [
                'knowbaseitems_id' => getItemByTypeName("KnowbaseItem", "FAQ 6", true),
                'groups_id'        => $tech_group,
                'entities_id'      => getItemByTypeName("Entity", "_test_root_entity", true),
                'is_recursive'     => 1,
            ],
        ]);

        // Target entity
        $entity = new \Entity();
        $faq_entity1 = (int) $entity->add([
            'name' => 'FAQ 1 entity',
            'entities_id' => getItemByTypeName("Entity", "_test_root_entity", true),
        ]);
        $this->assertGreaterThan(0, $faq_entity1);
        $faq_entity2 = (int) $entity->add([
            'name' => 'FAQ 2 entity',
            'entities_id' => getItemByTypeName("Entity", "_test_root_entity", true),
        ]);
        $this->assertGreaterThan(0, $faq_entity2);
        $faq_entity11 = (int) $entity->add([
            'name' => 'FAQ 1.1 entity',
            'entities_id' => $faq_entity1,
        ]);
        $this->assertGreaterThan(0, $faq_entity11);

        $this->createItems("Entity_KnowbaseItem", [
            [
                'knowbaseitems_id' => getItemByTypeName("KnowbaseItem", "FAQ 7", true),
                'entities_id'      => getItemByTypeName("Entity", "_test_root_entity", true),
                'is_recursive'     => 1,
            ],
            [
                'knowbaseitems_id' => getItemByTypeName("KnowbaseItem", "FAQ 8", true),
                'entities_id'      => $faq_entity1,
            ],
            [
                'knowbaseitems_id' => getItemByTypeName("KnowbaseItem", "FAQ 9", true),
                'entities_id'      => $faq_entity2,
            ],
            [
                'knowbaseitems_id' => getItemByTypeName("KnowbaseItem", "FAQ 10", true),
                'entities_id'      => $faq_entity11,
            ],
        ]);

        // admin should see all articles
        yield ['articles' => ['FAQ 1', 'FAQ 2', 'FAQ 3', 'FAQ 4', 'FAQ 5', 'FAQ 6', 'FAQ 7', 'FAQ 8', 'FAQ 9', 'FAQ 10', 'FAQ 11']];

        // Check articles visible for "post-only" user
        $this->login('post-only', 'postonly');
        yield ['articles' => ['FAQ 1', 'FAQ 3', 'FAQ 5', 'FAQ 7', 'FAQ 8', 'FAQ 9', 'FAQ 10']];
        $this->setEntity("FAQ 1 entity", true);
        yield ['articles' => ['FAQ 1', 'FAQ 5', 'FAQ 7', 'FAQ 8', 'FAQ 10']];
        $this->setEntity("FAQ 2 entity", true);
        yield ['articles' => ['FAQ 1', 'FAQ 5', 'FAQ 7', 'FAQ 9']];
        $this->setEntity("FAQ 1.1 entity", true);
        yield ['articles' => ['FAQ 1', 'FAQ 5', 'FAQ 7', 'FAQ 10']];

        // Check articles visible for "tech" user
        $this->login('tech', 'tech');
        yield ['articles' => ['FAQ 2', 'FAQ 4', 'FAQ 6', 'FAQ 7', 'FAQ 8', 'FAQ 9', 'FAQ 10']];
        $this->setEntity("FAQ 1 entity", true);
        yield ['articles' => ['FAQ 2', 'FAQ 6', 'FAQ 7', 'FAQ 8', 'FAQ 10']];
        $this->setEntity("FAQ 2 entity", true);
        yield ['articles' => ['FAQ 2', 'FAQ 6', 'FAQ 7', 'FAQ 9']];
        $this->setEntity("FAQ 1.1 entity", true);
        yield ['articles' => ['FAQ 2', 'FAQ 6', 'FAQ 7', 'FAQ 10']];
    }

    protected function testGetVisibilityCriteriaProvider_KB(): iterable
    {
        // Create set of test subjects
        $glpi_user = getItemByTypeName("User", "glpi", true);
        $tech_user = getItemByTypeName("User", "tech", true);
        $this->createItems("KnowbaseItem", [
            [
                'name'     => 'KB 1',
                'answer'   => 'KB 1',
                'is_faq'   => false,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'KB 2',
                'answer'   => 'KB 2',
                'is_faq'   => false,
                'users_id' => $tech_user, // Specific author (our test user)
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'KB 3',
                'answer'   => 'KB 3',
                'is_faq'   => false,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'KB 4',
                'answer'   => 'KB 4',
                'is_faq'   => false,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'KB 5',
                'answer'   => 'KB 5',
                'is_faq'   => false,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'KB 6',
                'answer'   => 'KB 6',
                'is_faq'   => false,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'KB 7',
                'answer'   => 'KB 7',
                'is_faq'   => false,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'KB 8',
                'answer'   => 'KB 8',
                'is_faq'   => false,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'KB 9',
                'answer'   => 'KB 9',
                'is_faq'   => false,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'KB 10',
                'answer'   => 'KB 10',
                'is_faq'   => false,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'KB 11',
                'answer'   => 'KB 11',
                'is_faq'   => false,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'KB 12',
                'answer'   => 'KB 12',
                'is_faq'   => false,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'name'     => 'KB 13',
                'answer'   => 'KB 13',
                'is_faq'   => false,
                'users_id' => $glpi_user,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
        ]);

        // First three KB article will be visible only for a given user
        $kb_1 = getItemByTypeName("KnowbaseItem", "KB 1", true);
        $kb_2 = getItemByTypeName("KnowbaseItem", "KB 2", true);
        $kb_3 = getItemByTypeName("KnowbaseItem", "KB 3", true);
        $normal_user = getItemByTypeName("User", "normal", true);
        $this->createItems("KnowbaseItem_User", [
            [
                'knowbaseitems_id' => $kb_1,
                'users_id' => $normal_user,
            ],
            [
                'knowbaseitems_id' => $kb_2,
                'users_id' => $normal_user,
            ],
            [
                'knowbaseitems_id' => $kb_3,
                'users_id' => $tech_user, // Allowed for our test user
            ],
        ]);

        // Add group restrictions for articles 4 to 7
        $kb_4 = getItemByTypeName("KnowbaseItem", "KB 4", true);
        $kb_5 = getItemByTypeName("KnowbaseItem", "KB 5", true);
        $kb_6 = getItemByTypeName("KnowbaseItem", "KB 6", true);
        $kb_7 = getItemByTypeName("KnowbaseItem", "KB 7", true);
        $group_a = $this->createItem("Group", [
            "name" => "Group KB A",
            'is_recursive' => 1,
        ])->fields['id'];
        $group_b = $this->createItem("Group", [
            "name" => "Group KB B",
            'is_recursive' => 1,
        ])->fields['id'];
        $this->createItem("Group_User", ['users_id' => $tech_user, 'groups_id' => $group_a]);
        $this->createItems("Group_KnowbaseItem", [
            [
                'knowbaseitems_id' => $kb_4,
                'groups_id' => $group_a, // Our test user is part of this group
                'entities_id' => 0,
                'is_recursive' => 1,
                'no_entity_restriction' => false,
            ],
            [
                'knowbaseitems_id' => $kb_5,
                'groups_id' => $group_b,
                'entities_id' => 0,
                'is_recursive' => 1,
                'no_entity_restriction' => false,
            ],
            [
                'knowbaseitems_id' => $kb_6,
                'groups_id' => $group_a, // Our test user is part of this group
                'entities_id' => 0,
                'is_recursive' => 0,
                'no_entity_restriction' => false,
            ],
            [
                'knowbaseitems_id' => $kb_7,
                'groups_id' => $group_a, // Our test user is part of this group
                'entities_id' => 0,
                'is_recursive' => 0,
                'no_entity_restriction' => true,
            ],
        ]);

        // Add profiles restrictions for article 8 to 11
        $kb_8 = getItemByTypeName("KnowbaseItem", "KB 8", true);
        $kb_9 = getItemByTypeName("KnowbaseItem", "KB 9", true);
        $kb_10 = getItemByTypeName("KnowbaseItem", "KB 10", true);
        $kb_11 = getItemByTypeName("KnowbaseItem", "KB 11", true);
        $this->createItems("KnowbaseItem_Profile", [
            [
                'knowbaseitems_id' => $kb_8,
                'profiles_id' => getItemByTypeName("Profile", "Technician", true), // our test user have this profile
                'entities_id' => 0,
                'is_recursive' => 1,
                'no_entity_restriction' => false,
            ],
            [
                'knowbaseitems_id' => $kb_9,
                'profiles_id' => getItemByTypeName("Profile", "Technician", true), // our test user have this profile
                'entities_id' => 0,
                'is_recursive' => 0,
                'no_entity_restriction' => false,
            ],
            [
                'knowbaseitems_id' => $kb_10,
                'profiles_id' => getItemByTypeName("Profile", "Technician", true), // our test user have this profile
                'entities_id' => 0,
                'is_recursive' => 0,
                'no_entity_restriction' => true,
            ],
            [
                'knowbaseitems_id' => $kb_11,
                'profiles_id' => getItemByTypeName("Profile", "Hotliner", true),
                'entities_id' => 0,
                'is_recursive' => 1,
                'no_entity_restriction' => false,
            ],
        ]);

        // Add entity restriction for articles 12 and 13
        $kb_12 = getItemByTypeName("KnowbaseItem", "KB 12", true);
        $kb_13 = getItemByTypeName("KnowbaseItem", "KB 13", true);
        $this->createItems("Entity_KnowbaseItem", [
            [
                'knowbaseitems_id' => $kb_12,
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
            [
                'knowbaseitems_id' => $kb_13,
                'entities_id' => 0,
                'is_recursive' => 0,
            ],
        ]);

        // Check articles visible for "tech" user
        $this->login('tech', 'tech');
        yield [
            'articles' => [
                'FAQ 2', 'KB 2', 'KB 3', 'KB 4', 'KB 6', 'KB 7', 'KB 8', 'KB 9',
                'KB 10', 'KB 12', 'KB 13',
            ],
        ];

        // Switch entities
        $this->setEntity("_test_child_1", true);
        yield [
            'articles' => [
                'FAQ 2', 'KB 2', 'KB 3', 'KB 4', 'KB 7', 'KB 8', 'KB 10', 'KB 12',
            ],
        ];

        // Last test, admin should see all articles
        $this->login('glpi', 'glpi');
        yield [
            'articles' => [
                'FAQ 1', 'FAQ 2', 'FAQ 3', 'KB 1', 'KB 2', 'KB 3', 'KB 4',
                'KB 5', 'KB 6', 'KB 7', 'KB 8', 'KB 9', 'KB 10', 'KB 11',
                'KB 12', 'KB 13',
            ],
        ];
    }

    public function testGetVisibilityCriteria()
    {
        global $DB;

        $values = $this->testGetVisibilityCriteriaProvider();
        foreach ($values as $value) {
            $criteria = array_merge(KnowbaseItem::getVisibilityCriteria(false), [
                'SELECT' => 'name',
                'FROM' => KnowbaseItem::getTable(),
            ]);

            $data = $DB->request($criteria);
            $result = array_column(iterator_to_array($data), "name");

            // We need to sort data before comparing or the tests will fail on mariaDB
            sort($value['articles']);
            sort($result);

            $this->assertEquals($value['articles'], $result);

            // Check that every article can be opened
            $item = new KnowbaseItem();
            foreach ($value['articles'] as $name) {
                $kb_id = getItemByTypeName('KnowbaseItem', $name, true);
                $this->assertTrue($item->can($kb_id, READ));
            }

            // Compare with Search::showList result

            // Run search and capture results
            $params =  [
                'display_type' => \Search::NAMES_OUTPUT,
                'criteria'     => [],
                'item_type'    => 'KnowbaseItem',
                'is_deleted'   => 0,
                'as_map'       => 0,
                'browse'       => 0,
                'unpublished'  => 1,
            ];
            ob_start();
            \Search::showList('KnowbaseItem', $params);
            $names = ob_get_contents();
            ob_end_clean();

            // Convert results to array
            $names = trim($names);
            $names = empty($names) ? [] : explode("\n", $names);

            // Check results
            $this->assertCount(count($value['articles']), $names);
            $this->assertEqualsCanonicalizing($value['articles'], $names);
        }
    }

    public function testClone()
    {
        $this->login();
        $this->setEntity('_test_root_entity', true);

        $entity = new \Entity();
        $entity->getFromDBByCrit(['name' => '_test_root_entity']);

        $date = date('Y-m-d H:i:s');
        $_SESSION['glpi_currenttime'] = $date;

        // Test item cloning
        $knowbaseitem = new KnowbaseItem();
        $this->assertGreaterThan(
            0,
            $id = $knowbaseitem->add([
                'name'   => 'Test clone knowbaseitem',
                'answer' => 'Test clone knowbaseitem',
                'is_faq' => false,
            ])
        );

        //add target
        $tentity = new \Entity();
        $this->assertGreaterThan(
            0,
            $eid = $tentity->add([
                'name'  => 'Test kb clone',
            ])
        );
        $kbentity = new \Entity_KnowbaseItem();
        $this->assertGreaterThan(
            0,
            $kbentity->add([
                'entities_id'  => $eid,
                'knowbaseitems_id'  => $id,
            ])
        );

        //add associated elements
        $computer = new \Computer();
        $this->assertGreaterThan(
            0,
            $cid = $computer->add([
                'name'  => 'Test kb clone Cpt',
                'entities_id'  => $entity->fields['id'],
            ])
        );
        $linked_item = new \KnowbaseItem_Item();
        $this->assertGreaterThan(
            0,
            $linked_item->add([
                'itemtype'  => 'Computer',
                'items_id'  => $cid,
                'knowbaseitems_id'  => $id,
            ])
        );

        //add document
        $document = new \Document();
        $docid = (int) $document->add(['name' => 'Test link document']);
        $this->assertGreaterThan(0, $docid);

        $docitem = new \Document_Item();
        $this->assertGreaterThan(
            0,
            $docitem->add([
                'documents_id' => $docid,
                'itemtype'     => 'KnowbaseItem',
                'items_id'     => $id,
            ])
        );

        //clone!
        $kbitem = new KnowbaseItem();
        $this->assertTrue($kbitem->getFromDB($id));
        $added = $kbitem->clone();
        $this->assertGreaterThan(0, (int) $added);
        $this->assertNotEquals($kbitem->fields['id'], $added);

        $clonedKbitem = new KnowbaseItem();
        $this->assertTrue($clonedKbitem->getFromDB($added));

        $fields = $kbitem->fields;

        // Check the knowbaseitem values. Id and dates must be different, everything else must be equal
        foreach ($fields as $k => $v) {
            switch ($k) {
                case 'id':
                    $this->assertNotEquals($kbitem->getField($k), $clonedKbitem->getField($k));
                    break;
                case 'date_mod':
                case 'date_creation':
                    $dateClone = new \DateTime($clonedKbitem->getField($k));
                    $expectedDate = new \DateTime($date);
                    $this->assertEquals($expectedDate, $dateClone);
                    break;
                case 'name':
                    $this->assertEquals("{$kbitem->getField($k)} (copy)", $clonedKbitem->getField($k));
                    break;
                default:
                    $this->assertEquals($kbitem->getField($k), $clonedKbitem->getField($k));
            }
        }

        //check relations
        $relations = [
            \Entity_KnowbaseItem::class => 1,
            \KnowbaseItem_Item::class => 1,
        ];

        foreach ($relations as $relation => $expected) {
            $this->assertSame(
                $expected,
                countElementsInTable(
                    $relation::getTable(),
                    [
                        'knowbaseitems_id'  => $clonedKbitem->fields['id'],
                    ]
                )
            );
        }

        //check document
        $this->assertSame(
            1,
            countElementsInTable(
                \Document_Item::getTable(),
                [
                    'itemtype'      => 'KnowbaseItem',
                    'items_id'      => $clonedKbitem->fields['id'],
                ]
            )
        );
    }

    /**
     * @return void
     * @see https://github.com/glpi-project/glpi/issues/21873
     */
    public function testUnsetCateogry(): void
    {
        $this->login();
        $kbi = getItemByTypeName('KnowbaseItem', '_knowbaseitem01', false);

        $category = $this->createItem('KnowbaseItemCategory', [
            'name' => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1,
            'knowbaseitemcategories_id' => 0,
        ]);

        $kbi->update([
            'id' => $kbi->getID(),
            '_categories' => [$category->getID()],
            '__categories_defined' => 1,
        ]);
        $this->assertEquals(
            1,
            countElementsInTable(
                \KnowbaseItem_KnowbaseItemCategory::getTable(),
                ['knowbaseitems_id' => $kbi->getID()]
            )
        );
        $kbi->update([
            'id' => $kbi->getID(),
            '_categories' => '',
            '__categories_defined' => 1,
        ]);
        $this->assertEquals(
            0,
            countElementsInTable(
                \KnowbaseItem_KnowbaseItemCategory::getTable(),
                ['knowbaseitems_id' => $kbi->getID()]
            )
        );
    }

    public function testVisibilityRestrictionsInSearch()
    {
        $this->login();

        $fn_can_tech_see_kb = static function () {
            $criteria = [
                'itemtype' => KnowbaseItem::class,
                'criteria' => [
                    [
                        'field' => 1,
                        'searchtype' => 'contains',
                        'value' => 'KB visible to tech',
                    ],
                ],
            ];
            ob_start();
            \Search::showList(KnowbaseItem::class, $criteria, [7]);
            $output = ob_get_clean();
            return str_contains($output, "KB anwser");
        };

        $tech_user = getItemByTypeName("User", "tech", true);
        $kb_item = $this->createItem(KnowbaseItem::class, [
            'name'     => 'KB visible to tech',
            'answer'   => 'KB anwser',
            'is_faq'   => false,
            'users_id' => $_SESSION['glpiID'],
        ]);

        $this->login('tech', 'tech');

        $this->assertFalse($fn_can_tech_see_kb());

        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $kb_item->getID(),
            'users_id'         => $tech_user,
        ]);
        $this->assertTrue($fn_can_tech_see_kb());
    }

    public function testArticleAdminRendering_Subject(): void
    {
        $this->login();

        // Arrange: create an article
        $article = $this->createItem(KnowbaseItem::class, [
            'name' => "My article",
        ]);

        // Act: render article and get its title
        $html = $this->renderArticleAdmin($article->getID());
        $subject = $html->filter('[data-glpi-kb-subject]')->text();

        // Assert: title should be as configured
        $this->assertEquals("My article", $subject);
    }

    public function testArticleAdminRendering_LastUpdate(): void
    {
        $this->login();

        // Arrange: create an article, then move the time forward by 2 days
        $this->setCurrentTime("2025-01-01 10:00:00");
        $article = $this->createItem(KnowbaseItem::class, [
            'name' => "My article",
        ]);
        $this->setCurrentTime("2025-01-03 10:00:00");

        // Act: render article and get its last update info
        $html = $this->renderArticleAdmin($article->getID());
        $last_update = $html->filter('[data-testid=last-update]')->text();
        $author_link = $html->filter('[data-testid=author_link]');

        // Assert: last update should be contain relative date + the author name
        $this->assertEquals("Updated: 2 days ago by _test_user", $last_update);
        $this->assertCount(1, $author_link);
    }

    public function testArticleAdminRendering_LastUpdateDeletedUser(): void
    {
        $this->login();

        // Arrange: create an article, then move the time forward by 2 days
        $this->setCurrentTime("2025-01-01 10:00:00");
        $article = $this->createItem(KnowbaseItem::class, [
            'name' => "My article",
            'users_id' => 99999999, // Unknown
        ]);
        $this->setCurrentTime("2025-01-03 10:00:00");

        // Act: render article and get its last update info
        $html = $this->renderArticleAdmin($article->getID());
        $last_update = $html->filter('[data-testid=last-update]')->text();
        $author_link = $html->filter('[data-testid=author_link]');

        // Assert: last update should be contain relative date + the author name
        $this->assertEquals("Updated: 2 days ago by Deleted user", $last_update);
        $this->assertCount(0, $author_link);
    }

    public static function articleAdminRendering_ViewsProvider(): iterable
    {
        yield 'no views'           => [0, "No views"];
        yield 'one view'           => [1, "1 view"];
        yield 'more than one view' => [9, "9 views"];
        yield 'more than 1k'       => [1234, "1 234 views"];
    }

    #[DataProvider('articleAdminRendering_ViewsProvider')]
    public function testArticleAdminRendering_Views(
        int $views,
        string $expected,
    ): void {
        $this->login();

        // Arrange: create an article
        $article = $this->createItem(KnowbaseItem::class, [
            'name' => "My article",
            'view' => $views,
        ]);

        // Act: render article and get the views
        $html = $this->renderArticleAdmin($article->getID());
        $views = $html->filter('[data-testid=views]')->text();

        // Assert: views should be as configured
        $this->assertEquals($expected, $views);
    }

    private function renderArticleAdmin(int $id): Crawler
    {
        $kb = new KnowbaseItem();
        if (!$kb->getFromDB($id)) {
            throw new InvalidArgumentException("Failed to load article");
        }

        ob_start();
        KnowbaseItem::displayTabContentForItem($kb, 1);
        $html = ob_get_clean();

        return new Crawler($html);
    }

    public function testRelinkEmbeddedDocumentsFromTicketSolution(): void
    {
        $this->login();

        $ticket = $this->createItem(\Ticket::class, [
            'name'    => __FUNCTION__,
            'content' => __FUNCTION__,
        ]);

        $document = $this->createItem(\Document::class, [
            'name'     => __FUNCTION__,
            'filename' => 'test.txt',
        ]);

        $this->createItem(\Document_Item::class, [
            'documents_id' => $document->getID(),
            'itemtype'     => \Ticket::class,
            'items_id'     => $ticket->getID(),
        ]);

        $doc_url = '/front/document.send.php?docid=' . $document->getID()
            . '&amp;itemtype=Ticket&amp;items_id=' . $ticket->getID();

        $kb = $this->createItem(KnowbaseItem::class, [
            'name'       => __FUNCTION__,
            'answer'     => '<p><a href="' . $doc_url . '">doc</a></p>',
            'is_faq'     => 0,
            'users_id'   => Session::getLoginUserID(),
            '_itemtype'  => \Ticket::class,
            '_items_id'  => $ticket->getID(),
            '_do_item_link' => false,
        ], ['answer']);

        $this->assertTrue($kb->getFromDB($kb->getID()));

        $this->assertStringContainsString('itemtype=KnowbaseItem', $kb->fields['answer']);
        $this->assertStringContainsString('items_id=' . $kb->getID(), $kb->fields['answer']);
        $this->assertStringNotContainsString('itemtype=Ticket', $kb->fields['answer']);

        $this->assertEquals(
            1,
            countElementsInTable(\Document_Item::getTable(), [
                'documents_id' => $document->getID(),
                'itemtype'     => KnowbaseItem::class,
                'items_id'     => $kb->getID(),
            ])
        );
    }

    public function testRelinkEmbeddedDocumentsBlockedWithoutSourceAccess(): void
    {
        $this->login();

        $ticket = $this->createItem(\Ticket::class, [
            'name'    => __FUNCTION__,
            'content' => __FUNCTION__,
        ]);

        $document = $this->createItem(\Document::class, [
            'name'     => __FUNCTION__,
            'filename' => 'test.txt',
        ]);

        $this->createItem(\Document_Item::class, [
            'documents_id' => $document->getID(),
            'itemtype'     => \Ticket::class,
            'items_id'     => $ticket->getID(),
        ]);

        $profile = $this->createItem(\Profile::class, [
            'name'      => __FUNCTION__,
            'interface' => 'central',
            'knowbase'  => KnowbaseItem::PUBLISHFAQ | CREATE,
            'ticket'    => 0,
        ]);

        $user = $this->createItem(\User::class, [
            'name'         => __FUNCTION__,
            'password'     => 'testpassword',
            'password2'    => 'testpassword',
            '_profiles_id' => $profile->getID(),
            '_entities_id' => 0,
        ], ['password', 'password2']);

        $this->login($user->fields['name'], 'testpassword');

        $doc_url = '/front/document.send.php?docid=' . $document->getID()
            . '&amp;itemtype=Ticket&amp;items_id=' . $ticket->getID();

        $kb = $this->createItem(KnowbaseItem::class, [
            'name'          => __FUNCTION__,
            'answer'        => '<p><a href="' . $doc_url . '">doc</a></p>',
            'is_faq'        => 1,
            'users_id'      => $user->getID(),
            '_itemtype'     => \Ticket::class,
            '_items_id'     => $ticket->getID(),
            '_do_item_link' => false,
        ], ['answer']);

        $this->assertTrue($kb->getFromDB($kb->getID()));

        $this->assertStringNotContainsString('itemtype=KnowbaseItem', $kb->fields['answer']);
        $this->assertStringContainsString('itemtype=Ticket', $kb->fields['answer']);

        $this->assertEquals(
            0,
            countElementsInTable(\Document_Item::getTable(), [
                'documents_id' => $document->getID(),
                'itemtype'     => KnowbaseItem::class,
                'items_id'     => $kb->getID(),
            ])
        );
    }

    // ---------------------------------------------------------------------
    // Draft status (roadmap #327)
    // ---------------------------------------------------------------------

    public function testDraftDefaultsToZero(): void
    {
        $this->login();
        $kb = $this->createItem(KnowbaseItem::class, [
            'name'   => __FUNCTION__,
            'answer' => 'published by default',
            'is_faq' => 0,
        ]);
        $this->assertTrue($kb->getFromDB($kb->getID()));
        $this->assertEquals(0, (int) $kb->fields['is_draft']);
    }

    public function testDraftFlagPersisted(): void
    {
        $this->login();
        $kb = $this->createItem(KnowbaseItem::class, [
            'name'     => __FUNCTION__,
            'answer'   => 'in progress',
            'is_faq'   => 0,
            'is_draft' => 1,
        ]);
        $this->assertTrue($kb->getFromDB($kb->getID()));
        $this->assertEquals(1, (int) $kb->fields['is_draft']);
    }

    /**
     * is_faq=1 + is_draft=1 must coerce is_draft back to 0 and surface a
     * warning to the session, instead of silently mutating user input.
     */
    public function testFaqAndDraftAreMutuallyExclusive(): void
    {
        $this->login();

        $kb = new KnowbaseItem();
        $id = $kb->add([
            'name'     => __FUNCTION__,
            'answer'   => 'faq trumps draft',
            'is_faq'   => 1,
            'is_draft' => 1,
        ]);
        $this->assertGreaterThan(0, $id);

        $this->assertTrue($kb->getFromDB($id));
        $this->assertEquals(1, (int) $kb->fields['is_faq']);
        $this->assertEquals(0, (int) $kb->fields['is_draft']);

        // Consume the warning so the tearDown's session hygiene check passes.
        $this->hasSessionMessageThatContains(
            'FAQ entry cannot be a draft',
            (string) WARNING
        );
    }

    public function testCanViewItemForAuthorAndAdminAndStranger(): void
    {
        $this->login('glpi', 'glpi'); // super-admin (has KNOWBASEADMIN)
        $author_id = (int) $_SESSION['glpiID'];

        $kb = $this->createItem(KnowbaseItem::class, [
            'name'     => __FUNCTION__,
            'answer'   => 'secret',
            'is_faq'   => 0,
            'is_draft' => 1,
            'users_id' => $author_id,
        ]);

        // Make it nominally visible to "tech" via KnowbaseItem_User so we can
        // assert that visibility alone does NOT bypass the draft gate.
        $tech_id = (int) getItemByTypeName('User', 'tech', true);
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => $tech_id,
        ]);

        // Author can view.
        $reloaded = new KnowbaseItem();
        $this->assertTrue($reloaded->getFromDB($kb->getID()));
        $this->assertTrue($reloaded->canViewItem());

        // Tech (no KNOWBASEADMIN, only on the visibility list) cannot view.
        $this->login('tech', 'tech');
        $this->assertFalse($reloaded->canViewItem());

        // Admin (KNOWBASEADMIN) can view even though not the author.
        $this->login('glpi', 'glpi');
        $this->assertTrue($reloaded->canViewItem());
    }

    /**
     * A user explicitly added to the visibility list of a draft article must
     * NOT see it in SQL-driven listings.
     */
    public function testDraftHiddenFromVisibilityTargetInSqlListing(): void
    {
        global $DB;

        $this->login('glpi', 'glpi');
        $author_id = (int) $_SESSION['glpiID'];

        $kb = $this->createItem(KnowbaseItem::class, [
            'name'     => __FUNCTION__,
            'answer'   => 'still drafting',
            'is_faq'   => 0,
            'is_draft' => 1,
            'users_id' => $author_id,
        ]);
        $tech_id = (int) getItemByTypeName('User', 'tech', true);
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => $tech_id,
        ]);

        $this->login('tech', 'tech');
        $criteria = array_merge(KnowbaseItem::getVisibilityCriteria(false), [
            'SELECT' => KnowbaseItem::getTable() . '.id',
            'FROM'   => KnowbaseItem::getTable(),
        ]);
        $ids = array_column(iterator_to_array($DB->request($criteria)), 'id');
        $this->assertNotContains((int) $kb->getID(), array_map('intval', $ids));

        // Same article should appear for the author.
        $this->login('glpi', 'glpi');
        $criteria = array_merge(KnowbaseItem::getVisibilityCriteria(false), [
            'SELECT' => KnowbaseItem::getTable() . '.id',
            'FROM'   => KnowbaseItem::getTable(),
        ]);
        $ids = array_column(iterator_to_array($DB->request($criteria)), 'id');
        $this->assertContains((int) $kb->getID(), array_map('intval', $ids));
    }

    public function testDraftCannotBeUpdatedByNonAuthorWithVisibilityAccess(): void
    {
        $this->login('glpi', 'glpi');
        $author_id = (int) $_SESSION['glpiID'];

        $kb = $this->createItem(KnowbaseItem::class, [
            'name'     => __FUNCTION__,
            'answer'   => 'private draft',
            'is_faq'   => 0,
            'is_draft' => 1,
            'users_id' => $author_id,
        ]);
        // Put 'tech' on the visibility list — would normally grant
        // haveVisibilityAccess() to that user.
        $tech_id = (int) getItemByTypeName('User', 'tech', true);
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => $tech_id,
        ]);

        // Switch to the non-author and explicitly grant UPDATE on knowbase
        // so the test exercises the new draft guard (not an unrelated
        // missing right).
        $this->login('tech', 'tech');
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | UPDATE;

        $reloaded = new KnowbaseItem();
        $this->assertTrue($reloaded->getFromDB($kb->getID()));
        $this->assertFalse(
            $reloaded->can($kb->getID(), UPDATE),
            'A non-author with UPDATE + visibility access must not be able to update a draft.',
        );
        $this->assertFalse(
            $reloaded->canUpdateItem(),
            'canUpdateItem must deny non-author non-admin viewers of a draft.',
        );

        // KNOWBASEADMIN must still be able to update any draft.
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | UPDATE | KnowbaseItem::KNOWBASEADMIN;
        $this->assertTrue(
            $reloaded->canUpdateItem(),
            'KNOWBASEADMIN must keep full control over drafts.',
        );

        // The author must still be able to update their own draft.
        $this->login('glpi', 'glpi');
        $reloaded = new KnowbaseItem();
        $this->assertTrue($reloaded->getFromDB($kb->getID()));
        $this->assertTrue(
            $reloaded->canUpdateItem(),
            'The author must be able to update their own draft.',
        );
    }

    public function testDraftCannotBeDeletedOrPurgedByNonAuthor(): void
    {
        $this->login('glpi', 'glpi');
        $author_id = (int) $_SESSION['glpiID'];

        $kb = $this->createItem(KnowbaseItem::class, [
            'name'     => __FUNCTION__,
            'answer'   => 'private draft to destroy',
            'is_faq'   => 0,
            'is_draft' => 1,
            'users_id' => $author_id,
        ]);
        $tech_id = (int) getItemByTypeName('User', 'tech', true);
        $this->createItem(KnowbaseItem_User::class, [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => $tech_id,
        ]);

        // Non-author with DELETE + PURGE + visibility access must not be
        // able to dispose of the draft.
        $this->login('tech', 'tech');
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | DELETE | PURGE;

        $reloaded = new KnowbaseItem();
        $this->assertTrue($reloaded->getFromDB($kb->getID()));
        $this->assertFalse(
            $reloaded->canDeleteItem(),
            'A non-author must not be able to delete a draft they did not write.',
        );
        $this->assertFalse(
            $reloaded->canPurgeItem(),
            'A non-author must not be able to purge a draft they did not write.',
        );

        // KNOWBASEADMIN must keep full control.
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | DELETE | PURGE | KnowbaseItem::KNOWBASEADMIN;
        $this->assertTrue(
            $reloaded->canDeleteItem(),
            'KNOWBASEADMIN must be able to delete any draft.',
        );
        $this->assertTrue(
            $reloaded->canPurgeItem(),
            'KNOWBASEADMIN must be able to purge any draft.',
        );

        // The author can always dispose of their own draft.
        $this->login('glpi', 'glpi');
        $reloaded = new KnowbaseItem();
        $this->assertTrue($reloaded->getFromDB($kb->getID()));
        $this->assertTrue(
            $reloaded->canDeleteItem(),
            'The author must be able to delete their own draft.',
        );
        $this->assertTrue(
            $reloaded->canPurgeItem(),
            'The author must be able to purge their own draft.',
        );
    }

    public function testFaqExcludesDraftEvenWhenForcedInDb(): void
    {
        global $DB;

        $this->login('glpi', 'glpi');
        $kb = $this->createItem(KnowbaseItem::class, [
            'name'   => __FUNCTION__,
            'answer' => 'faq published',
            'is_faq' => 1,
        ]);
        // Force the inconsistent state directly (bypasses coercion in
        // prepareInputForUpdate). This mimics a buggy plugin or a raw SQL
        // patch — the FAQ visibility query must still hide the row.
        $DB->update(
            KnowbaseItem::getTable(),
            ['is_draft' => 1],
            ['id' => $kb->getID()],
        );

        // Switch to a helpdesk user (no KNOWBASEADMIN, no READ on knowbase →
        // hits the FAQ-only branch of getVisibilityCriteria).
        $this->login('post-only', 'postonly');

        $criteria = array_merge(KnowbaseItem::getVisibilityCriteria(false), [
            'SELECT' => KnowbaseItem::getTable() . '.id',
            'FROM'   => KnowbaseItem::getTable(),
        ]);
        $ids = array_map('intval', array_column(iterator_to_array($DB->request($criteria)), 'id'));
        $this->assertNotContains((int) $kb->getID(), $ids);
    }

    public function testRawSearchOptionsExposeDraft(): void
    {
        $kb = new KnowbaseItem();
        $found = false;
        foreach ($kb->rawSearchOptions() as $opt) {
            if (is_array($opt) && ($opt['field'] ?? null) === 'is_draft') {
                $this->assertSame('bool', $opt['datatype'] ?? null);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'is_draft search option missing from rawSearchOptions()');
    }

    private function readCronVolume(\CronTask $task): int
    {
        $ref = new \ReflectionProperty(\CronTask::class, 'volume');
        return (int) $ref->getValue($task);
    }

    public function testCronPurgedraftitemsRespectsDateModAndDraftFlag(): void
    {
        global $DB;

        $this->login();

        $old_draft = $this->createItem(KnowbaseItem::class, [
            'name'     => 'old draft',
            'answer'   => '...',
            'is_draft' => 1,
        ]);
        $recent_draft = $this->createItem(KnowbaseItem::class, [
            'name'     => 'recent draft',
            'answer'   => '...',
            'is_draft' => 1,
        ]);
        $old_published = $this->createItem(KnowbaseItem::class, [
            'name'     => 'old published',
            'answer'   => '...',
            'is_draft' => 0,
        ]);

        $old_ts = date('Y-m-d H:i:s', strtotime('-30 days'));
        $recent_ts = date('Y-m-d H:i:s', strtotime('-1 day'));
        $DB->update(KnowbaseItem::getTable(), ['date_mod' => $old_ts], ['id' => $old_draft->getID()]);
        $DB->update(KnowbaseItem::getTable(), ['date_mod' => $recent_ts], ['id' => $recent_draft->getID()]);
        $DB->update(KnowbaseItem::getTable(), ['date_mod' => $old_ts], ['id' => $old_published->getID()]);

        $task = new \CronTask();
        $task->fields = ['param' => 7];

        $this->assertSame(1, KnowbaseItem::cronPurgedraftitems($task));

        $check = new KnowbaseItem();
        $this->assertFalse($check->getFromDB($old_draft->getID()), 'old draft should have been purged');
        $this->assertTrue($check->getFromDB($recent_draft->getID()), 'recent draft should survive');
        $this->assertTrue($check->getFromDB($old_published->getID()), 'old published article should survive');
    }

    public function testCronPurgedraftitemsReturnsZeroWhenNothingToPurge(): void
    {
        $this->login();

        // Only a recent draft exists — must NOT be purged.
        $this->createItem(KnowbaseItem::class, [
            'name'     => 'recent draft',
            'answer'   => '...',
            'is_draft' => 1,
        ]);

        $task = new \CronTask();
        $task->fields = ['param' => 7];
        $task->setVolume(0);

        $this->assertSame(
            0,
            KnowbaseItem::cronPurgedraftitems($task),
            'Cron must return 0 when no draft was purged.'
        );
        $this->assertSame(0, $this->readCronVolume($task), 'Volume must stay at 0.');
    }

    public function testCronPurgedraftitemsReportsVolume(): void
    {
        global $DB;

        $this->login();

        $draft_a = $this->createItem(KnowbaseItem::class, [
            'name' => 'old draft A', 'answer' => '...', 'is_draft' => 1,
        ]);
        $draft_b = $this->createItem(KnowbaseItem::class, [
            'name' => 'old draft B', 'answer' => '...', 'is_draft' => 1,
        ]);
        $old_ts = date('Y-m-d H:i:s', strtotime('-30 days'));
        $DB->update(KnowbaseItem::getTable(), ['date_mod' => $old_ts], ['id' => $draft_a->getID()]);
        $DB->update(KnowbaseItem::getTable(), ['date_mod' => $old_ts], ['id' => $draft_b->getID()]);

        $task = new \CronTask();
        $task->fields = ['param' => 7];
        $task->setVolume(0);

        $this->assertSame(1, KnowbaseItem::cronPurgedraftitems($task));
        $this->assertSame(2, $this->readCronVolume($task), 'Volume must reflect the 2 purged drafts.');
    }

    public function testFlippingPublishedToDraftDeactivatesShareTokens(): void
    {
        $this->login('glpi', 'glpi');
        $kb = $this->createItem(KnowbaseItem::class, [
            'name'     => __FUNCTION__,
            'answer'   => '...',
            'is_draft' => 0,
        ]);

        // Insert a share token directly so we don't rely on the controller.
        $token = new ShareToken();
        $token_id = $token->add([
            'itemtype'  => KnowbaseItem::class,
            'items_id'  => $kb->getID(),
            'is_active' => 1,
        ]);
        $this->assertGreaterThan(0, $token_id);

        // Flip to draft via update — post_updateItem must deactivate (not
        // delete) the tokens so the author can revive them on republish.
        $this->assertTrue($kb->update([
            'id'       => $kb->getID(),
            'is_draft' => 1,
        ]));

        $reloaded = new ShareToken();
        $this->assertTrue($reloaded->getFromDB($token_id));
        $this->assertSame(0, (int) $reloaded->fields['is_active']);

        // Republishing does NOT auto-reactivate: the author keeps explicit
        // control via the sharing panel (cannot distinguish auto-disabled
        // from user-disabled tokens).
        $this->assertTrue($kb->update([
            'id'       => $kb->getID(),
            'is_draft' => 0,
        ]));

        $reloaded = new ShareToken();
        $this->assertTrue($reloaded->getFromDB($token_id));
        $this->assertSame(0, (int) $reloaded->fields['is_active']);
    }

    public function testDraftReportsNotShareable(): void
    {
        $this->login();
        $kb = $this->createItem(KnowbaseItem::class, [
            'name'     => __FUNCTION__,
            'answer'   => '...',
            'is_draft' => 1,
        ]);
        $reloaded = new KnowbaseItem();
        $this->assertTrue($reloaded->getFromDB($kb->getID()));
        $this->assertFalse($reloaded->canBeShared());

        $this->assertTrue($reloaded->update([
            'id'       => $kb->getID(),
            'is_draft' => 0,
        ]));
        $this->assertTrue($reloaded->getFromDB($kb->getID()));
        $this->assertTrue($reloaded->canBeShared());
    }

    public function testAllUnpublishedListingHidesOtherAuthorDraftsFromNonAdmin(): void
    {
        global $DB;

        // Author A (glpi) creates a draft with no visibility entries.
        $this->login('glpi', 'glpi');
        $author_a = (int) $_SESSION['glpiID'];

        $draft = $this->createItem(KnowbaseItem::class, [
            'name'     => __FUNCTION__,
            'answer'   => 'draft body',
            'users_id' => $author_a,
            'is_draft' => 1,
        ]);

        // Switch to a non-KNOWBASEADMIN user reaching getListRequest directly
        // (bypasses the KNOWBASEADMIN gate in searchKnowbaseItem).
        $this->login('tech', 'tech');
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | UPDATE;

        $criteria = KnowbaseItem::getListRequest(
            ['knowbaseitemcategories_id' => \KnowbaseItemCategory::SEEALL],
            'allunpublished'
        );
        $ids = array_map(
            'intval',
            array_column(iterator_to_array($DB->request($criteria)), 'id')
        );

        $this->assertNotContains(
            (int) $draft->getID(),
            $ids,
            'A non-admin reaching allunpublished must not see another author draft.'
        );
    }

    public function testAllUnpublishedListingShowsDraftsToKnowbaseAdmin(): void
    {
        global $DB;

        // Non-admin author (tech) creates a draft.
        $this->login('tech', 'tech');
        $author = (int) $_SESSION['glpiID'];

        $draft = $this->createItem(KnowbaseItem::class, [
            'name'     => __FUNCTION__,
            'answer'   => 'draft body',
            'users_id' => $author,
            'is_draft' => 1,
        ]);

        // Switch to a KNOWBASEADMIN user — must keep full visibility on
        // unpublished drafts (this is the intentional admin moderation view).
        $this->login('glpi', 'glpi');

        $criteria = KnowbaseItem::getListRequest(
            ['knowbaseitemcategories_id' => \KnowbaseItemCategory::SEEALL],
            'allunpublished'
        );
        $ids = array_map(
            'intval',
            array_column(iterator_to_array($DB->request($criteria)), 'id')
        );

        $this->assertContains(
            (int) $draft->getID(),
            $ids,
            'KNOWBASEADMIN must keep seeing other authors drafts in allunpublished.'
        );
    }
}
