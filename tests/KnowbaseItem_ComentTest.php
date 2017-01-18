<?php
/**
-------------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2015-2016 Teclib'.

http://glpi-project.org

based on GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2003-2014 by the INDEPNET Development Team.

-------------------------------------------------------------------------

LICENSE

This file is part of GLPI.

GLPI is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
*/

/* Test for inc/knowbaseitem_comment.class.php */

class KnowbaseItem_CommentTest extends DbTestCase {

   public function testGetTypeName() {
      $expected = 'Comment';
      $this->assertEquals($expected, KnowbaseItem_Comment::getTypeName(1));

      $expected = 'Comments';
      $this->assertEquals($expected, KnowbaseItem_Comment::getTypeName(0));
      $this->assertEquals($expected, KnowbaseItem_Comment::getTypeName(2));
      $this->assertEquals($expected, KnowbaseItem_Comment::getTypeName(10));
   }

   public function testGetCommentsForKbItem() {
      //$this->Login();
      $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

      //first, set data
      $this->addComments($kb1);
      $this->addComments($kb1, 'fr_FR');

      $nb = countElementsInTable(
         'glpi_knowbaseitems_comments',
         $where
      );
      $this->assertEquals(10, $nb);

      // second, test what we retrieve
      $comments = KnowbaseItem_Comment::getCommentsForKbItem($kb1->getID(), null);
      $this->assertEquals(2, count($comments));
      $this->assertEquals(9, count($comments[0]));
      $this->assertEquals(2, count($comments[0]['answers']));
      $this->assertEquals(1, count($comments[0]['answers'][0]['answers']));
      $this->assertEquals(0, count($comments[0]['answers'][1]['answers']));
      $this->assertEquals(9, count($comments[1]));
      $this->assertEquals(0, count($comments[1]['answers']));
   }

   /**
    * Add comments into database
    *
    * @param KnowbaseItem $kb   KB item instance
    * @param string       $lang KB item language, defaults to null
    *
    * @return void
    */
   private function addComments(KnowbaseItem $kb, $lang = 'NULL') {
      $this->Login();
      $kbcom = new KnowbaseItem_Comment();
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

   public function testGetTabNameForItem() {
       $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');
       $this->addComments($kb1);
       $kbcom = new KnowbaseItem_Comment();

       $name = $kbcom->getTabNameForItem($kb1, true);
       $this->assertEquals('Comments <sup class=\'tab_nb\'>5</sup>', $name);

       $_SESSION['glpishow_count_on_tabs'] = 1;
       $name = $kbcom->getTabNameForItem($kb1);
       $this->assertEquals('Comments <sup class=\'tab_nb\'>5</sup>', $name);

       $_SESSION['glpishow_count_on_tabs'] = 0;
       $name = $kbcom->getTabNameForItem($kb1);
       $this->assertEquals('Comments', $name);
   }

   public function testDisplayComments() {
      $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');
      $this->addComments($kb1);

      $html = KnowbaseItem_Comment::displayComments(
         KnowbaseItem_Comment::getCommentsForKbItem($kb1->getID(), null),
         true
      );

      preg_match_all("/li class='comment'/", $html, $results);
      $this->assertEquals(2, count($results[0]));

      preg_match_all("/li class='comment subcomment'/", $html, $results);
      $this->assertEquals(3, count($results[0]));

      preg_match_all("/span class='edit_item'/", $html, $results);
      $this->assertEquals(4, count($results[0]));

      preg_match_all("/span class='add_answer'/", $html, $results);
      $this->assertEquals(5, count($results[0]));

      //same tests, from another user
      $auth = new Auth();
      $result = $auth->Login('glpi', 'glpi', true);
      $this->assertTrue($result);

      $html = KnowbaseItem_Comment::displayComments(
         KnowbaseItem_Comment::getCommentsForKbItem($kb1->getID(), null),
         true
      );

      preg_match_all("/li class='comment'/", $html, $results);
      $this->assertEquals(2, count($results[0]));

      preg_match_all("/li class='comment subcomment'/", $html, $results);
      $this->assertEquals(3, count($results[0]));

      preg_match_all("/span class='edit_item'/", $html, $results);
      $this->assertEquals(1, count($results[0]));

      preg_match_all("/span class='add_answer'/", $html, $results);
      $this->assertEquals(5, count($results[0]));
   }
}
