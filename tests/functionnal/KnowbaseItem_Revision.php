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

/* Test for inc/knowbaseitem_revision.class.php */

class KnowbaseItem_Revision extends DbTestCase {

   public function afterTestMethod($method) {
      global $DB;
      $DB->delete('glpi_knowbaseitems_revisions', [true]);
      parent::afterTestMethod($method);
   }

   public function testGetTypeName() {
      $expected = 'Revision';
      $this->string(\KnowbaseItem_Revision::getTypeName(1))->isIdenticalTo($expected);

      $expected = 'Revisions';
      $this->string(\KnowbaseItem_Revision::getTypeName(0))->isIdenticalTo($expected);
      $this->string(\KnowbaseItem_Revision::getTypeName(2))->isIdenticalTo($expected);
      $this->string(\KnowbaseItem_Revision::getTypeName(10))->isIdenticalTo($expected);
   }

   public function testNewRevision() {
      global $DB;
      $this->login();

      $kb1 = $this->getNewKbItem();

      $where = [
         'knowbaseitems_id' => $kb1->getID(),
         'language'         => ''
      ];

      $nb = countElementsInTable(
         'glpi_knowbaseitems_revisions',
         $where
      );
      $this->integer((int)$nb)->isIdenticalTo(0);

      $this->boolean(
         $kb1->update(
            [
               'id'   => $kb1->getID(),
               'name' => '_knowbaseitem01-01'
            ]
         )
      )->isTrue();

      $nb = countElementsInTable(
         'glpi_knowbaseitems_revisions',
         $where
      );
      $this->integer((int)$nb)->isIdenticalTo(1);

      $rev_id = null;
      $result = $DB->query('SELECT MIN(id) as id FROM glpi_knowbaseitems_revisions');
      $data = $DB->fetch_assoc($result);
      $rev_id = $data['id'];

      $kb1->getFromDB($kb1->getID());
      $this->boolean($kb1->revertTo($rev_id))->isTrue();

      $nb = countElementsInTable(
         'glpi_knowbaseitems_revisions',
         $where
      );
      $this->integer((int)$nb)->isIdenticalTo(2);
   }

   public function testGetTabNameForItemNotLogged() {
      //we are not logged, we should not see revision tab
      $kb_rev = new \KnowbaseItem_Revision();
      $kb1 = $this->getNewKbItem();

      $_SESSION['glpishow_count_on_tabs'] = 1;
      $name = $kb_rev->getTabNameForItem($kb1);
      $this->string($name)->isIdenticalTo('');
   }

   public function testGetTabNameForItemLogged() {
      $this->login();

      $kb_rev = new \KnowbaseItem_Revision();
      $kb1 = $this->getNewKbItem();

      $this->boolean(
         $kb1->update(
            [
               'id'   => $kb1->getID(),
               'name' => '_knowbaseitem01-01'
            ]
         )
      )->isTrue();

      $_SESSION['glpishow_count_on_tabs'] = 1;
      $name = $kb_rev->getTabNameForItem($kb1);
      $this->string($name)->isIdenticalTo('Revision <sup class=\'tab_nb\'>1</sup>');

      $this->boolean(
         $kb1->update(
            [
               'id'   => $kb1->getID(),
               'name' => '_knowbaseitem01-02'
            ]
         )
      )->isTrue();

      $name = $kb_rev->getTabNameForItem($kb1);
      $this->string($name)->isIdenticalTo('Revisions <sup class=\'tab_nb\'>2</sup>');

      $_SESSION['glpishow_count_on_tabs'] = 0;
      $name = $kb_rev->getTabNameForItem($kb1);
      $this->string($name)->isIdenticalTo('Revisions');
   }

   private function getNewKbItem() {
      $kb1 = getItemByTypeName(\KnowbaseItem::getType(), '_knowbaseitem01');
      $toadd = $kb1->fields;
      unset($toadd['id']);
      unset($toadd['date_creation']);
      unset($toadd['date_mod']);
      $toadd['name'] = $this->getUniqueString();
      $this->integer((int)$kb1->add($toadd))->isGreaterThan(0);
      return $kb1;
   }
}
