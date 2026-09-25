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

namespace tests\units;

use Glpi\DBAL\QueryExpression;
use Glpi\Tests\DbTestCase;
use KnowbaseItem;
use KnowbaseItem_Revision;

final class KnowbaseItem_RevisionTest extends DbTestCase
{
    public function tearDown(): void
    {
        global $DB;
        $DB->delete('glpi_knowbaseitems_revisions', [new QueryExpression('true')]);
        parent::tearDown();
    }

    public function testGetTypeName()
    {
        $expected = 'Revision';
        $this->assertSame($expected, \KnowbaseItem_Revision::getTypeName(1));

        $expected = 'Revisions';
        $this->assertSame($expected, \KnowbaseItem_Revision::getTypeName(0));
        $this->assertSame($expected, \KnowbaseItem_Revision::getTypeName(2));
        $this->assertSame($expected, \KnowbaseItem_Revision::getTypeName(10));
    }

    public function testNewRevision()
    {
        global $DB;
        $this->login();

        $kb1 = $this->getNewKbItem();

        $where = [
            'knowbaseitems_id' => $kb1->getID(),
            'language'         => '',
        ];

        $nb = countElementsInTable(
            'glpi_knowbaseitems_revisions',
            $where
        );
        $this->assertEquals(0, $nb);

        $this->assertTrue(
            $kb1->update(
                [
                    'id'     => $kb1->getID(),
                    'name'   => '_knowbaseitem01-01',
                    'answer' => 'Updated answer',
                ]
            )
        );

        $nb = countElementsInTable(
            'glpi_knowbaseitems_revisions',
            $where
        );
        $this->assertEquals(1, $nb);

        $data = $DB->request([
            'SELECT' => ['MIN' => 'id as id'],
            'FROM'   => 'glpi_knowbaseitems_revisions',
        ])->current();
        $rev_id = $data['id'];

        $kb1->getFromDB($kb1->getID());
        $this->assertTrue($kb1->revertTo($rev_id));

        $nb = countElementsInTable(
            'glpi_knowbaseitems_revisions',
            $where
        );
        $this->assertEquals(2, $nb);

        //try a change on contents
        $this->assertTrue(
            $kb1->update(
                [
                    'id'     => $kb1->getID(),
                    'answer' => 'Don\'t use paths with spaces, like C:\\Program Files\\MyApp',
                ]
            )
        );

        $this->assertTrue(
            $kb1->update(
                [
                    'id'     => $kb1->getID(),
                    'answer' => 'Answer changed',
                ]
            )
        );

        $nb = countElementsInTable(
            'glpi_knowbaseitems_revisions',
            $where
        );
        $this->assertEquals(4, $nb);

        $data = $DB->request([
            'SELECT' => new QueryExpression('MAX(id) AS id'),
            'FROM'   => 'glpi_knowbaseitems_revisions',
        ])->current();
        $nrev_id = $data['id'];

        $this->assertTrue($kb1->getFromDB($kb1->getID()));
        $this->assertTrue($kb1->revertTo($nrev_id));

        $this->assertTrue($kb1->getFromDB($kb1->getID()));
        $this->assertSame('Don\'t use paths with spaces, like C:\\Program Files\\MyApp', $kb1->fields['answer']);

        //reset
        $this->assertTrue($kb1->getFromDB($kb1->getID()));
        $this->assertTrue($kb1->revertTo($rev_id));
    }

    public function testWriteActionsAreForbidden(): void
    {
        // A super-admin session has every knowbase right, yet direct writes stay refused.
        $this->login();
        $this->assertFalse(KnowbaseItem_Revision::canCreate());
        $this->assertFalse(KnowbaseItem_Revision::canUpdate());
        $this->assertFalse(KnowbaseItem_Revision::canPurge());
        $this->assertFalse(KnowbaseItem_Revision::canDelete());
    }

    public function testCanViewItemFollowsReadableParent(): void
    {
        $this->login();
        $kb = $this->getNewKbItem();
        $this->assertTrue(
            $kb->update(['id' => $kb->getID(), 'answer' => 'v2'])
        );

        $revision = new KnowbaseItem_Revision();
        $this->assertTrue($revision->getFromDBByCrit(['knowbaseitems_id' => $kb->getID()]));

        $this->assertTrue($revision->canViewItem());
    }

    public function testCanViewItemDeniedForRestrictedParent(): void
    {
        $this->login();

        // Authored by another user, no visibility grant: unreadable without KNOWBASEADMIN.
        $kb = $this->createItem(KnowbaseItem::class, [
            'name'     => 'restricted ' . $this->getUniqueString(),
            'answer'   => '<p>secret</p>',
            'users_id' => getItemByTypeName('User', 'normal', true),
            'is_faq'   => 0,
        ]);
        $this->assertTrue(
            $kb->update(['id' => $kb->getID(), 'answer' => '<p>secret v2</p>'])
        );

        $revision = new KnowbaseItem_Revision();
        $this->assertTrue($revision->getFromDBByCrit(['knowbaseitems_id' => $kb->getID()]));

        // Drop to a plain writer with no visibility on the article.
        $this->setEntity('_test_root_entity', true);
        $_SESSION['glpiactiveprofile']['knowbase'] = READ | UPDATE | CREATE | PURGE;

        $this->assertFalse($kb->can($kb->getID(), READ)); // guard: parent now unreadable
        $this->assertFalse($revision->canViewItem());
    }

    private function getNewKbItem(): KnowbaseItem
    {
        $kb1 = getItemByTypeName(KnowbaseItem::getType(), '_knowbaseitem01');
        $toadd = $kb1->fields;
        unset($toadd['id']);
        unset($toadd['date_creation']);
        unset($toadd['date_mod']);
        $toadd['name'] = $this->getUniqueString();
        $this->assertGreaterThan(0, (int) $kb1->add($toadd));
        return $kb1;
    }
}
