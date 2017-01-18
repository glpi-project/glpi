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

/* Test for inc/knowbaseitem_revision.class.php */

class KnowbaseItem_RevisionTest extends DbTestCase {

   public function testGetTypeName() {
      $expected = 'Revision';
      $this->assertEquals($expected, KnowbaseItem_Revision::getTypeName(1));

      $expected = 'Revisions';
      $this->assertEquals($expected, KnowbaseItem_Revision::getTypeName(0));
      $this->assertEquals($expected, KnowbaseItem_Revision::getTypeName(2));
      $this->assertEquals($expected, KnowbaseItem_Revision::getTypeName(10));
   }

   public function testNewRevision() {
      global $DB;

      $kb1 = getItemByTypeName('KnowbaseItem', '_knowbaseitem01');

      $where = [
         'knowbaseitems_id' => $kb1->getID(),
         'language'         => ''
      ];

      $nb = countElementsInTable(
         'glpi_knowbaseitems_revisions',
         $where
      );
      $this->assertEquals(0, $nb);

      $this->assertTrue(
         $kb1->update(
            [
               'id'   => $kb1->getID(),
               'name' => '_knowbaseitem01-01'
            ]
         )
      );

      $nb = countElementsInTable(
         'glpi_knowbaseitems_revisions',
         $where
      );
      $this->assertEquals(1, $nb);

      $rev_id = null;
      $result = $DB->query('SELECT MIN(id) as id FROM glpi_knowbaseitems_revisions');
      $data = $DB->fetch_assoc($result);
      $rev_id = $data['id'];

      $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01-01');
      $this->assertTrue($kb1->revertTo($rev_id));

      $nb = countElementsInTable(
         'glpi_knowbaseitems_revisions',
         $where
      );
      $this->assertEquals(2, $nb);
   }

   public function testGetTabNameForItem() {
       global $DB;

       $kb_rev = new KnowbaseItem_Revision();
       $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');

       $_SESSION['glpishow_count_on_tabs'] = 1;
       $name = $kb_rev->getTabNameForItem($kb1);
       $this->assertEquals('Revisions <sup class=\'tab_nb\'>2</sup>', $name);

       $_SESSION['glpishow_count_on_tabs'] = 0;
       $name = $kb_rev->getTabNameForItem($kb1);
       $this->assertEquals('Revisions', $name);

       //cleanup...
       $DB->query('DELETE FROM glpi_knowbaseitems_revisions');
   }
}
