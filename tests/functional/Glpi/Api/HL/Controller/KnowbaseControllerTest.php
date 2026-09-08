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
use Entity_KnowbaseItem;
use Glpi\Http\Request;
use Glpi\Tests\HLAPITestCase;
use KnowbaseItem;
use KnowbaseItem_Favorite;
use KnowbaseItemTranslation;
use Project;
use Ticket;
use User;

class KnowbaseControllerTest extends HLAPITestCase
{
    public function testCreateGetUpdateDelete()
    {
        $this->api->autoTestCRUD('/Knowledgebase/Article');
    }

    public function testCreateGetUpdateDeleteComment()
    {
        $article_id = getItemByTypeName(KnowbaseItem::class, '_knowbaseitem02', true);
        $this->api->autoTestCRUD('/Knowledgebase/Article/' . $article_id . '/Comment', [
            'user' => 2,
            'comment' => 'This is a comment on knowbase article',
        ], [
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
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use (&$last_revision_id) {
                    $this->assertCount(2, $content);
                    $last_revision_id = $content[count($content) - 1]['revision'];
                });
        });
        // Get last revision
        $this->api->call(new Request('GET', '/Knowledgebase/Article/' . $kbi->getID() . '/Revision/' . $last_revision_id), function ($call) {
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
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use (&$last_revision_id) {
                    $this->assertCount(2, $content);
                    $last_revision_id = $content[count($content) - 1]['revision'];
                });
        });
        // Get last revision
        $this->api->call(new Request('GET', '/Knowledgebase/Article/' . $kbi->getID() . '/fr_FR/Revision/' . $last_revision_id), function ($call) {
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

    public function testIsFavoriteProperty(): void
    {
        global $DB;

        $DB->insert(KnowbaseItem::getTable(), [
            'name' => '_knowbaseitem_favorite_test',
            'answer' => 'Favorite test content',
            'entities_id' => $this->getTestRootEntity(true),
            'is_faq' => 1,
        ]);
        $article_id = $DB->insertId();

        $DB->insert(Entity_KnowbaseItem::getTable(), [
            'knowbaseitems_id' => $article_id,
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1,
        ]);

        $DB->insert(KnowbaseItem_Favorite::getTable(), [
            'knowbaseitems_id' => $article_id,
            'users_id' => getItemByTypeName(User::class, 'post-only', true),
        ]);

        $DB->insert(KnowbaseItem::getTable(), [
            'name' => '_knowbaseitem_notfavorite_test',
            'answer' => 'Not favorite test content',
            'entities_id' => $this->getTestRootEntity(true),
            'is_faq' => 1,
        ]);
        $article_id2 = $DB->insertId();

        $DB->insert(Entity_KnowbaseItem::getTable(), [
            'knowbaseitems_id' => $article_id2,
            'entities_id' => $this->getTestRootEntity(true),
            'is_recursive' => 1,
        ]);

        // The `is_favorite` property is a scalar join (a scalar value pulled from another table) which reflects if the article is marked as favorite by the current user.
        $this->login();

        $this->api->call(new Request('GET', '/Knowledgebase/Article/' . $article_id), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertArrayHasKey('is_favorite', $content);
                    $this->assertFalse($content['is_favorite']);
                });
        });

        $this->login('post-only', 'postonly');

        $this->api->call(new Request('GET', '/Knowledgebase/Article/' . $article_id), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertArrayHasKey('is_favorite', $content);
                    $this->assertTrue($content['is_favorite']);
                });
        });

        // Test is_favorite as RSQL filter
        $this->api->call(new Request('GET', '/Knowledgebase/Article'), function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(2, $content);
                    $this->assertEquals('_knowbaseitem_favorite_test', $content[0]['name']);
                    $this->assertEquals('_knowbaseitem_notfavorite_test', $content[1]['name']);
                });
        });

        $request = new Request('GET', '/Knowledgebase/Article');
        $request->setParameter('filter', 'is_favorite==1');
        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content);
                    $this->assertEquals('_knowbaseitem_favorite_test', $content[0]['name']);
                });
        });
        $request = new Request('GET', '/Knowledgebase/Article');
        $request->setParameter('filter', 'is_favorite==0');
        $this->api->call($request, function ($call) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) {
                    $this->assertCount(1, $content);
                    $this->assertEquals('_knowbaseitem_notfavorite_test', $content[0]['name']);
                });
        });
    }

    public function testCRUDKBArticleShareToken()
    {
        $this->loginWeb();
        $article_id = $this->createItem(KnowbaseItem::class, [
            'name' => 'test_kb_article_share_token',
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();

        $this->login();

        $this->api->autoTestCRUD('/Knowledgebase/Article/' . $article_id . '/ShareToken', [
            'is_active' => 1,
        ], [
            'is_active' => 0,
        ], ['new_location_singleton' => true]);
    }

    public function testKBSharingTokenGraphQL(): void
    {
        $this->loginWeb();
        $article_id = $this->createItem(KnowbaseItem::class, [
            'name' => 'test_kb_article_share_token',
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();

        $this->login();

        $this->api->call(new Request('POST', '/Knowledgebase/Article/' . $article_id . '/ShareToken'), function ($call) {
            $call->response->isOK();
        });

        $this->graphql->call('query { KBArticle(id: ' . $article_id . ') { id name share_token { token } } }', function ($call) {
            $call->response
                ->isOK()
                ->data('KBArticle', function ($data) {
                    $this->assertCount(1, $data);
                    $this->assertNotEmpty($data[0]['share_token']['token']);
                });
        });
    }

    public function testGetKBArticleByToken(): void
    {
        $this->loginWeb();
        $article_id = $this->createItem(KnowbaseItem::class, [
            'name' => 'test_kb_article_share_token',
            'entities_id' => $this->getTestRootEntity(true),
        ])->getID();

        $this->login();

        $token = null;
        $this->api->call(new Request('POST', '/Knowledgebase/Article/' . $article_id . '/ShareToken'), function ($call) use (&$token) {
            $call->response->isOK();
        });

        $this->graphql->call('query { KBArticle(id: ' . $article_id . ') { id name share_token { id token } } }', function ($call) use (&$token) {
            $call->response
                ->isOK()
                ->data('KBArticle', function ($data) use (&$token) {
                    $this->assertCount(1, $data);
                    $this->assertNotEmpty($data[0]['share_token']['token']);
                    $token = $data[0]['share_token']['token'];
                });
        });

        $this->api->call(new Request('GET', '/Knowledgebase/Article/ShareToken/' . $token), function ($call) use ($article_id) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($article_id) {
                    $this->assertEquals($article_id, (int) $content['id']);
                });
        });

        $request = new Request('PATCH', '/Knowledgebase/Article/' . $article_id . '/ShareToken');
        $request->setParameter('is_active', 0);
        $this->api->call($request, function ($call) {
            $call->response->isOK();
        });

        $this->api->call(new Request('GET', '/Knowledgebase/Article/ShareToken/' . $token), function ($call) {
            $call->response->isNotFoundError();
        });

        $this->logOut();
        $this->api->call(new Request('GET', '/Knowledgebase/Article/ShareToken/' . $token), function ($call) {
            $call->response->isNotFoundError();
        });

        $this->login('post-only', 'postonly');
        $this->api->call(new Request('GET', '/Knowledgebase/Article/ShareToken/' . $token), function ($call) {
            $call->response->isNotFoundError();
        });

        $this->login();

        $request = new Request('PATCH', '/Knowledgebase/Article/' . $article_id . '/ShareToken');
        $request->setParameter('is_active', 1);
        $this->api->call($request, function ($call) {
            $call->response->isOK();
        });

        $this->logOut();
        $this->api->call(new Request('GET', '/Knowledgebase/Article/ShareToken/' . $token), function ($call) use ($article_id) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($article_id) {
                    $this->assertEquals($article_id, (int) $content['id']);
                });
        });

        $this->login('post-only', 'postonly');
        $this->api->call(new Request('GET', '/Knowledgebase/Article/ShareToken/' . $token), function ($call) use ($article_id) {
            $call->response
                ->isOK()
                ->jsonContent(function ($content) use ($article_id) {
                    $this->assertEquals($article_id, (int) $content['id']);
                });
        });
    }
}
