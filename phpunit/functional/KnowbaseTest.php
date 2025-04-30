<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use DbTestCase;

class KnowbaseTest extends DbTestCase
{
    public function testGetTreeCategoryList()
    {
        $itemtype = 'KnowbaseItem';

        // Create empty categories
        $kbcat = new \KnowbaseItemCategory();

        $cat_1_id = $kbcat->add(
            [
                'name' => 'cat 1',
                'knowbaseitemcategories_id' => 0,
            ]
        );
        $this->assertGreaterThan(0, $cat_1_id);

        $cat_1_1_id = $kbcat->add(
            [
                'name' => 'cat 1.1',
                'knowbaseitemcategories_id' => $cat_1_id,
            ]
        );
        $this->assertGreaterThan(0, $cat_1_1_id);

        $cat_1_1_1_id = $kbcat->add(
            [
                'name' => 'cat 1.1.1',
                'knowbaseitemcategories_id' => $cat_1_1_id,
            ]
        );
        $this->assertGreaterThan(0, $cat_1_1_1_id);

        $cat_1_1_2_id = $kbcat->add(
            [
                'name' => 'cat 1.1.2',
                'knowbaseitemcategories_id' => $cat_1_1_id,
            ]
        );
        $this->assertGreaterThan(0, $cat_1_1_2_id);

        $cat_1_2_id = $kbcat->add(
            [
                'name' => 'cat 1.2',
                'knowbaseitemcategories_id' => $cat_1_id,
            ]
        );
        $this->assertGreaterThan(0, $cat_1_2_id);

        $cat_1_2_1_id = $kbcat->add(
            [
                'name' => 'cat 1.2.1',
                'knowbaseitemcategories_id' => $cat_1_2_id,
            ]
        );
        $this->assertGreaterThan(0, $cat_1_2_1_id);

        $cat_1_3_id = $kbcat->add(
            [
                'name' => 'cat 1.3',
                'knowbaseitemcategories_id' => $cat_1_id,
            ]
        );
        $this->assertGreaterThan(0, $cat_1_3_id);

        $this->login('normal', 'normal');

        // Expected for normal user
        $expected = [
            [
                'key' => -1,
                'title' => 'Without Category <span class="badge bg-azure-lt" title="This category contains Knowledge base">2</span>',
                'parent' => 0,
                'a_attr' => ['data-id' => -1],
            ],
        ];

        $tree = \KnowbaseItem::getTreeCategoryList($itemtype, []);
        $this->assertEquals($expected, $tree);

        // Add a private item (not FAQ)
        $kbitem = new \KnowbaseItem();
        $kbitem_id = $kbitem->add(
            [
                'users_id' => \Session::getLoginUserID(),
            ]
        );
        $this->assertGreaterThan(0, $kbitem_id);

        $kbitem_cat = new \KnowbaseItem_KnowbaseItemCategory();
        $kbitem_cat_id = $kbitem_cat->add(
            [
                'knowbaseitemcategories_id' => "$cat_1_1_2_id",
                'knowbaseitems_id' => "$kbitem_id",
            ]
        );
        $this->assertGreaterThan(0, $kbitem_cat_id);

        $kbitem_target = new \Entity_KnowbaseItem();
        $kbitem_target_id = $kbitem_target->add(
            [
                'knowbaseitems_id' => $kbitem_id,
                'entities_id' => 0,
                'is_recursive' => 1,
            ]
        );
        $this->assertGreaterThan(0, $kbitem_target_id);

        $tree = \KnowbaseItem::getTreeCategoryList($itemtype, []);
        $expected = [
            [
                'key' => $cat_1_id,
                'title' => 'cat 1',
                'parent' => 0,
                'a_attr' => ['data-id' => $cat_1_id],
                'children' => [
                    [
                        'key' => $cat_1_1_id,
                        'title' => 'cat 1.1',
                        'parent' => $cat_1_id,
                        'a_attr' => ['data-id' => $cat_1_1_id],
                        'children' => [
                            [
                                'key' => $cat_1_1_2_id,
                                'title' => 'cat 1.1.2 <span class="badge bg-azure-lt" title="This category contains Knowledge base">1</span>',
                                'parent' => $cat_1_1_id,
                                'a_attr' => ['data-id' => $cat_1_1_2_id],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'key' => -1,
                'title' => 'Without Category <span class="badge bg-azure-lt" title="This category contains Knowledge base">2</span>',
                'parent' => 0,
                'a_attr' => ['data-id' => -1],
            ],
        ];
        $this->assertEquals($expected, $tree);

        // Add 2nd category
        $kbitem_cat_id = $kbitem_cat->add(
            [
                'knowbaseitemcategories_id' => "$cat_1_3_id",
                'knowbaseitems_id' => "$kbitem_id",
            ]
        );
        $this->assertGreaterThan(0, $kbitem_cat_id);

        $kbitem_target = new \Entity_KnowbaseItem();
        $kbitem_target_id = $kbitem_target->add(
            [
                'knowbaseitems_id' => $kbitem_id,
                'entities_id' => 0,
                'is_recursive' => 1,
            ]
        );
        $this->assertGreaterThan(0, $kbitem_target_id);

        $tree = \KnowbaseItem::getTreeCategoryList($itemtype, []);
        $expected = [
            [
                'key' => $cat_1_id,
                'title' => 'cat 1',
                'parent' => 0,
                'a_attr' => ['data-id' => $cat_1_id],
                'children' => [
                    [
                        'key' => $cat_1_1_id,
                        'title' => 'cat 1.1',
                        'parent' => $cat_1_id,
                        'a_attr' => ['data-id' => $cat_1_1_id],
                        'children' => [
                            [
                                'key' => $cat_1_1_2_id,
                                'title' => 'cat 1.1.2 <span class="badge bg-azure-lt" title="This category contains Knowledge base">1</span>',
                                'parent' => $cat_1_1_id,
                                'a_attr' => ['data-id' => $cat_1_1_2_id],
                            ],
                        ],
                    ],
                    [
                        'key' => $cat_1_3_id,
                        'title' => 'cat 1.3 <span class="badge bg-azure-lt" title="This category contains Knowledge base">1</span>',
                        'parent' => $cat_1_id,
                        'a_attr' => ['data-id' => $cat_1_3_id],
                    ],
                ],
            ],
            [
                'key' => -1,
                'title' => 'Without Category <span class="badge bg-azure-lt" title="This category contains Knowledge base">2</span>',
                'parent' => 0,
                'a_attr' => ['data-id' => -1],
            ],
        ];
        $this->assertEquals($expected, $tree);

        // Add a FAQ item
        $kbitem = new \KnowbaseItem();
        $kbitem_id = $kbitem->add(
            [
                'is_faq' => 1,
            ]
        );
        $this->assertGreaterThan(0, $kbitem_id);

        $kbitem_cat = new \KnowbaseItem_KnowbaseItemCategory();
        $kbitem_cat_id = $kbitem_cat->add(
            [
                'knowbaseitemcategories_id' => "$cat_1_2_1_id",
                'knowbaseitems_id' => "$kbitem_id",
            ]
        );
        $this->assertGreaterThan(0, $kbitem_cat_id);

        $kbitem_target = new \Entity_KnowbaseItem();
        $kbitem_target_id = $kbitem_target->add(
            [
                'knowbaseitems_id' => $kbitem_id,
                'entities_id' => 0,
                'is_recursive' => 1,
            ]
        );
        $this->assertGreaterThan(0, $kbitem_target_id);

        // Check that tree contains root only (FAQ is not public) for anonymous user
        // Force session reset
        $session_bck = $_SESSION;
        $this->resetSession();
        $tree_with_no_public_faq = \KnowbaseItem::getTreeCategoryList($itemtype, []);

        // Check that tree contains root + category branch containing FAQ item (FAQ is public) for anonymous user
        global $CFG_GLPI;
        $use_public_faq_bck = $CFG_GLPI['use_public_faq'];
        $CFG_GLPI['use_public_faq'] = 1;
        $tree_with_public_faq = \KnowbaseItem::getTreeCategoryList($itemtype, []);

        // Put back globals
        $_SESSION = $session_bck;
        $CFG_GLPI['use_public_faq'] = $use_public_faq_bck;

        $expected = [
            [
                'key' => -1,
                'title' => 'Without Category',
                'parent' => 0,
                'a_attr' => ['data-id' => -1],
            ],
        ];
        $this->assertEquals($expected, $tree_with_no_public_faq);

        $expected = [
            [
                'key' => $cat_1_id,
                'title' => 'cat 1',
                'parent' => 0,
                'a_attr' => ['data-id' => $cat_1_id],
                'children' => [
                    [
                        'key' => $cat_1_2_id,
                        'title' => 'cat 1.2',
                        'parent' => $cat_1_id,
                        'a_attr' => ['data-id' => $cat_1_2_id],
                        'children' => [
                            [
                                'key' => $cat_1_2_1_id,
                                'title' => 'cat 1.2.1 <span class="badge bg-azure-lt" title="This category contains Knowledge base">1</span>',
                                'parent' => $cat_1_2_id,
                                'a_attr' => ['data-id' => $cat_1_2_1_id],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'key' => -1,
                'title' => 'Without Category',
                'parent' => 0,
                'a_attr' => ['data-id' => -1],
            ],
        ];
        $this->assertEquals($expected, $tree_with_public_faq);
    }
}
