<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace tests\units;

use DbTestCase;

/* Test for inc/knowbaseitem_comment.class.php */

/**
 * @engine isolate
 */
class KnowbaseItem_CommentTest extends DbTestCase
{
    public function testGetTypeName()
    {
        $expected = 'Comment';
        $this->assertSame($expected, \KnowbaseItem_Comment::getTypeName(1));

        $expected = 'Comments';
        foreach ([0, 2, 10] as $i) {
            $this->assertSame($expected, \KnowbaseItem_Comment::getTypeName($i));
        }
    }

    public function testGetCommentsForKbItem()
    {
        $kb1 = getItemByTypeName(\KnowbaseItem::getType(), '_knowbaseitem01');

        //first, set data
        $this->addComments($kb1);
        $this->addComments($kb1, 'fr_FR');

        $nb = countElementsInTable(
            'glpi_knowbaseitems_comments'
        );
        $this->assertSame(10, $nb);

        // second, test what we retrieve
        $comments = \KnowbaseItem_Comment::getCommentsForKbItem($kb1->getID(), null);
        $this->assertCount(2, $comments);
        $this->assertCount(9, $comments[0]);
        $this->assertCount(2, $comments[0]['answers']);
        $this->assertCount(1, $comments[0]['answers'][0]['answers']);
        $this->assertCount(0, $comments[0]['answers'][1]['answers']);
        $this->assertCount(9, $comments[1]);
        $this->assertCount(0, $comments[1]['answers']);
    }

    /**
     * Add comments into database
     *
     * @param \KnowbaseItem $kb   KB item instance
     * @param string        $lang KB item language, defaults to null
     *
     * @return void
     */
    private function addComments(\KnowbaseItem $kb, string $lang = 'NULL')
    {
        $this->login();
        $kbcom = new \KnowbaseItem_Comment();
        $input = [
            'knowbaseitems_id' => $kb->getID(),
            'users_id'         => getItemByTypeName('User', TU_USER, true),
            'comment'          => 'Comment 1 for KB1',
            'language'         => $lang
        ];
        $kbcom1 = $kbcom->add($input);
        $this->assertTrue($kbcom1 > 0);

        $input['comment'] = 'Comment 2 for KB1';
        $kbcom2 = $kbcom->add($input);
        $this->assertTrue($kbcom2 > $kbcom1);

        //this one is from another user.
        $input['comment'] = 'Comment 1 - 1 for KB1';
        $input['parent_comment_id'] = $kbcom1;
        $input['users_id'] = getItemByTypeName('User', 'glpi', true);
        $kbcom11 = $kbcom->add($input);
        $this->assertTrue($kbcom11 > $kbcom2);

        $input['comment'] = 'Comment 1 - 2 for KB1';
        $input['users_id'] = getItemByTypeName('User', TU_USER, true);
        $kbcom12 = $kbcom->add($input);
        $this->assertTrue($kbcom12 > $kbcom11);

        $input['comment'] = 'Comment 1 - 1 - 1 for KB1';
        $input['parent_comment_id'] = $kbcom11;
        $kbcom111 = $kbcom->add($input);
        $this->assertTrue($kbcom111 > $kbcom12);
    }

    public function testGetTabNameForItemNotLogged()
    {
        //we are not logged, we should not see comment tab
        $kb1 = getItemByTypeName(\KnowbaseItem::getType(), '_knowbaseitem01');
        $kbcom = new \KnowbaseItem_Comment();

        $name = $kbcom->getTabNameForItem($kb1, true);
        $this->assertSame('', $name);
    }

    public function testGetTabNameForItemLogged()
    {
        $this->login();

        $kb1 = getItemByTypeName(\KnowbaseItem::getType(), '_knowbaseitem01');
        $this->addComments($kb1);
        $kbcom = new \KnowbaseItem_Comment();

        $name = $kbcom->getTabNameForItem($kb1, true);
        $this->assertSame('Comments <span class=\'badge\'>5</span>', $name);

        $_SESSION['glpishow_count_on_tabs'] = 1;
        $name = $kbcom->getTabNameForItem($kb1);
        $this->assertSame('Comments <span class=\'badge\'>5</span>', $name);

        $_SESSION['glpishow_count_on_tabs'] = 0;
        $name = $kbcom->getTabNameForItem($kb1);
        $this->assertSame('Comments', $name);

        // Change knowbase rights to be empty
        $_SESSION['glpiactiveprofile']['knowbase'] = 0;
        // Tab name should be empty
        $this->assertEmpty($kbcom->getTabNameForItem($kb1));

        // Add comment and read right
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | \KnowbaseItem::COMMENTS;
        // Tab name should be filled (start with "Comments")
        $this->assertMatchesRegularExpression('/^Comments/', $kbcom->getTabNameForItem($kb1));
    }

    public function testDisplayComments()
    {
        $kb1 = getItemByTypeName(\KnowbaseItem::getType(), '_knowbaseitem01');
        $this->addComments($kb1);

        $html = \KnowbaseItem_Comment::displayComments(
            \KnowbaseItem_Comment::getCommentsForKbItem($kb1->getID(), null),
            true
        );

        preg_match_all("/li class='comment'/", $html, $results);
        $this->assertCount(2, $results[0]);

        preg_match_all("/li class='comment subcomment'/", $html, $results);
        $this->assertCount(3, $results[0]);

        preg_match_all("/span class='ti ti-edit edit_item pointer'/", $html, $results);
        $this->assertCount(4, $results[0]);

        preg_match_all("/span class='add_answer'/", $html, $results);
        $this->assertCount(5, $results[0]);

        //same tests, from another user
        $auth = new \Auth();
        $result = $auth->login('glpi', 'glpi', true);
        $this->assertTrue($result);

        $html = \KnowbaseItem_Comment::displayComments(
            \KnowbaseItem_Comment::getCommentsForKbItem($kb1->getID(), null),
            true
        );

        preg_match_all("/li class='comment'/", $html, $results);
        $this->assertCount(2, $results[0]);

        preg_match_all("/li class='comment subcomment'/", $html, $results);
        $this->assertCount(3, $results[0]);

        preg_match_all("/span class='ti ti-edit edit_item pointer'/", $html, $results);
        $this->assertCount(1, $results[0]);

        preg_match_all("/span class='add_answer'/", $html, $results);
        $this->assertCount(5, $results[0]);
    }
}
