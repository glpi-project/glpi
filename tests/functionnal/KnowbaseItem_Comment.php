<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
*/

namespace tests\units;

use \DbTestCase;

/* Test for inc/knowbaseitem_comment.class.php */

/**
 * @engine isolate
 */
class KnowbaseItem_Comment extends DbTestCase {

   public function testGetTypeName() {
      $expected = 'Comment';
      $this->string(\KnowbaseItem_Comment::getTypeName(1))->isIdenticalTo($expected);

      $expected = 'Comments';
      foreach ([0, 2, 10] as $i) {
         $this->string(\KnowbaseItem_Comment::getTypeName($i))->isIdenticalTo($expected);
      }
   }

   public function testGetCommentsForKbItem() {
      $kb1 = getItemByTypeName(\KnowbaseItem::getType(), '_knowbaseitem01');

      //first, set data
      $this->addComments($kb1);
      $this->addComments($kb1, 'fr_FR');

      $nb = countElementsInTable(
         'glpi_knowbaseitems_comments'
      );
      $this->integer((int)$nb)->isIdenticalTo(10);

      // second, test what we retrieve
      $comments = \KnowbaseItem_Comment::getCommentsForKbItem($kb1->getID(), null);
      $this->array($comments)->hasSize(2);
      $this->array($comments[0])->hasSize(9);
      $this->array($comments[0]['answers'])->hasSize(2);
      $this->array($comments[0]['answers'][0]['answers'])->hasSize(1);
      $this->array($comments[0]['answers'][1]['answers'])->hasSize(0);
      $this->array($comments[1])->hasSize(9);
      $this->array($comments[1]['answers'])->hasSize(0);
   }

   /**
    * Add comments into database
    *
    * @param KnowbaseItem $kb   KB item instance
    * @param string       $lang KB item language, defaults to null
    *
    * @return void
    */
   private function addComments(\KnowbaseItem $kb, $lang = 'NULL') {
      $this->login();
      $kbcom = new \KnowbaseItem_Comment();
      $input = [
         'knowbaseitems_id' => $kb->getID(),
         'users_id'         => getItemByTypeName('User', TU_USER, true),
         'comment'          => 'Comment 1 for KB1',
         'language'         => $lang
      ];
      $kbcom1 = $kbcom->add($input);
      $this->boolean($kbcom1 > 0)->isTrue();

      $input['comment'] = 'Comment 2 for KB1';
      $kbcom2 = $kbcom->add($input);
      $this->boolean($kbcom2 > $kbcom1)->isTrue();

      //this one is from another user.
      $input['comment'] = 'Comment 1 - 1 for KB1';
      $input['parent_comment_id'] = $kbcom1;
      $input['users_id'] = getItemByTypeName('User', 'glpi', true);
      $kbcom11 = $kbcom->add($input);
      $this->boolean($kbcom11 > $kbcom2)->isTrue();

      $input['comment'] = 'Comment 1 - 2 for KB1';
      $input['users_id'] = getItemByTypeName('User', TU_USER, true);
      $kbcom12 = $kbcom->add($input);
      $this->boolean($kbcom12 > $kbcom11)->isTrue();

      $input['comment'] = 'Comment 1 - 1 - 1 for KB1';
      $input['parent_comment_id'] = $kbcom11;
      $kbcom111 = $kbcom->add($input);
      $this->boolean($kbcom111 > $kbcom12)->isTrue();
   }

   public function testGetTabNameForItemNotLogged() {
      //we are not logged, we should not see comment tab
      $kb1 = getItemByTypeName(\KnowbaseItem::getType(), '_knowbaseitem01');
      $kbcom = new \KnowbaseItem_Comment();

      $name = $kbcom->getTabNameForItem($kb1, true);
      $this->string($name)->isIdenticalTo('');
   }

   public function testGetTabNameForItemLogged() {
      $this->login();

      $kb1 = getItemByTypeName(\KnowbaseItem::getType(), '_knowbaseitem01');
      $this->addComments($kb1);
      $kbcom = new \KnowbaseItem_Comment();

      $name = $kbcom->getTabNameForItem($kb1, true);
      $this->string($name)->isIdenticalTo('Comments <sup class=\'tab_nb\'>5</sup>');

      $_SESSION['glpishow_count_on_tabs'] = 1;
      $name = $kbcom->getTabNameForItem($kb1);
      $this->string($name)->isIdenticalTo('Comments <sup class=\'tab_nb\'>5</sup>');

      $_SESSION['glpishow_count_on_tabs'] = 0;
      $name = $kbcom->getTabNameForItem($kb1);
      $this->string($name)->isIdenticalTo('Comments');
   }

   public function testDisplayComments() {
      $kb1 = getItemByTypeName(\KnowbaseItem::getType(), '_knowbaseitem01');
      $this->addComments($kb1);

      $html = \KnowbaseItem_Comment::displayComments(
         \KnowbaseItem_Comment::getCommentsForKbItem($kb1->getID(), null),
         true
      );

      preg_match_all("/li class='comment'/", $html, $results);
      $this->array($results[0])->hasSize(2);

      preg_match_all("/li class='comment subcomment'/", $html, $results);
      $this->array($results[0])->hasSize(3);

      preg_match_all("/span class='fa fa-pencil-square-o edit_item'/", $html, $results);
      $this->array($results[0])->hasSize(4);

      preg_match_all("/span class='add_answer'/", $html, $results);
      $this->array($results[0])->hasSize(5);

      //same tests, from another user
      $auth = new \Auth();
      $result = $auth->login('glpi', 'glpi', true);
      $this->boolean($result)->isTrue();

      $html = \KnowbaseItem_Comment::displayComments(
         \KnowbaseItem_Comment::getCommentsForKbItem($kb1->getID(), null),
         true
      );

      preg_match_all("/li class='comment'/", $html, $results);
      $this->array($results[0])->hasSize(2);

      preg_match_all("/li class='comment subcomment'/", $html, $results);
      $this->array($results[0])->hasSize(3);

      preg_match_all("/span class='fa fa-pencil-square-o edit_item'/", $html, $results);
      $this->array($results[0])->hasSize(1);

      preg_match_all("/span class='add_answer'/", $html, $results);
      $this->array($results[0])->hasSize(5);
   }
}
