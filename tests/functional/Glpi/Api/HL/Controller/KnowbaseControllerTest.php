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

namespace tests\units\Glpi\Api\HL\Controller;

use Budget;
use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use KnowbaseItem;
use KnowbaseItemTranslation;
use Project;
use Ticket;

class KnowbaseControllerTest extends HLAPITestCase
{
    public function testCreateGetUpdateDelete()
    {
        $this->api->autoTestCRUD('/Knowledgebase/Article');
    }

    public function testCreateGetUpdateDeleteCategory()
    {
        $this->api->autoTestCRUD('/Knowledgebase/Category');
    }

    public function testCreateGetUpdateDeleteComment()
    {
        $article_id = getItemByTypeName(KnowbaseItem::class, '_knowbaseitem02', true);
        $this->api->autoTestCRUD('/Knowledgebase/Article/' . $article_id . '/Comment', [
            'language' => 'en_US',
            'user' => 2,
            'comment' => 'This is a comment on knowbase article',
        ], [
            'language' => 'en_US',
            'user' => 2,
            'comment' => 'This is an updated comment on knowbase article',
        ]);
    }

    public function testSearchAndGetRevision()
    {
        // revision creation is done automatically on article update and no deletions are possible

        $this->loginWeb();
        // Create an article

        $kbi = $this->createItem(KnowbaseItem::class, [
            'name' => '_knowbaseitem_revision_test',
            'answer' => 'Initial content',
        ]);
        // update the content to create a revision
        $this->assertTrue($kbi->update([
            'id' => $kbi->getID(),
            'answer' => 'Updated content',
        ]));
        $this->assertTrue($kbi->update([
            'id' => $kbi->getID(),
            'answer' => 'Updated content 2',
        ]));

        $this->login();

        $last_revision_id = null;
        $this->api->call(new Request('GET', '/Knowledgebase/Article/' . $kbi->getID() . '/Revision'), function ($call) use (&$last_revision_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use (&$last_revision_id) {
                    $this->assertCount(2, $content);
                    $last_revision_id = $content[count($content) - 1]['revision'];
                });
        });
        // Get last revision
        $this->api->call(new Request('GET', '/Knowledgebase/Article/' . $kbi->getID() . '/Revision/' . $last_revision_id), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('Updated content', $content['content']);
                });
        });

        // make a translation and test revisions there
        $trans = $this->createItem(KnowbaseItemTranslation::class, [
            'knowbaseitems_id' => $kbi->getID(),
            'language' => 'fr_FR',
            'name' => 'Traduction française',
            'answer' => 'Contenu initial',
        ]);
        // update the content to create a revision
        $this->assertTrue($trans->update([
            'id' => $trans->getID(),
            'answer' => 'Contenu mis à jour',
        ]));
        $this->assertTrue($trans->update([
            'id' => $trans->getID(),
            'answer' => 'Contenu mis à jour 2',
        ]));

        $last_revision_id = null;
        $this->api->call(new Request('GET', '/Knowledgebase/Article/' . $kbi->getID() . '/fr_FR/Revision'), function ($call) use (&$last_revision_id) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use (&$last_revision_id) {
                    $this->assertCount(2, $content);
                    $last_revision_id = $content[count($content) - 1]['revision'];
                });
        });
        // Get last revision
        $this->api->call(new Request('GET', '/Knowledgebase/Article/' . $kbi->getID() . '/fr_FR/Revision/' . $last_revision_id), function ($call) {
            /** @var \HLAPICallAsserter $call */
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertEquals('Contenu mis à jour', $content['content']);
                });
        });
    }

    public function testCRUDKBArticleLink()
    {
        $this->loginWeb();
        $computers_id = getItemByTypeName(\Computer::class, '_test_pc01', true);
        $article_id = $this->createItem(KnowbaseItem::class, [
            'name' => 'test_kb_article_link',
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();
        $budget_id = getItemByTypeName(Budget::class, '_budget01', true);
        $ticket_id = getItemByTypeName(Ticket::class, '_ticket01', true);
        $entity_id = $this->getTestRootEntity(true);
        $project_id = getItemByTypeName(Project::class, '_project01', true);

        $this->login();

        $this->api->autoTestCRUD('/Assets/Computer/' . $computers_id . '/KBArticle', [
            'kbarticle' => $article_id,
        ], [
            'date_creation' => '2026-03-01T10:00:00+00:00',
        ]);

        $this->api->autoTestCRUD('/Management/Budget/' . $budget_id . '/KBArticle', [
            'kbarticle' => $article_id,
        ], [
            'date_creation' => '2026-03-01T10:00:00+00:00',
        ]);

        $this->api->autoTestCRUD('/Assistance/Ticket/' . $ticket_id . '/KBArticle', [
            'kbarticle' => $article_id,
        ], [
            'date_creation' => '2026-03-01T10:00:00+00:00',
        ]);

        $this->api->autoTestCRUD('/Administration/Entity/' . $entity_id . '/KBArticle', [
            'kbarticle' => $article_id,
        ], [
            'date_creation' => '2026-03-01T10:00:00+00:00',
        ]);

        $this->api->autoTestCRUD('/Project/Project/' . $project_id . '/KBArticle', [
            'kbarticle' => $article_id,
        ], [
            'date_creation' => '2026-03-01T10:00:00+00:00',
        ]);
    }
}
